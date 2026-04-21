<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

/**
 * /membership — short public page that points prospective members at the
 * portal. All tier pricing and application workflow lives behind auth in the
 * portal, so this view is intentionally static.
 */
class MembershipController extends Controller
{
    public function __invoke()
    {
        return view('site.membership');
    }
}
