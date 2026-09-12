<?php

namespace App\Console\Commands;

use App\Services\Tenant\SubscriptionService;
use Illuminate\Console\Command;

class CheckSubscriptionsAndEnforceLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:check-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate tenant subscription statuses, trial expirations, grace periods, and auto-suspend delinquent companies.';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionService $subscriptionService): int
    {
        $this->info('Starting SaaS subscription lifecycle evaluation...');

        $result = $subscriptionService->processScheduledLifecycleChecks();

        $this->info('Evaluation complete:');
        $this->line("- Companies evaluated: {$result['checked']}");
        $this->line("- Statuses updated: {$result['updated']}");
        $this->line("- Transitions to past_due: {$result['past_due']}");
        $this->line("- Companies suspended: {$result['suspended']}");

        return Command::SUCCESS;
    }
}
