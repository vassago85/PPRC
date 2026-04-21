<?php

namespace App\Http\Controllers\Site;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function show(string $token): View
    {
        $membership = Membership::query()
            ->where('certificate_token', $token)
            ->with(['member', 'membershipType'])
            ->firstOrFail();

        abort_unless(
            $membership->status === MembershipStatus::Active,
            404
        );

        return view('site.membership-certificate', [
            'membership' => $membership,
        ]);
    }
}
