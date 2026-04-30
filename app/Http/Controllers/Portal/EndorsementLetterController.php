<?php

namespace App\Http\Controllers\Portal;

use App\Enums\EndorsementStatus;
use App\Http\Controllers\Controller;
use App\Models\EndorsementRequest;
use App\Models\SiteSetting;
use Illuminate\View\View;

class EndorsementLetterController extends Controller
{
    /**
     * Public/portal letter — token-based, only valid for approved requests.
     */
    public function __invoke(string $token): View
    {
        $endorsement = EndorsementRequest::query()
            ->where('token', $token)
            ->where('status', EndorsementStatus::Approved->value)
            ->with('member')
            ->firstOrFail();

        return $this->render($endorsement, isPreview: false);
    }

    /**
     * Admin-only preview — renders the letter for ANY request (incl. pending),
     * watermarked as a draft so it can never be confused with the issued doc.
     */
    public function preview(EndorsementRequest $endorsement): View
    {
        abort_unless(auth()->user()?->can('members.view'), 403);

        $endorsement->load('member');

        return $this->render($endorsement, isPreview: true);
    }

    protected function render(EndorsementRequest $endorsement, bool $isPreview): View
    {
        return view('portal.documents.endorsement-letter', [
            'endorsement' => $endorsement,
            'member' => $endorsement->member,
            'clubAddress' => SiteSetting::get('contact.physical_address', 'Pretoria, Gauteng, South Africa'),
            'clubEmail' => SiteSetting::get('contact.email', 'info@pretoriaprc.co.za'),
            'isPreview' => $isPreview,
        ]);
    }
}
