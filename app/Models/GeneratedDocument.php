<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'installment_agreement_id',
        'customer_id',
        'document_number',
        'document_type',
        'title',
        'generated_by_id',
        'parameters',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(InstallmentAgreement::class, 'installment_agreement_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->document_type) {
            'application_form' => '<span class="badge bg-secondary"><i class="bi bi-file-earmark-person me-1"></i>Application Form</span>',
            'verification_report' => '<span class="badge bg-info text-dark"><i class="bi bi-check2-circle me-1"></i>Verification Report</span>',
            'credit_approval_sheet' => '<span class="badge bg-primary"><i class="bi bi-award me-1"></i>Credit Approval Sheet</span>',
            'installment_contract' => '<span class="badge bg-dark text-warning"><i class="bi bi-file-earmark-ruled me-1"></i>Legal Contract</span>',
            'guarantor_affidavit' => '<span class="badge bg-secondary-subtle text-dark"><i class="bi bi-pen me-1"></i>Guarantor Affidavit</span>',
            'delivery_note' => '<span class="badge bg-success"><i class="bi bi-box-seam me-1"></i>Delivery & Handover Note</span>',
            'payment_receipt' => '<span class="badge bg-warning text-dark"><i class="bi bi-receipt me-1"></i>Payment Receipt</span>',
            'account_statement' => '<span class="badge bg-info"><i class="bi bi-journal-text me-1"></i>Statement of Account</span>',
            'settlement_letter' => '<span class="badge bg-danger"><i class="bi bi-calculator me-1"></i>Settlement Letter</span>',
            'clearance_noc' => '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-patch-check-fill me-1"></i>Clearance NOC</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst(str_replace('_', ' ', $this->document_type)) . '</span>',
        };
    }
}
