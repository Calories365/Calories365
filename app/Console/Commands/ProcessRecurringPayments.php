<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRecurringPaymentJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProcessRecurringPayments extends Command
{
    protected $signature = 'portmone:process-recurring';

    protected $description = 'Processes recurring payments for users whose subscription expires today';

    public function handle()
    {
        User::where('premium_until', '>=', Carbon::today()->startOfDay())
            ->where('premium_until', '<=', Carbon::today()->endOfDay())
            ->where('token', '!=', null)
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    ProcessRecurringPaymentJob::dispatch($user);
                }
            });

        $this->info('Recurring payments processed for users with subscriptions expiring today.');

        return CommandAlias::SUCCESS;
    }
}
