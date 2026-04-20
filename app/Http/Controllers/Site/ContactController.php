<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ContactRequest;
use App\Mail\ContactFormMessage;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('site.contact', [
            'email'     => SiteSetting::get('contact.email'),
            'address'   => SiteSetting::get('contact.physical_address'),
            'facebook'  => SiteSetting::get('contact.social.facebook'),
            'instagram' => SiteSetting::get('contact.social.instagram'),
            'whatsapp'  => SiteSetting::get('contact.social.whatsapp'),
        ]);
    }

    public function submit(ContactRequest $request): RedirectResponse
    {
        $recipient = SiteSetting::get('contact.email');

        // Fail loudly in the admin's logs if no inbox is configured yet,
        // but still show the visitor a polite success state so they don't
        // re-submit (we'd rather the committee fixes their settings).
        if (! $recipient) {
            Log::warning('Contact form submitted but contact.email is not configured.');

            return redirect()
                ->route('contact')
                ->with('contact.status', 'sent');
        }

        try {
            Mail::to($recipient)->send(new ContactFormMessage(
                senderName: $request->string('name')->toString(),
                senderEmail: $request->string('email')->toString(),
                senderSubject: $request->filled('subject') ? $request->string('subject')->toString() : null,
                messageBody: $request->string('message')->toString(),
                ipAddress: $request->ip(),
            ));
        } catch (\Throwable $e) {
            Log::error('Contact form send failed', [
                'error'      => $e->getMessage(),
                'ip'         => $request->ip(),
                'recipient'  => $recipient,
            ]);

            return back()
                ->withInput()
                ->with('contact.status', 'error');
        }

        return redirect()
            ->route('contact')
            ->with('contact.status', 'sent');
    }
}
