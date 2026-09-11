<?php

namespace App\Services\Payment;

use App\Models\Branch;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentSchedule;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentEngine
{
    public function __construct(
        protected ScheduleGenerator $scheduleGenerator
    ) {}

    /**
     * Process and allocate a payment against an agreement following the strict 4-tier waterfall:
     * 1. Unpaid Late Fees & Penalties
     * 2. Earliest Overdue Installments
     * 3. Current Due Installment
     * 4. Advance Future Installments
     */
    public function recordPayment(
        InstallmentAgreement $agreement,
        float $amount,
        string $paymentMethod,
        ?string $reference = null,
        ?User $cashier = null,
        ?string $notes = null
    ): Payment {
        if ($amount <= 0) {
            throw new DomainException("Payment amount must be greater than zero.");
        }

        if (!in_array($agreement->status, ['active', 'overdue', 'defaulted'])) {
            throw new DomainException("Cannot record payment on an agreement in '{$agreement->status}' status. Only active or overdue contracts can receive payments.");
        }

        return DB::transaction(function () use ($agreement, $amount, $paymentMethod, $reference, $cashier, $notes) {
            // Ensure schedules exist; if not, generate them
            if ($agreement->schedules()->count() === 0) {
                $this->scheduleGenerator->generateSchedule($agreement);
            }

            // Lock all unpaid or partially paid schedules in chronological order
            $schedules = InstallmentSchedule::where('installment_agreement_id', $agreement->id)
                ->where('status', '!=', 'paid')
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get();

            if ($schedules->isEmpty()) {
                throw new DomainException("Agreement has no outstanding installments to pay.");
            }

            $branch = $agreement->branch;
            $paymentNumber = $this->generatePaymentNumber($branch);

            $payment = Payment::create([
                'company_id' => $agreement->company_id,
                'branch_id' => $agreement->branch_id,
                'installment_agreement_id' => $agreement->id,
                'customer_id' => $agreement->customer_id,
                'cashier_id' => $cashier?->id ?? $agreement->creator_id,
                'payment_number' => $paymentNumber,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference_number' => $reference,
                'payment_date' => Carbon::today()->toDateString(),
                'status' => 'acknowledged',
                'late_fee_paid' => 0.00,
                'principal_paid' => 0.00,
                'markup_paid' => 0.00,
                'notes' => $notes,
            ]);

            $remainingFunds = $amount;
            $totalLateFeesPaid = 0.0;
            $totalPrincipalPaid = 0.0;
            $totalMarkupPaid = 0.0;

            // Tier 1: Unpaid Late Fees across all schedules
            foreach ($schedules as $schedule) {
                if ($remainingFunds <= 0) {
                    break;
                }

                if ((float) $schedule->late_fee_amount > 0) {
                    $feeDue = (float) $schedule->late_fee_amount;
                    $appliedFee = min($remainingFunds, $feeDue);

                    $schedule->late_fee_amount = round($feeDue - $appliedFee, 2);
                    $schedule->save();

                    $remainingFunds = round($remainingFunds - $appliedFee, 2);
                    $totalLateFeesPaid = round($totalLateFeesPaid + $appliedFee, 2);
                }
            }

            // Tier 2, 3, 4: Chronological sequence of installments (Overdue -> Due -> Advance)
            foreach ($schedules as $schedule) {
                if ($remainingFunds <= 0) {
                    break;
                }

                $balanceDue = (float) $schedule->remaining_balance;
                if ($balanceDue <= 0.001) {
                    continue;
                }

                $allocating = min($remainingFunds, $balanceDue);

                // Split proportionately into principal and markup
                $schedTotal = (float) $schedule->total_amount;
                $principalRatio = $schedTotal > 0 ? ((float) $schedule->principal_amount / $schedTotal) : 1.0;

                $appliedPrincipal = round($allocating * $principalRatio, 2);
                $appliedMarkup = round($allocating - $appliedPrincipal, 2);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'installment_schedule_id' => $schedule->id,
                    'amount_allocated' => $allocating,
                    'principal_component' => $appliedPrincipal,
                    'markup_component' => $appliedMarkup,
                    'late_fee_component' => 0.00,
                ]);

                $schedule->paid_amount = round($schedule->paid_amount + $allocating, 2);
                $schedule->remaining_balance = max(0.00, round($balanceDue - $allocating, 2));

                if ($schedule->remaining_balance <= 0.01) {
                    $schedule->remaining_balance = 0.00;
                    $schedule->status = 'paid';
                    $schedule->paid_at = now();
                } else {
                    $schedule->status = 'partially_paid';
                }
                $schedule->save();

                $totalPrincipalPaid = round($totalPrincipalPaid + $appliedPrincipal, 2);
                $totalMarkupPaid = round($totalMarkupPaid + $appliedMarkup, 2);
                $remainingFunds = round($remainingFunds - $allocating, 2);
            }

            // Update payment record breakdown
            $payment->late_fee_paid = $totalLateFeesPaid;
            $payment->principal_paid = $totalPrincipalPaid;
            $payment->markup_paid = $totalMarkupPaid;
            $payment->save();

            // Recalculate agreement balances
            $remainingAgreementBalance = (float) $agreement->schedules()->sum('remaining_balance');
            $paidCount = $agreement->schedules()->where('status', 'paid')->count();

            $agreement->remaining_balance = max(0.00, $remainingAgreementBalance);
            $agreement->paid_installments = $paidCount;

            // Auto-complete contract if all installments are fully satisfied
            if ($remainingAgreementBalance <= 0.01) {
                $agreement->remaining_balance = 0.00;
                $agreement->status = 'completed';
                $agreement->completed_at = now();
            }

            $agreement->save();

            return $payment->load('allocations.schedule');
        });
    }

    /**
     * Generate sequential payment receipt number: PAY-{BRANCH}-{YYYYMM}-{0001}
     */
    protected function generatePaymentNumber(Branch $branch): string
    {
        $yearMonth = date('Ym');
        $branchCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $branch->code ?? 'BR01'));

        $count = Payment::where('company_id', $branch->company_id)
            ->where('branch_id', $branch->id)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count() + 1;

        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        $paymentNumber = "PAY-{$branchCode}-{$yearMonth}-{$sequence}";

        while (Payment::where('company_id', $branch->company_id)->where('payment_number', $paymentNumber)->exists()) {
            $count++;
            $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
            $paymentNumber = "PAY-{$branchCode}-{$yearMonth}-{$sequence}";
        }

        return $paymentNumber;
    }
}
