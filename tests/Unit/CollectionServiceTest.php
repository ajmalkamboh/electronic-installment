<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\CollectionAssignment;
use App\Models\CollectionLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Collection\CollectionService;
use App\Services\Payment\PaymentEngine;
use App\Services\Payment\ScheduleGenerator;
use Database\Seeders\RoleAndPermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $manager;
    protected User $officer;
    protected Customer $customer;
    protected Product $product;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $agreement;
    protected CollectionService $collectionService;
    protected PaymentEngine $paymentEngine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics',
            'slug' => 'kamboh-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Main Showroom',
            'code' => 'MSH-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $managerRole = Role::where('name', 'branch_manager')->first() ?? Role::first();
        $this->manager = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $managerRole->id,
            'role' => 'branch_manager',
            'name' => 'Manager Tariq',
            'email' => 'tariq@kamboh.pk',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $officerRole = Role::where('name', 'collection_officer')->first() ?? Role::first();
        $this->officer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $officerRole->id,
            'role' => 'collection_officer',
            'name' => 'Recovery Officer Imran',
            'email' => 'imran@kamboh.pk',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35201-1112233-4',
            'full_name' => 'Sohail Akhtar',
            'father_or_husband_name' => 'Akhtar Ali',
            'gender' => 'male',
            'mobile_primary' => '03011234567',
            'present_address' => 'Gulberg III, Lahore',
            'permanent_address' => 'Gulberg III, Lahore',
            'status' => 'active',
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Home Appliances',
            'slug' => 'home-appliances',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Waves Singer Pakistan',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'Waves',
            'model_name' => 'Deep Freezer 350L',
            'sku' => 'WAV-DF-350',
            'base_cash_price' => 120000,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => true,
            'is_active' => true,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '6 Months Standard',
            'slug' => '6-months-standard',
            'tenure_months' => 6,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 20.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->agreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->manager->id,
            'account_number' => 'AGR-TEST-001',
            'cash_price' => 120000,
            'down_payment_amount' => 24000,
            'down_payment_paid' => 24000,
            'down_payment_satisfied' => true,
            'financed_principal' => 96000,
            'markup_rate_pct' => 20.00,
            'markup_amount' => 9600,
            'total_financed' => 105600,
            'total_payable' => 129600,
            'remaining_balance' => 105600,
            'tenure_months' => 6,
            'total_installments' => 6,
            'paid_installments' => 0,
            'installment_amount' => 17600,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'maturity_date' => now()->addMonths(6)->toDateString(),
            'status' => 'active',
        ]);

        (new ScheduleGenerator())->generateSchedule($this->agreement);

        $this->collectionService = new CollectionService();
        $this->paymentEngine = new PaymentEngine(new ScheduleGenerator());
    }

    public function test_can_assign_agreement_to_officer(): void
    {
        $assignment = $this->collectionService->assignAgreement(
            agreement: $this->agreement,
            officer: $this->officer,
            assignedBy: $this->manager,
            priority: 'urgent',
            notes: 'Customer phone unreachable; visit residence.'
        );

        $this->assertInstanceOf(CollectionAssignment::class, $assignment);
        $this->assertEquals($this->officer->id, $assignment->collection_officer_id);
        $this->assertEquals('active', $assignment->status);
        $this->assertEquals('urgent', $assignment->priority);
        $this->assertEquals('Customer phone unreachable; visit residence.', $assignment->notes);
    }

    public function test_reassigning_deactivates_previous_assignment(): void
    {
        $firstAssignment = $this->collectionService->assignAgreement(
            agreement: $this->agreement,
            officer: $this->officer,
            assignedBy: $this->manager,
            priority: 'normal'
        );

        $anotherOfficer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $this->officer->role_id,
            'role' => 'collection_officer',
            'name' => 'Recovery Officer Babar',
            'email' => 'babar@kamboh.pk',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $secondAssignment = $this->collectionService->assignAgreement(
            agreement: $this->agreement,
            officer: $anotherOfficer,
            assignedBy: $this->manager,
            priority: 'high'
        );

        $firstAssignment->refresh();
        $this->assertEquals('reassigned', $firstAssignment->status);
        $this->assertEquals('active', $secondAssignment->status);
        $this->assertEquals($anotherOfficer->id, $secondAssignment->collection_officer_id);
    }

    public function test_cannot_assign_completed_agreement(): void
    {
        $this->agreement->update(['status' => 'completed']);

        $this->expectException(DomainException::class);
        $this->collectionService->assignAgreement(
            agreement: $this->agreement,
            officer: $this->officer,
            assignedBy: $this->manager
        );
    }

    public function test_can_log_customer_interaction_and_ptp(): void
    {
        $ptpDate = now()->addDays(5)->toDateString();

        $log = $this->collectionService->logInteraction(
            agreement: $this->agreement,
            officer: $this->officer,
            data: [
                'interaction_type' => 'field_visit',
                'interaction_status' => 'promise_to_pay',
                'promise_to_pay_date' => $ptpDate,
                'promised_amount' => 17600,
                'location_notes' => 'Met customer at residence. Committed to pay next week after salary disbursement.',
                'notes' => 'Guarantor also present.',
            ]
        );

        $this->assertInstanceOf(CollectionLog::class, $log);
        $this->assertEquals('field_visit', $log->interaction_type);
        $this->assertEquals('promise_to_pay', $log->interaction_status);
        $this->assertEquals('pending', $log->ptp_status);
        $this->assertEquals(17600, (float) $log->promised_amount);
        $this->assertTrue($log->isPtp());
    }

    public function test_field_cash_collection_is_in_submitted_status(): void
    {
        $payment = $this->paymentEngine->recordFieldCollection(
            agreement: $this->agreement,
            amount: 17600,
            collector: $this->officer,
            reference: 'FIELD-REC-001',
            notes: 'Collected cash during field visit'
        );

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('submitted', $payment->status);
        $this->assertEquals($this->officer->id, $payment->collector_id);
        $this->assertNull($payment->cashier_id);
        $this->assertEquals(17600, (float) $payment->amount);
    }

    public function test_cashier_handover_acknowledges_payment(): void
    {
        // 1. Officer records field collection
        $payment = $this->paymentEngine->recordFieldCollection(
            agreement: $this->agreement,
            amount: 17600,
            collector: $this->officer,
            reference: 'FIELD-REC-002',
            notes: 'Field cash collection'
        );

        // 2. Cashier receives cash at branch drawer and acknowledges
        $acknowledgedPayment = $this->paymentEngine->acknowledgeFieldHandover(
            payment: $payment,
            cashier: $this->manager,
            notes: 'Received cash in drawer at EOD'
        );

        $this->assertEquals('acknowledged', $acknowledgedPayment->status);
        $this->assertEquals($this->manager->id, $acknowledgedPayment->cashier_id);
        $this->assertEquals($this->officer->id, $acknowledgedPayment->collector_id);
        $this->assertStringContainsString('Received cash in drawer at EOD', $acknowledgedPayment->notes);
    }
}
