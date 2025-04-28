<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Notifications\AccountDeletionScheduledNotification;

class AccountController extends Controller
{
    public function requestAccountDeletion()
    {
        $user = auth()->user();

        if ($user->deletion_requested) {
            return  ResponseHelper::withError('Deletion already scheduled.');
        }

        $user->update([
            'deletion_requested' => true,
            'scheduled_deletion_at' => now()->addDays(7),
        ]);

        $user->notify(new AccountDeletionScheduledNotification);

        return ResponseHelper::withSuccess('Your account will be permanently deleted in 7 days.');
    }


    public function cancelAccountDeletion()
    {
        $user = auth()->user();

        if (!$user->deletion_requested) {
            return ResponseHelper::withError('No deletion scheduled.');
        }

        $user->update(['deletion_requested' => false, 'scheduled_deletion_at' => null,]);

        return ResponseHelper::withSuccess('Account deletion cancelled.');
    }
}
