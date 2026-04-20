<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class HomeController extends Controller
{
    public function __invoke()
    {
        // Homepage is hand-built (not CMS-driven) so the visible copy stays
        // aligned with pretoriaprc.co.za. Dynamic data we pass through:
        //   - upcomingMatches: reserved for when the Event model ships
        //   - recentResults:   reserved for when the Result model ships
        //   - announcements:   already live, shown below the fold if any exist
        return view('site.home', [
            'upcomingMatches' => collect(),
            'recentResults' => collect(),
            'announcements' => Announcement::live()->limit(3)->get(),
        ]);
    }
}
