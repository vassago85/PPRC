<?php

namespace App\Http\Controllers\Portal;

use App\Enums\EndorsementStatus;
use App\Http\Controllers\Controller;
use App\Models\EndorsementRequest;
use App\Models\SiteSetting;
use Illuminate\View\View;

class EndorsementLetterController extends Controller
{
    public function __invoke(string $token): View
    {
        $endorsement = EndorsementRequest::query()
            ->where('token', $token)
            ->where('status', EndorsementStatus::Approved->value)
            ->with('member')
            ->firstOrFail();

        return view('portal.documents.endorsement-letter', [
            'endorsement' => $endorsement,
            'member' => $endorsement->member,
            'clubAddress' => SiteSetting::get('contact.physical_address', 'Pretoria, Gauteng, South Africa'),
            'clubEmail' => SiteSetting::get('contact.email', 'info@pretoriaprc.co.za'),
        ]);
    }
}
