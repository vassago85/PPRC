<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::live()->paginate(10);

        return view('site.announcements.index', compact('announcements'));
    }

    public function show(string $slug)
    {
        $announcement = Announcement::live()->where('slug', $slug)->firstOrFail();

        return view('site.announcements.show', compact('announcement'));
    }
}
