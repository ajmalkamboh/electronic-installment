<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Product;
use App\Models\SerializedItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Inventory\InventoryService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Showroom aggregated inventory levels.
     */
    public function index(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $query = BranchInventory::where('company_id', $company->id)
            ->with(['branch', 'product.category']);

        // Filter by Showroom Branch
        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        // Filter by Product
        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        $inventories = $query->orderBy('branch_id')->paginate(15)->withQueryString();

        // Metrics
        $totalOnHand = (int) BranchInventory::where('company_id', $company->id)->sum('quantity_on_hand');
        $totalReserved = (int) BranchInventory::where('company_id', $company->id)->sum('quantity_reserved');
        $totalAvailable = (int) BranchInventory::where('company_id', $company->id)->sum('quantity_available');
        $branches = Branch::where('company_id', $company->id)->where('status', 'active')->orderBy('name')->get();
        $products = Product::where('company_id', $company->id)->where('is_active', true)->orderBy('brand')->get();

        return view('tenant.inventory.index', compact(
            'inventories',
            'totalOnHand',
            'totalReserved',
            'totalAvailable',
            'branches',
            'products'
        ));
    }

    /**
     * Serialized hardware units list (IMEI / Serial directory).
     */
    public function serializedIndex(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $query = SerializedItem::where('company_id', $company->id)
            ->with(['branch', 'product', 'supplier']);

        // Search Filter (IMEI 1, IMEI 2, Serial Number, Asset Tag)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('imei_1', 'like', "%{$search}%")
                    ->orWhere('imei_2', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('asset_tag', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('brand', 'like', "%{$search}%")
                            ->orWhere('model_name', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Branch Filter
        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $branches = Branch::where('company_id', $company->id)->where('status', 'active')->orderBy('name')->get();

        return view('tenant.inventory.serialized', compact('items', 'branches'));
    }

    /**
     * Stock intake form (Receive wholesale stock).
     */
    public function createReceipt(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $products = Product::where('company_id', $company->id)->where('is_active', true)->orderBy('brand')->get();
        $branches = Branch::where('company_id', $company->id)->where('status', 'active')->orderBy('name')->get();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        $selectedProductId = $request->input('product_id');
        $selectedProduct = $selectedProductId ? Product::where('company_id', $company->id)->find($selectedProductId) : null;

        return view('tenant.inventory.receipt', compact('products', 'branches', 'suppliers', 'selectedProduct'));
    }

    /**
     * Process stock intake and barcode/IMEI entry.
     */
    public function storeReceipt(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $company->id)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $company->id)],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'bulk_imei_input' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.imei_1' => ['nullable', 'string', 'max:50'],
            'items.*.imei_2' => ['nullable', 'string', 'max:50'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.asset_tag' => ['nullable', 'string', 'max:50'],
            'items.*.color' => ['nullable', 'string', 'max:50'],
            'items.*.purchase_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branch = Branch::findOrFail($validated['branch_id']);
        $product = Product::findOrFail($validated['product_id']);
        $supplier = ! empty($validated['supplier_id']) ? Supplier::find($validated['supplier_id']) : null;
        $purchaseCost = ! empty($validated['purchase_cost']) ? (float) $validated['purchase_cost'] : null;

        $unitsData = [];

        // 1. Process line-by-line items array
        if (! empty($validated['items']) && is_array($validated['items'])) {
            foreach ($validated['items'] as $item) {
                if (! empty($item['imei_1']) || ! empty($item['serial_number']) || ! empty($item['asset_tag'])) {
                    $unitsData[] = $item;
                }
            }
        }

        // 2. Process fast bulk barcode/IMEI scanner textarea (one IMEI or Serial per line)
        if (! empty($validated['bulk_imei_input'])) {
            $lines = preg_split('/[\r\n]+/', $validated['bulk_imei_input']);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (empty($trimmed)) {
                    continue;
                }

                // If line contains comma or tab, parse dual IMEI (e.g. IMEI1,IMEI2)
                $parts = preg_split('/[,\t]+/', $trimmed);
                if (count($parts) >= 2) {
                    $unitsData[] = [
                        'imei_1' => trim($parts[0]),
                        'imei_2' => trim($parts[1]),
                        'purchase_cost' => $purchaseCost,
                    ];
                } elseif (strlen($trimmed) === 15 && ctype_digit($trimmed)) {
                    // Standard 15-digit IMEI
                    $unitsData[] = [
                        'imei_1' => $trimmed,
                        'purchase_cost' => $purchaseCost,
                    ];
                } else {
                    // Appliance Serial or Asset Tag
                    $unitsData[] = [
                        'serial_number' => $trimmed,
                        'purchase_cost' => $purchaseCost,
                    ];
                }
            }
        }

        if (empty($unitsData)) {
            return back()->withInput()->with('error', 'Please enter at least one IMEI or Serial Number to receive stock.');
        }

        $receivedItems = $this->inventoryService->receiveStock(
            $branch,
            $product,
            $unitsData,
            $supplier,
            $purchaseCost,
            $request->user(),
            $validated['reference_number'] ?? null
        );

        $count = count($receivedItems);
        return redirect()->route('inventory.index', ['branch_id' => $branch->id])
            ->with('success', "Successfully received {$count} units of {$product->full_name} into {$branch->name}.");
    }

    /**
     * Inter-branch stock transfer form.
     */
    public function createTransfer(SerializedItem $item): View
    {
        $company = $this->tenantContext->getCompany();
        if ($item->company_id !== $company->id) {
            abort(404);
        }

        $item->load(['branch', 'product']);

        $destinationBranches = Branch::where('company_id', $company->id)
            ->where('id', '!=', $item->branch_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('tenant.inventory.transfer', compact('item', 'destinationBranches'));
    }

    /**
     * Process inter-branch stock transfer.
     */
    public function storeTransfer(Request $request, SerializedItem $item): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($item->company_id !== $company->id) {
            abort(404);
        }

        $validated = $request->validate([
            'destination_branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $company->id),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $destinationBranch = Branch::findOrFail($validated['destination_branch_id']);

        $this->inventoryService->transferSerializedItem(
            $item,
            $destinationBranch,
            $request->user(),
            $validated['notes'] ?? null
        );

        return redirect()->route('inventory.serialized')
            ->with('success', "Item {$item->identifier_label} transferred to {$destinationBranch->name}.");
    }

    /**
     * Stock movements audit ledger.
     */
    public function movements(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $query = StockMovement::where('company_id', $company->id)
            ->with(['product', 'serializedItem', 'sourceBranch', 'destinationBranch', 'user']);

        if ($type = $request->input('movement_type')) {
            $query->where('movement_type', $type);
        }

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $products = Product::where('company_id', $company->id)->where('is_active', true)->orderBy('brand')->get();

        return view('tenant.inventory.movements', compact('movements', 'products'));
    }
}
