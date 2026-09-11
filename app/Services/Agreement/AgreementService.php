<?php

namespace App\Services\Agreement;

use App\Models\AgreementGuarantor;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\SerializedItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Payment\ScheduleGenerator;
use App\Services\Pricing\PricingEngine;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgreementService
{
    public function __construct(
        protected PricingEngine $pricingEngine,
        protected ScheduleGenerator $scheduleGenerator
    ) {}

    /**
     * Create a new installment agreement in draft status.
     * Enforces customer eligibility, runs pricing calculations,
     * reserves showroom inventory, and binds guarantors.
     */
    public function createDraft(array $data, User $creator): InstallmentAgreement
    {
        return DB::transaction(function () use ($data, $creator) {
            $customer = Customer::where('company_id', $creator->company_id)->findOrFail($data['customer_id']);

            if ($customer->isBlacklisted()) {
                throw new DomainException("Customer {$customer->full_name} is blacklisted and cannot enter into installment agreements.");
            }

            $branch = Branch::where('company_id', $creator->company_id)->findOrFail($data['branch_id']);
            $product = Product::where('company_id', $creator->company_id)->findOrFail($data['product_id']);
            $plan = InstallmentPlan::where('company_id', $creator->company_id)->findOrFail($data['installment_plan_id']);

            $serializedItem = null;
            if (! empty($data['serialized_item_id'])) {
                $serializedItem = SerializedItem::where('company_id', $creator->company_id)
                    ->where('branch_id', $branch->id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->findOrFail($data['serialized_item_id']);

                if (! $serializedItem->isAvailable()) {
                    throw new DomainException("Hardware unit ({$serializedItem->identifier_label}) is not available in stock.");
                }
            }

            // Financial computation via PricingEngine
            $cashPrice = ! empty($data['cash_price']) ? (float) $data['cash_price'] : (float) $product->base_cash_price;
            $markupRate = ! empty($data['markup_rate']) ? (float) $data['markup_rate'] : (float) $plan->default_markup_rate_pct;
            $minDownPaymentPct = (float) $product->min_down_payment_pct;

            $defaultDownPayment = round($cashPrice * ($minDownPaymentPct / 100.0), -2);
            $downPayment = isset($data['down_payment']) && $data['down_payment'] !== null
                ? (float) $data['down_payment']
                : $defaultDownPayment;

            $pricingResult = $this->pricingEngine->calculateCustom(
                cashPrice: $cashPrice,
                tenureMonths: (int) $plan->tenure_months,
                model: $plan->markup_calculation_model,
                markupRate: $markupRate,
                downPayment: $downPayment,
                fixedAmount: $plan->fixed_markup_amount ? (float) $plan->fixed_markup_amount : null,
                frequency: $plan->installment_frequency,
                minDownPaymentPct: $minDownPaymentPct
            );

            // Generate sequential human-readable account number
            $accountNumber = $this->generateAccountNumber($branch);

            // Date calculations
            $startDate = ! empty($data['start_date']) ? Carbon::parse($data['start_date']) : Carbon::today();
            $firstDueDate = ! empty($data['first_due_date'])
                ? Carbon::parse($data['first_due_date'])
                : (clone $startDate)->addMonth();
            $maturityDate = (clone $firstDueDate)->addMonths($plan->tenure_months - 1);

            $agreement = InstallmentAgreement::create([
                'company_id' => $creator->company_id,
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'serialized_item_id' => $serializedItem?->id,
                'installment_plan_id' => $plan->id,
                'credit_assessment_id' => $data['credit_assessment_id'] ?? null,
                'creator_id' => $creator->id,
                'account_number' => $accountNumber,
                'status' => 'draft',
                'cash_price' => $pricingResult->cashPrice,
                'down_payment_amount' => $pricingResult->downPayment,
                'down_payment_paid' => 0.00,
                'financed_principal' => $pricingResult->financedPrincipal,
                'markup_rate_pct' => $pricingResult->markupRatePct,
                'markup_amount' => $pricingResult->markupAmount,
                'total_financed' => $pricingResult->totalFinanced,
                'total_payable' => $pricingResult->totalPayable,
                'installment_amount' => $pricingResult->installmentAmount,
                'tenure_months' => $pricingResult->tenureMonths,
                'installment_frequency' => $pricingResult->installmentFrequency,
                'total_installments' => $pricingResult->tenureMonths,
                'paid_installments' => 0,
                'remaining_balance' => $pricingResult->totalFinanced,
                'start_date' => $startDate->toDateString(),
                'first_due_date' => $firstDueDate->toDateString(),
                'maturity_date' => $maturityDate->toDateString(),
                'terms_conditions_snapshot' => $this->buildTermsSnapshot($customer, $product, $pricingResult, $plan),
            ]);

            // Hardware Reservation: lock the selected device
            if ($serializedItem) {
                $serializedItem->status = 'reserved';
                $serializedItem->save();

                $inventory = BranchInventory::firstOrCreate(
                    ['branch_id' => $branch->id, 'product_id' => $product->id],
                    ['company_id' => $creator->company_id, 'quantity_on_hand' => 0, 'quantity_reserved' => 0, 'quantity_available' => 0]
                );
                $inventory->quantity_reserved += 1;
                $inventory->recalculateAvailable();
                $inventory->save();
            }

            // Bind Guarantors
            if (! empty($data['guarantor_ids']) && is_array($data['guarantor_ids'])) {
                foreach ($data['guarantor_ids'] as $index => $guarantorId) {
                    AgreementGuarantor::create([
                        'installment_agreement_id' => $agreement->id,
                        'guarantor_id' => $guarantorId,
                        'is_primary' => $index === 0,
                        'verification_status' => 'verified',
                    ]);
                }
            }

            return $agreement;
        });
    }

    /**
     * Submit draft agreement for underwriting review.
     */
    public function submitForReview(InstallmentAgreement $agreement, User $user): InstallmentAgreement
    {
        if (! $agreement->isDraft()) {
            throw new DomainException("Only draft agreements can be submitted for review.");
        }

        $agreement->status = 'under_review';
        $agreement->submitted_at = now();
        $agreement->save();

        return $agreement;
    }

    /**
     * Approve agreement.
     */
    public function approve(InstallmentAgreement $agreement, User $approver, ?string $notes = null): InstallmentAgreement
    {
        if (! in_array($agreement->status, ['draft', 'under_review'])) {
            throw new DomainException("Agreement in status '{$agreement->status}' cannot be approved.");
        }

        $agreement->status = 'approved';
        $agreement->approved_by_id = $approver->id;
        $agreement->approved_at = now();
        $agreement->approval_notes = $notes;
        $agreement->save();

        return $agreement;
    }

    /**
     * Record customer down payment collection into the cashier drawer.
     */
    public function recordDownPayment(
        InstallmentAgreement $agreement,
        float $amount,
        string $paymentMethod,
        ?string $receiptRef,
        User $cashier,
        ?string $notes = null
    ): InstallmentAgreement {
        if ($amount <= 0) {
            throw new DomainException("Down payment amount must be greater than zero.");
        }

        $agreement->down_payment_paid += $amount;
        $agreement->down_payment_method = $paymentMethod;
        $agreement->down_payment_receipt_ref = $receiptRef;
        $agreement->save();

        return $agreement;
    }

    /**
     * Disburse product hardware and transition agreement to active.
     * Enforces mandatory down payment collection before disbursement.
     */
    public function disburseAndActivate(
        InstallmentAgreement $agreement,
        User $officer,
        ?string $handoverNotes = null
    ): InstallmentAgreement {
        return DB::transaction(function () use ($agreement, $officer, $handoverNotes) {
            if ($agreement->status !== 'approved') {
                throw new DomainException("Agreement must be approved before merchandise handover (Current status: {$agreement->status}).");
            }

            if (! $agreement->isDownPaymentSatisfied()) {
                throw new DomainException("Cannot disburse merchandise: Down payment deficit of Rs. " . number_format($agreement->down_payment_deficit) . " remains unpaid.");
            }

            // If hardware item was allocated, disburse it and update stock counters
            if ($agreement->serialized_item_id) {
                $item = SerializedItem::lockForUpdate()->find($agreement->serialized_item_id);
                if ($item) {
                    $item->status = 'disbursed';
                    $item->save();

                    $inventory = BranchInventory::where('branch_id', $agreement->branch_id)
                        ->where('product_id', $agreement->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inventory) {
                        $inventory->quantity_on_hand = max(0, $inventory->quantity_on_hand - 1);
                        $inventory->quantity_reserved = max(0, $inventory->quantity_reserved - 1);
                        $inventory->recalculateAvailable();
                        $inventory->save();
                    }

                    StockMovement::create([
                        'company_id' => $agreement->company_id,
                        'product_id' => $agreement->product_id,
                        'serialized_item_id' => $item->id,
                        'source_branch_id' => $agreement->branch_id,
                        'destination_branch_id' => null,
                        'user_id' => $officer->id,
                        'movement_type' => 'sale_disbursement',
                        'quantity' => 1,
                        'reference_number' => $agreement->account_number,
                        'notes' => "Merchandise handover for active agreement {$agreement->account_number}",
                        'created_at' => now(),
                    ]);
                }
            }

            $agreement->status = 'active';
            $agreement->disbursed_by_id = $officer->id;
            $agreement->activated_at = now();
            $agreement->handover_notes = $handoverNotes;
            $agreement->save();

            // Automatically generate chronological repayment schedule
            $this->scheduleGenerator->generateSchedule($agreement);

            return $agreement;
        });
    }

    /**
     * Cancel agreement and release reserved hardware back to showroom stock.
     */
    public function cancel(InstallmentAgreement $agreement, User $user, string $reason): InstallmentAgreement
    {
        return DB::transaction(function () use ($agreement, $user, $reason) {
            if (in_array($agreement->status, ['completed', 'cancelled'])) {
                throw new DomainException("Agreement in status '{$agreement->status}' cannot be cancelled.");
            }

            // If unit was reserved but not yet disbursed, release it back to stock
            if ($agreement->serialized_item_id && $agreement->status !== 'active') {
                $item = SerializedItem::lockForUpdate()->find($agreement->serialized_item_id);
                if ($item && $item->status === 'reserved') {
                    $item->status = 'in_stock';
                    $item->save();

                    $inventory = BranchInventory::where('branch_id', $agreement->branch_id)
                        ->where('product_id', $agreement->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inventory) {
                        $inventory->quantity_reserved = max(0, $inventory->quantity_reserved - 1);
                        $inventory->recalculateAvailable();
                        $inventory->save();
                    }
                }
            }

            $agreement->status = 'cancelled';
            $agreement->cancelled_at = now();
            $agreement->cancellation_reason = $reason;
            $agreement->save();

            return $agreement;
        });
    }

    /**
     * Generate sequential account number: AGR-{BRANCH_CODE}-{YYYYMM}-{0001}
     */
    protected function generateAccountNumber(Branch $branch): string
    {
        $yearMonth = date('Ym');
        $branchCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $branch->code ?? 'BR01'));

        $count = InstallmentAgreement::where('company_id', $branch->company_id)
            ->where('branch_id', $branch->id)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count() + 1;

        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        $accountNumber = "AGR-{$branchCode}-{$yearMonth}-{$sequence}";

        // Guard against any collision
        while (InstallmentAgreement::where('company_id', $branch->company_id)->where('account_number', $accountNumber)->exists()) {
            $count++;
            $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
            $accountNumber = "AGR-{$branchCode}-{$yearMonth}-{$sequence}";
        }

        return $accountNumber;
    }

    /**
     * Terms & conditions snapshot text.
     */
    protected function buildTermsSnapshot(Customer $customer, Product $product, $pricingResult, InstallmentPlan $plan): string
    {
        return "AGREEMENT TERMS & CONDITIONS:\n" .
               "1. The Purchaser ({$customer->full_name}, CNIC: {$customer->cnic}) agrees to purchase {$product->brand} {$product->model_name} on installment basis.\n" .
               "2. Total Agreement Value: Rs. " . number_format($pricingResult->totalPayable) . ".\n" .
               "3. Required Down Payment: Rs. " . number_format($pricingResult->downPayment) . ".\n" .
               "4. Financed Balance: Rs. " . number_format($pricingResult->totalFinanced) . " payable in {$pricingResult->tenureMonths} equal monthly installments of Rs. " . number_format($pricingResult->installmentAmount) . " each.\n" .
               "5. Ownership and title of merchandise remains with the seller until all installments, markup, and applicable dues are fully satisfied.\n" .
               "6. The Purchaser guarantees that all submitted information, utility records, and guarantor undertakings are genuine.";
    }
}
