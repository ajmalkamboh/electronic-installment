<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\SerializedItem;
use App\Services\Inventory\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    public function __construct(
        protected TransferService $transferService
    ) {}

    /**
     * Display a listing of transfer orders with metrics and filters.
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $request->get('branch_id');
        $tab = $request->get('tab', 'all');
        $status = $request->get('status');

        $query = InventoryTransfer::where('company_id', $companyId)
            ->with(['sourceBranch', 'destinationBranch', 'creator', 'items']);

        // Tab filters
        if ($tab === 'outgoing' && $branchId) {
            $query->where('source_branch_id', $branchId);
        } elseif ($tab === 'incoming' && $branchId) {
            $query->where('destination_branch_id', $branchId);
        } elseif ($tab === 'in_transit') {
            $query->where('status', 'dispatched');
        } elseif ($tab === 'completed') {
            $query->where('status', 'received');
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($branchId && !in_array($tab, ['outgoing', 'incoming'])) {
            $query->where(function ($q) use ($branchId) {
                $q->where('source_branch_id', $branchId)
                  ->orWhere('destination_branch_id', $branchId);
            });
        }

        $transfers = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $metrics = $this->transferService->getTransferMetrics($companyId, $branchId ? (int) $branchId : null);
        $branches = Branch::where('company_id', $companyId)->get();

        return view('tenant.transfers.index', compact('transfers', 'metrics', 'branches', 'branchId', 'tab', 'status'));
    }

    /**
     * Show the transfer creation form.
     */
    public function create(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->get();

        $sourceBranchId = $request->get('source_branch_id', Auth::user()->branch_id ?? $branches->first()?->id);

        // Get in_stock serialized hardware available at source branch
        $availableItems = SerializedItem::where('company_id', $companyId)
            ->where('branch_id', $sourceBranchId)
            ->where('status', 'in_stock')
            ->with(['product'])
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['category'])
            ->get();

        return view('tenant.transfers.create', compact('branches', 'sourceBranchId', 'availableItems', 'products'));
    }

    /**
     * Store a new transfer order.
     */
    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validated = $request->validate([
            'source_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id|different:source_branch_id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.serialized_item_id' => 'nullable|exists:serialized_items,id',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $transfer = $this->transferService->createTransfer(
            Auth::user()->company,
            (int) $validated['source_branch_id'],
            (int) $validated['destination_branch_id'],
            $validated['items'],
            Auth::id(),
            $validated['notes'] ?? null
        );

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Transfer request {$transfer->transfer_number} submitted successfully.");
    }

    /**
     * Display transfer order details with timeline and actions.
     */
    public function show(InventoryTransfer $transfer)
    {
        $this->authorizeTransfer($transfer);

        $transfer->load([
            'sourceBranch',
            'destinationBranch',
            'creator',
            'approver',
            'dispatcher',
            'receiver',
            'items.product.category',
            'items.serializedItem',
        ]);

        return view('tenant.transfers.show', compact('transfer'));
    }

    /**
     * Approve transfer order.
     */
    public function approve(Request $request, InventoryTransfer $transfer)
    {
        $this->authorizeTransfer($transfer);

        $this->transferService->approveTransfer($transfer, Auth::id());

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Transfer {$transfer->transfer_number} approved successfully. Ready for showroom dispatch.");
    }

    /**
     * Dispatch transfer order with logistics and generate Outward Security Gate Pass.
     */
    public function dispatchOrder(Request $request, InventoryTransfer $transfer)
    {
        $this->authorizeTransfer($transfer);

        $validated = $request->validate([
            'driver_name' => 'required|string|max:191',
            'driver_cnic' => 'required|string|max:25',
            'driver_phone' => 'required|string|max:25',
            'vehicle_number' => 'required|string|max:50',
            'transport_company' => 'nullable|string|max:191',
            'gate_pass_notes' => 'nullable|string|max:500',
        ]);

        $this->transferService->dispatchTransfer($transfer, $validated, Auth::id());

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Transfer {$transfer->transfer_number} dispatched! Gate Pass {$transfer->gate_pass_number} generated.");
    }

    /**
     * Receive transfer order at destination showroom, inspect items, and accept inventory.
     */
    public function receiveOrder(Request $request, InventoryTransfer $transfer)
    {
        $this->authorizeTransfer($transfer);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.condition' => 'required|in:good,damaged,missing',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $this->transferService->receiveTransfer($transfer, $validated['items'], Auth::id());

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Transfer {$transfer->transfer_number} received and verified into showroom inventory.");
    }

    /**
     * Cancel transfer order.
     */
    public function cancel(Request $request, InventoryTransfer $transfer)
    {
        $this->authorizeTransfer($transfer);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $this->transferService->cancelTransfer($transfer, Auth::id(), $validated['rejection_reason']);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Transfer {$transfer->transfer_number} cancelled.");
    }

    /**
     * Official Outward Security Gate Pass / Delivery Challan print layout.
     */
    public function gatePass(InventoryTransfer $transfer)
    {
        $this->authorizeTransfer($transfer);

        if (!$transfer->isGatePassReady()) {
            abort(400, 'Security Gate Pass is only available for dispatched or received transfers.');
        }

        $transfer->load([
            'company',
            'sourceBranch',
            'destinationBranch',
            'dispatcher',
            'items.product.category',
            'items.serializedItem',
        ]);

        return view('tenant.transfers.gate_pass', compact('transfer'));
    }

    /**
     * Verify tenant access.
     */
    protected function authorizeTransfer(InventoryTransfer $transfer): void
    {
        if ($transfer->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized transfer access.');
        }
    }
}
