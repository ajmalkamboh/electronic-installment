<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\NotificationSetting;
use App\Models\NotificationTemplate;
use App\Models\Role;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected Customer $customer;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics Showroom',
            'slug' => 'kamboh-electronics-showroom',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Mall Road Showroom',
            'code' => 'MLR-01',
            'is_main' => true,
            'status' => 'active',
            'address' => 'Lahore',
            'city' => 'Lahore',
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
            'customer_number' => 'CUST-101',
            'full_name' => 'Tariq Mehmood',
            'cnic' => '35202-1234567-1',
            'mobile_primary' => '03001234567',
            'present_address' => 'Model Town, Lahore',
            'status' => 'verified',
        ]);

        $this->notificationService = app(NotificationService::class);
        $this->notificationService->provisionDefaultTemplates($this->company);
    }

    public function test_guest_is_redirected_from_notification_routes(): void
    {
        $response = $this->get(route('notifications.index'));
        $response->assertRedirect(route('login'));

        $response2 = $this->get(route('notifications.templates'));
        $response2->assertRedirect(route('login'));

        $response3 = $this->get(route('notifications.settings'));
        $response3->assertRedirect(route('login'));
    }

    public function test_outbox_index_page_loads_with_stats_and_table(): void
    {
        // Create sample notification log
        NotificationLog::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'channel' => 'sms',
            'recipient_phone' => '+923001234567',
            'recipient_name' => 'Tariq Mehmood',
            'template_code' => 'payment_receipt',
            'content' => 'Payment receipt for PKR 15,000 received.',
            'status' => 'delivered',
            'provider' => 'log',
            'provider_reference' => 'SIM-SMS-123456',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('tenant.notifications.index');
        $response->assertSee('SMS &amp; WhatsApp Communications Hub', false);
        $response->assertSee('Outbox Audit Log');
        $response->assertSee('Tariq Mehmood');
        $response->assertSee('+923001234567');
        $response->assertSee('Delivered');
    }

    public function test_templates_page_loads_with_default_templates(): void
    {
        $response = $this->actingAs($this->user)->get(route('notifications.templates'));

        $response->assertOk();
        $response->assertViewIs('tenant.notifications.templates');
        $response->assertSee('Automated Message Templates');
        $response->assertSee('welcome_agreement');
        $response->assertSee('payment_receipt');
        $response->assertSee('due_reminder');
        $response->assertSee('{customer_name}');
    }

    public function test_template_can_be_updated(): void
    {
        $template = NotificationTemplate::where('company_id', $this->company->id)
            ->where('code', 'payment_receipt')
            ->firstOrFail();

        $payload = [
            'name' => 'Customized Payment Receipt SMS',
            'channel' => 'both',
            'subject' => 'Payment Acknowledgment',
            'body' => 'Shukriya {customer_name}! Aap ki installment of PKR {amount} vasool ho chuki hai.',
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->user)->put(route('notifications.templates.update', $template), $payload);

        $response->assertRedirect(route('notifications.templates'));
        $response->assertSessionHas('success');

        $template->refresh();
        $this->assertEquals('Customized Payment Receipt SMS', $template->name);
        $this->assertStringContainsString('Shukriya {customer_name}', $template->body);
    }

    public function test_settings_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('notifications.settings'));

        $response->assertOk();
        $response->assertViewIs('tenant.notifications.settings');
        $response->assertSee('Gateway &amp; Automation Configuration', false);
        $response->assertSee('Active SMS Driver');
        $response->assertSee('WhatsApp Business Cloud API');
    }

    public function test_settings_can_be_updated(): void
    {
        $payload = [
            'sms_driver' => 'generic_http',
            'sms_sender_id' => 'KAMBOH-ELEC',
            'sms_endpoint_url' => 'https://api.smscountry.com/v1/send',
            'sms_api_key' => 'MY_SMS_KEY_123',
            'sms_api_secret' => 'MY_SMS_SECRET_456',
            'whatsapp_driver' => 'meta_cloud',
            'whatsapp_phone_number_id' => '1048291048123',
            'whatsapp_business_account_id' => '1928374829123',
            'whatsapp_access_token' => 'EAAB_TEST_TOKEN',
            'auto_receipt_sms' => '1',
            'auto_receipt_whatsapp' => '1',
            'auto_welcome_sms' => '1',
            'auto_reminder_days_before' => 5,
            'auto_overdue_sms' => '1',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->user)->post(route('notifications.settings.update'), $payload);

        $response->assertRedirect(route('notifications.settings'));
        $response->assertSessionHas('success');

        $setting = NotificationSetting::where('company_id', $this->company->id)->firstOrFail();
        $this->assertEquals('generic_http', $setting->sms_driver);
        $this->assertEquals('KAMBOH-ELEC', $setting->sms_sender_id);
        $this->assertEquals('meta_cloud', $setting->whatsapp_driver);
        $this->assertEquals(5, $setting->auto_reminder_days_before);
        $this->assertTrue($setting->auto_receipt_whatsapp);
    }

    public function test_custom_ad_hoc_notification_can_be_sent(): void
    {
        $payload = [
            'recipient_phone' => '03001234567',
            'recipient_name' => 'Tariq Mehmood',
            'channel' => 'sms',
            'message' => 'Dear customer, our showroom will remain open this Sunday for Eid installment clearance.',
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
        ];

        $response = $this->actingAs($this->user)->post(route('notifications.send'), $payload);

        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notification_logs', [
            'company_id' => $this->company->id,
            'recipient_phone' => '+923001234567',
            'recipient_name' => 'Tariq Mehmood',
            'channel' => 'sms',
            'status' => 'delivered',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_failed_notification_can_be_retried(): void
    {
        $log = NotificationLog::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'channel' => 'sms',
            'recipient_phone' => '+923001234567',
            'recipient_name' => 'Tariq Mehmood',
            'content' => 'Retry message test',
            'status' => 'failed',
            'error_message' => 'Simulated gateway timeout',
        ]);

        $response = $this->actingAs($this->user)->post(route('notifications.retry', $log));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $log->refresh();
        $this->assertEquals('delivered', $log->status);
        $this->assertNull($log->error_message);
    }

    public function test_reminders_console_command_executes_successfully(): void
    {
        $this->artisan('installments:send-reminders', ['--company' => $this->company->id])
            ->assertExitCode(0);
    }

    public function test_cross_tenant_isolation_denies_updating_other_company_template(): void
    {
        // Company B
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Rival Showroom',
            'slug' => 'rival-showroom',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->notificationService->provisionDefaultTemplates($otherCompany);
        $otherTemplate = NotificationTemplate::where('company_id', $otherCompany->id)->firstOrFail();

        // User from Company A attempts to update Company B's template
        $response = $this->actingAs($this->user)->put(route('notifications.templates.update', $otherTemplate), [
            'name' => 'Malicious update',
            'channel' => 'sms',
            'body' => 'Hacked',
            'is_active' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_cross_tenant_isolation_denies_retrying_other_company_log(): void
    {
        // Company B
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Rival Showroom',
            'slug' => 'rival-showroom-2',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $otherLog = NotificationLog::create([
            'company_id' => $otherCompany->id,
            'channel' => 'sms',
            'recipient_phone' => '+923009999999',
            'content' => 'Rival notification',
            'status' => 'failed',
        ]);

        // User from Company A attempts to retry Company B's log
        $response = $this->actingAs($this->user)->post(route('notifications.retry', $otherLog));

        $response->assertForbidden();
    }
}
