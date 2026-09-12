<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\InstallmentSchedule;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\SerializedItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Notification\PhoneNumberNormalizer;
use App\Services\Payment\ScheduleGenerator;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected Customer $customer;
    protected Product $product;
    protected SerializedItem $item;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $agreement;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics Lahore',
            'slug' => 'kamboh-electronics-lahore',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Ferozepur Road Showroom',
            'code' => 'FZR-01',
            'is_main' => true,
            'status' => 'active',
            'address' => 'Lahore',
            'city' => 'Lahore',
            'phone' => '042-35800000',
        ]);

        $role = Role::where('name', 'company_admin')->firstOrFail();
        $this->user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Ajmal Kamboh',
            'email' => 'admin@kamboh.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'ulid' => (string) Str::ulid(),
            'customer_number' => 'CUST-001',
            'full_name' => 'Tariq Mehmood',
            'cnic' => '35202-1234567-1',
            'mobile_primary' => '03001234567',
            'present_address' => 'House 45, Lahore',
            'status' => 'verified',
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'LED Televisions',
            'slug' => 'led-televisions',
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung Electronics Pakistan',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'model_name' => 'CU7000',
            'brand' => 'Samsung',
            'base_cash_price' => 100000,
            'is_active' => true,
        ]);

        $this->item = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'supplier_id' => $supplier->id,
            'serial_number' => 'SAM-SN-998877',
            'status' => 'disbursed',
            'purchase_price' => 80000,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Showroom Plan',
            'tenure_months' => 12,
            'markup_rate_pct' => 20.0,
            'down_payment_pct' => 20.0,
            'advance_installments_count' => 0,
            'status' => 'active',
        ]);

        $this->agreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'serialized_item_id' => $this->item->id,
            'agreement_number' => 'AGR-FZR-2026-0001',
            'account_number' => 'ACC-FZR-2026-0001',
            'cash_price' => 100000,
            'down_payment_amount' => 20000,
            'down_payment_paid' => 20000,
            'financed_principal' => 80000,
            'markup_rate_pct' => 20.0,
            'markup_amount' => 16000,
            'total_financed' => 96000,
            'total_payable' => 116000,
            'installment_amount' => 8000,
            'monthly_installment_amount' => 8000,
            'tenure_months' => 12,
            'installment_frequency' => 'monthly',
            'total_installments' => 12,
            'paid_installments' => 0,
            'remaining_balance' => 96000,
            'total_paid' => 0,
            'status' => 'active',
            'start_date' => Carbon::today(),
            'first_due_date' => Carbon::today()->addDays(3),
            'maturity_date' => Carbon::today()->addMonths(12),
            'creator_id' => $this->user->id,
        ]);

        // Generate schedules
        $generator = app(ScheduleGenerator::class);
        $generator->generateSchedule($this->agreement);

        $this->notificationService = app(NotificationService::class);
        $this->notificationService->provisionDefaultTemplates($this->company);
    }

    public function test_phone_number_normalizer_handles_pakistani_formats(): void
    {
        // 11 digits local
        $this->assertEquals('923001234567', PhoneNumberNormalizer::toInternational('03001234567'));
        $this->assertEquals('+923001234567', PhoneNumberNormalizer::toE164('03001234567'));
        $this->assertEquals('03001234567', PhoneNumberNormalizer::toLocal('03001234567'));

        // Formatted with dashes
        $this->assertEquals('923001234567', PhoneNumberNormalizer::toInternational('0300-1234567'));
        $this->assertEquals('+923001234567', PhoneNumberNormalizer::toE164('0300-1234567'));

        // International with 0092
        $this->assertEquals('923001234567', PhoneNumberNormalizer::toInternational('00923001234567'));

        // Validate
        $this->assertTrue(PhoneNumberNormalizer::isValidPakistaniMobile('03001234567'));
        $this->assertTrue(PhoneNumberNormalizer::isValidPakistaniMobile('+923451234567'));
        $this->assertFalse(PhoneNumberNormalizer::isValidPakistaniMobile('02135800000')); // Landline
        $this->assertFalse(PhoneNumberNormalizer::isValidPakistaniMobile('12345')); // Invalid
    }

    public function test_carrier_detection_identifies_pakistani_networks(): void
    {
        $this->assertEquals('Jazz / Mobilink', PhoneNumberNormalizer::getCarrierName('03001234567'));
        $this->assertEquals('Telenor Pakistan', PhoneNumberNormalizer::getCarrierName('03451234567'));
        $this->assertEquals('Zong / CMPak', PhoneNumberNormalizer::getCarrierName('03121234567'));
        $this->assertEquals('Ufone (PTCL)', PhoneNumberNormalizer::getCarrierName('03331234567'));
        $this->assertEquals('Warid (Jazz)', PhoneNumberNormalizer::getCarrierName('03211234567'));
        $this->assertEquals('Special Communications Organization (SCO)', PhoneNumberNormalizer::getCarrierName('03551234567'));
    }

    public function test_default_templates_are_provisioned(): void
    {
        $count = NotificationTemplate::where('company_id', $this->company->id)->count();
        $this->assertEquals(count(NotificationService::$defaultTemplates), $count);

        $this->assertDatabaseHas('notification_templates', [
            'company_id' => $this->company->id,
            'code' => 'payment_receipt',
        ]);
        $this->assertDatabaseHas('notification_templates', [
            'company_id' => $this->company->id,
            'code' => 'due_reminder',
        ]);
        $this->assertDatabaseHas('notification_templates', [
            'company_id' => $this->company->id,
            'code' => 'overdue_alert',
        ]);
    }

    public function test_template_variable_parser_replaces_tokens(): void
    {
        $template = "Hello {customer_name}, your balance is PKR {remaining_balance} at {company_name}.";
        $data = [
            'customer_name' => 'Tariq Mehmood',
            'remaining_balance' => '75,000.00',
            'company_name' => 'Kamboh Electronics',
        ];

        $rendered = $this->notificationService->parseVariables($template, $data);
        $this->assertEquals("Hello Tariq Mehmood, your balance is PKR 75,000.00 at Kamboh Electronics.", $rendered);
    }

    public function test_send_notification_logs_and_delivers_via_log_driver(): void
    {
        $log = $this->notificationService->send(
            $this->company,
            'sms',
            '03001234567',
            'Test message to Tariq Mehmood',
            'custom_test',
            'Tariq Mehmood',
            $this->branch->id,
            $this->customer->id,
            $this->agreement->id,
            null,
            $this->user->id
        );

        $this->assertInstanceOf(NotificationLog::class, $log);
        $this->assertEquals('delivered', $log->status);
        $this->assertStringStartsWith('SIM-SMS-', $log->provider_reference);
        $this->assertEquals('+923001234567', $log->recipient_phone);
        $this->assertNotNull($log->sent_at);

        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'status' => 'delivered',
            'template_code' => 'custom_test',
        ]);
    }

    public function test_send_payment_receipt_notification(): void
    {
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'customer_id' => $this->customer->id,
            'payment_number' => 'PAY-202609-0001',
            'receipt_number' => 'RCP-FZR-0001',
            'payment_date' => Carbon::today(),
            'amount' => 10000,
            'payment_method' => 'cash',
            'status' => 'posted',
            'cashier_id' => $this->user->id,
        ]);

        $logs = $this->notificationService->sendPaymentReceipt($payment, ['sms']);

        $this->assertCount(1, $logs);
        $log = $logs[0];

        $this->assertEquals('delivered', $log->status);
        $this->assertEquals('payment_receipt', $log->template_code);
        $this->assertStringContainsString('PKR 10,000.00', $log->content);
        $this->assertStringContainsString('PAY-202609-0001', $log->content);
    }

    public function test_send_due_reminder_prevents_duplicate_on_same_day(): void
    {
        $schedule = $this->agreement->schedules()->firstOrFail();

        // First dispatch should succeed
        $log1 = $this->notificationService->sendDueReminder($schedule);
        $this->assertNotNull($log1);
        $this->assertEquals('delivered', $log1->status);

        // Immediate second dispatch on same day should be skipped to prevent customer spam
        $log2 = $this->notificationService->sendDueReminder($schedule);
        $this->assertNull($log2);
    }

    public function test_retry_notification_reprocesses_failed_log(): void
    {
        $log = NotificationLog::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'installment_agreement_id' => $this->agreement->id,
            'channel' => 'sms',
            'recipient_phone' => '+923001234567',
            'recipient_name' => 'Tariq Mehmood',
            'content' => 'Retry testing message',
            'status' => 'failed',
            'error_message' => 'Simulated network timeout',
        ]);

        $retried = $this->notificationService->retryNotification($log);

        $this->assertEquals('delivered', $retried->status);
        $this->assertNull($retried->error_message);
        $this->assertNotNull($retried->sent_at);
    }
}
