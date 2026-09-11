<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCreditProfile;
use App\Models\Guarantor;
use App\Models\PersonalReference;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of registered customers / debtors.
     */
    public function index(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $query = Customer::where('company_id', $company->id)
            ->with(['creditProfile', 'guarantors']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('cnic', 'like', "%{$search}%")
                    ->orWhere('mobile_primary', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('utility_bill_ref_number', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Residence Type Filter
        if ($residenceType = $request->input('residence_type')) {
            $query->where('residence_type', $residenceType);
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Metrics for summary cards
        $totalCustomers = Customer::where('company_id', $company->id)->count();
        $activeCustomers = Customer::where('company_id', $company->id)->where('status', 'active')->count();
        $pendingVerification = Customer::where('company_id', $company->id)->where('status', 'pending_verification')->count();
        $blacklisted = Customer::where('company_id', $company->id)->where('status', 'blacklisted')->count();

        return view('tenant.customers.index', compact(
            'customers',
            'totalCustomers',
            'activeCustomers',
            'pendingVerification',
            'blacklisted'
        ));
    }

    /**
     * Show the customer registration form.
     */
    public function create(): View
    {
        return view('tenant.customers.create');
    }

    /**
     * Store a newly registered customer, initial credit profile, guarantor, and reference.
     */
    public function store(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            // Customer Details
            'cnic' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers')->where(fn ($q) => $q->where('company_id', $company->id)),
            ],
            'full_name' => ['required', 'string', 'max:191'],
            'father_or_husband_name' => ['nullable', 'string', 'max:191'],
            'gender' => ['required', 'in:male,female,other'],
            'mobile_primary' => ['required', 'string', 'max:25'],
            'mobile_secondary' => ['nullable', 'string', 'max:25'],
            'whatsapp_number' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:191'],
            'present_address' => ['required', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'residence_type' => ['required', 'in:owned,rented,family'],
            'residence_tenure_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'monthly_household_income' => ['nullable', 'numeric', 'min:0'],
            'utility_bill_ref_number' => ['nullable', 'string', 'max:50'],

            // Legal Guarantor 1 (Mandatory per BR-GUAR-01)
            'guarantor_name' => ['required', 'string', 'max:191'],
            'guarantor_cnic' => ['required', 'string', 'max:20'],
            'guarantor_relationship' => ['required', 'string', 'max:50'],
            'guarantor_mobile' => ['required', 'string', 'max:25'],
            'guarantor_address' => ['required', 'string'],
            'guarantor_occupation' => ['nullable', 'string', 'max:100'],
            'guarantor_employer_name' => ['nullable', 'string', 'max:191'],
            'guarantor_monthly_income' => ['nullable', 'numeric', 'min:0'],

            // Personal Reference (Optional)
            'ref_name' => ['nullable', 'string', 'max:191'],
            'ref_relationship' => ['nullable', 'string', 'max:50'],
            'ref_mobile' => ['nullable', 'string', 'max:25'],
            'ref_address' => ['nullable', 'string'],
        ]);

        $customer = DB::transaction(function () use ($company, $validated) {
            // 1. Create Customer
            $customer = Customer::create([
                'company_id' => $company->id,
                'cnic' => $validated['cnic'],
                'full_name' => $validated['full_name'],
                'father_or_husband_name' => $validated['father_or_husband_name'] ?? null,
                'gender' => $validated['gender'],
                'mobile_primary' => $validated['mobile_primary'],
                'mobile_secondary' => $validated['mobile_secondary'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'present_address' => $validated['present_address'],
                'permanent_address' => $validated['permanent_address'] ?? null,
                'residence_type' => $validated['residence_type'],
                'residence_tenure_years' => $validated['residence_tenure_years'] ?? null,
                'monthly_household_income' => $validated['monthly_household_income'] ?? null,
                'utility_bill_ref_number' => $validated['utility_bill_ref_number'] ?? null,
                'status' => 'pending_verification',
            ]);

            // 2. Initialize Customer Credit Profile
            CustomerCreditProfile::create([
                'customer_id' => $customer->id,
                'company_id' => $company->id,
                'credit_score' => 50,
                'max_authorized_credit' => 150000.00,
                'active_agreements_count' => 0,
                'completed_agreements_count' => 0,
                'total_dpd_days' => 0,
            ]);

            // 3. Create Primary Guarantor
            Guarantor::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'cnic' => $validated['guarantor_cnic'],
                'full_name' => $validated['guarantor_name'],
                'relationship' => $validated['guarantor_relationship'],
                'mobile' => $validated['guarantor_mobile'],
                'address' => $validated['guarantor_address'],
                'occupation' => $validated['guarantor_occupation'] ?? null,
                'employer_name' => $validated['guarantor_employer_name'] ?? null,
                'monthly_income' => $validated['guarantor_monthly_income'] ?? null,
                'is_verified' => false,
            ]);

            // 4. Create Personal Reference (if provided)
            if (!empty($validated['ref_name']) && !empty($validated['ref_mobile'])) {
                PersonalReference::create([
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'full_name' => $validated['ref_name'],
                    'relationship' => $validated['ref_relationship'] ?? 'Acquaintance',
                    'mobile' => $validated['ref_mobile'],
                    'address' => $validated['ref_address'] ?? null,
                ]);
            }

            return $customer;
        });

        return redirect()->route('customers.show', $customer)
            ->with('success', "Customer '{$customer->full_name}' registered successfully with primary guarantor.");
    }

    /**
     * Display the specified customer's executive underwriting dossier.
     */
    public function show(Customer $customer): View
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $customer->company_id !== (int) $company->id) {
            abort(404, 'Customer record not found.');
        }

        $customer->load([
            'creditProfile',
            'guarantors',
            'references',
            'verifications.verifiedBy',
        ]);

        return view('tenant.customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the customer profile.
     */
    public function edit(Customer $customer): View
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $customer->company_id !== (int) $company->id) {
            abort(404, 'Customer record not found.');
        }

        return view('tenant.customers.edit', compact('customer'));
    }

    /**
     * Update the customer profile in storage.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $customer->company_id !== (int) $company->id) {
            abort(404, 'Customer record not found.');
        }

        $validated = $request->validate([
            'cnic' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers')->where(fn ($q) => $q->where('company_id', $company->id))->ignore($customer->id),
            ],
            'full_name' => ['required', 'string', 'max:191'],
            'father_or_husband_name' => ['nullable', 'string', 'max:191'],
            'gender' => ['required', 'in:male,female,other'],
            'mobile_primary' => ['required', 'string', 'max:25'],
            'mobile_secondary' => ['nullable', 'string', 'max:25'],
            'whatsapp_number' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:191'],
            'present_address' => ['required', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'residence_type' => ['required', 'in:owned,rented,family'],
            'residence_tenure_years' => ['nullable', 'integer', 'min:0'],
            'monthly_household_income' => ['nullable', 'numeric', 'min:0'],
            'utility_bill_ref_number' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:pending_verification,active,restricted,blacklisted'],
        ]);

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', "Customer dossier for '{$customer->full_name}' updated successfully.");
    }

    /**
     * Toggle or update customer status (e.g. Blacklist / Re-activate).
     */
    public function toggleStatus(Request $request, Customer $customer): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $customer->company_id !== (int) $company->id) {
            abort(404, 'Customer record not found.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending_verification,active,restricted,blacklisted'],
            'blacklisted_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $customer->update(['status' => $validated['status']]);

        if ($validated['status'] === 'blacklisted') {
            $customer->creditProfile?->update([
                'blacklisted_at' => now(),
                'blacklisted_reason' => $validated['blacklisted_reason'] ?? 'Delinquency or security blacklist flagged by administrator.',
                'credit_score' => 0,
            ]);
        } elseif ($customer->creditProfile && $customer->creditProfile->isBlacklisted()) {
            $customer->creditProfile->update([
                'blacklisted_at' => null,
                'blacklisted_reason' => null,
                'credit_score' => 50,
            ]);
        }

        return back()->with('success', "Customer status updated to '{$validated['status']}'.");
    }
}
