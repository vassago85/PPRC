<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipationLetterController extends Controller
{
    public function __invoke(Request $request): View
    {
        $member = $request->user()->member;
        abort_unless($member, 403);

        $registrations = EventRegistration::query()
            ->where('member_id', $member->id)
            ->where('status', '!=', 'cancelled')
            ->whereHas('event')
            ->with(['event' => fn ($q) => $q->withTrashed()->orderBy('start_date')])
            ->get()
            ->sortBy(fn ($r) => $r->event?->start_date);

        return view('portal.documents.participation-letter', [
            'member' => $member,
            'registrations' => $registrations,
            'clubAddress' => SiteSetting::get('contact.physical_address', 'Pretoria, Gauteng, South Africa'),
            'clubEmail' => SiteSetting::get('contact.email', 'info@pretoriaprc.co.za'),
        ]);
    }
}
