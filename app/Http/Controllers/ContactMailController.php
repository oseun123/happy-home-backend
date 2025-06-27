<?php

namespace App\Http\Controllers;

use App\Models\ContactMail;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ContactFormNotification;
use App\Notifications\ContactAutoReplyNotification;

class ContactMailController extends Controller
{
    public function submit(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        // Save to database
        $contact = ContactMail::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'message' => $request->message,
        ]);

        // Notify Admin
        Notification::route('mail', config('mail.contact_receiver'))
            ->notify(new ContactFormNotification($contact));

        // Notify User (Auto-reply)
        Notification::route('mail', $contact->email)
            ->notify(new ContactAutoReplyNotification($contact));

        return ResponseHelper::withSuccess('Thank you for contacting us!');
    }
}
