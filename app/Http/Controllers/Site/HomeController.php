<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\HomepageSection;

class HomeController extends Controller
{
    public function __invoke()
    {
        $sections = HomepageSection::active()->get();
        $announcements = Announcement::live()->limit(3)->get();

        return view('site.home', compact('sections', 'announcements'));
    }
}
