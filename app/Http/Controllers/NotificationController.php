<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;


class NotificationController extends Controller
{

    public function latestNotifications()
    {
        $user = auth()->user();

        $notifications = $user->unreadNotifications()->latest()->take(5)->get();
        return ResponseHelper::withSuccess('Notifications fetched succesfully.', $notifications);
    }

    public function markAsRead($id)
    {
        $user = auth()->user();
        $notification = $user->unreadNotifications()->findOrFail($id);
        $notification->markAsRead();
        return ResponseHelper::withSuccess('Notification marked as read.');
    }

    public function clearNotifications(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'string|exists:notifications,id',
        ]);

        $user = auth()->user();
        // dd($user);
        $notifications = $user->unreadNotifications()
            ->whereIn('id', $request->notification_ids)
            ->get();

        if (!$notifications) {

            return ResponseHelper::withError('Invalid notifications');
        }

        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }



        return ResponseHelper::withSuccess('Notifications marked as read successfully');
    }


    public function markAllAsRead()
    {
        $user = auth()->user();

        $user->unreadNotifications->markAsRead();



        return ResponseHelper::withSuccess('All notifications marked as read.');
    }
}
