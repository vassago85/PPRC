<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ExcoMember;

/**
 * /about — static narrative (club story + vision) plus the live committee
 * roster pulled from ExcoMember. Replaces the old CMS-backed "pages" table
 * so we stop pretending to be WordPress.
 */
class AboutController extends Controller
{
    public function __invoke()
    {
        $committee = ExcoMember::current()->get();

        return view('site.about', [
            'committee' => $committee,
        ]);
    }
}
