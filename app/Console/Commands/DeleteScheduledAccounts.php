<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Notifications\AccountDeletedNotification;

class DeleteScheduledAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:delete-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete users account after 7 days or more from request action';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::where('deletion_requested', true)
            ->where('scheduled_deletion_at', '<=', now())
            ->get();

        foreach ($users as $user) {
            $user->delete(); // This will soft delete
            $user->notify(new AccountDeletedNotification);
        }

        $this->info("Deleted " . count($users) . " user(s).");
    }
}
