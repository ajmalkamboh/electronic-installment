<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\InstallmentSchedule;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendInstallmentNotificationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:send-reminders {--company= : Specific company ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch automated SMS/WhatsApp reminders for upcoming and overdue installments';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $this->info('Starting automated installment notification reminders scan...');

        $companiesQuery = Company::where('status', 'active');
        if ($companyId = $this->option('company')) {
            $companiesQuery->where('id', $companyId);
        }

        $companies = $companiesQuery->get();
        $totalUpcomingSent = 0;
        $totalOverdueSent = 0;

        foreach ($companies as $company) {
            $setting = $notificationService->getSetting($company);

            if (! $setting->is_active) {
                $this->line("Skipping [{$company->name}]: Notifications disabled in settings.");
                continue;
            }

            $daysBefore = $setting->auto_reminder_days_before ?? 3;
            $targetDueDate = Carbon::today()->addDays($daysBefore)->toDateString();

            // 1. Upcoming installments due exactly in $daysBefore days
            $upcomingSchedules = InstallmentSchedule::whereHas('agreement', function ($q) use ($company) {
                $q->where('company_id', $company->id)->where('status', 'active');
            })
                ->whereIn('status', ['pending', 'partially_paid'])
                ->whereDate('due_date', $targetDueDate)
                ->with(['agreement.customer', 'agreement.product', 'agreement.branch'])
                ->get();

            foreach ($upcomingSchedules as $schedule) {
                $log = $notificationService->sendDueReminder($schedule);
                if ($log && $log->status === 'delivered') {
                    $totalUpcomingSent++;
                }
            }

            // 2. Overdue installments (e.g. 1, 7, or 15 days overdue)
            if ($setting->auto_overdue_sms) {
                $overdueSchedules = InstallmentSchedule::whereHas('agreement', function ($q) use ($company) {
                    $q->where('company_id', $company->id)->where('status', 'active');
                })
                    ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                    ->whereDate('due_date', '<', Carbon::today())
                    ->with(['agreement.customer', 'agreement.product', 'agreement.branch'])
                    ->get();

                foreach ($overdueSchedules as $schedule) {
                    $daysLate = (int) Carbon::parse($schedule->due_date)->diffInDays(Carbon::today(), false);
                    // Trigger alerts at milestone intervals: 1st day late, 7th day late, 15th day late, 30th day late
                    if (in_array($daysLate, [1, 7, 15, 30])) {
                        $log = $notificationService->sendOverdueAlert($schedule);
                        if ($log && $log->status === 'delivered') {
                            $totalOverdueSent++;
                        }
                    }
                }
            }
        }

        $this->info("Completed reminders scan: {$totalUpcomingSent} upcoming reminders sent, {$totalOverdueSent} overdue alerts sent.");

        return Command::SUCCESS;
    }
}
