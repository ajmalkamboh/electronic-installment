<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Company;
use App\Services\Recovery\LateFeeEngine;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessLateFeesAndDelinquency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recovery:assess-delinquency
                            {--company= : Specific company ID}
                            {--branch= : Specific branch ID}
                            {--date= : Custom evaluation date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active installment agreements, accrue late fees past grace period, and update delinquency recovery stages.';

    /**
     * Execute the console command.
     */
    public function handle(LateFeeEngine $lateFeeEngine): int
    {
        $this->info('Initiating daily installment delinquency assessment...');

        $companyId = $this->option('company');
        $branchId = $this->option('branch');
        $dateParam = $this->option('date');

        $asOfDate = $dateParam ? Carbon::parse($dateParam) : Carbon::today();

        $companiesQuery = Company::query();
        if ($companyId) {
            $companiesQuery->where('id', $companyId);
        }
        $companies = $companiesQuery->get();

        if ($companies->isEmpty()) {
            $this->warn('No companies found matching criteria.');
            return self::SUCCESS;
        }

        $branch = $branchId ? Branch::find($branchId) : null;

        $totalEvaluated = 0;
        $totalSchedules = 0;
        $totalPenalties = 0.00;
        $totalCases = 0;

        foreach ($companies as $company) {
            $this->line("Assessing Company: <comment>{$company->name}</comment> (ID: {$company->id}) as of {$asOfDate->toDateString()}");

            $stats = $lateFeeEngine->assessAllDelinquencies($company, $branch, $asOfDate);

            $totalEvaluated += $stats['agreements_evaluated'];
            $totalSchedules += $stats['schedules_updated'];
            $totalPenalties += $stats['total_penalties_accrued'];
            $totalCases += $stats['cases_synced'];

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Agreements Evaluated', $stats['agreements_evaluated']],
                    ['Overdue Schedules Updated', $stats['schedules_updated']],
                    ['Penalties Accrued (PKR)', number_format($stats['total_penalties_accrued'], 2)],
                    ['Recovery Cases Synced', $stats['cases_synced']],
                ]
            );
        }

        $this->info("Assessment Complete! Total Agreements: {$totalEvaluated}, Total Penalties: PKR " . number_format($totalPenalties, 2));

        return self::SUCCESS;
    }
}
