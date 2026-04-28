<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;

class GalleryController extends Controller
{
    public function index()
    {
        $events = Event::query()
            ->published()
            ->whereHas('galleryPhotos')
            ->with(['galleryPhotos' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->orderByDesc('start_date')
            ->get();

        return view('site.gallery.index', [
            'events' => $events,
        ]);
    }

    public function show(Event $event)
    {
        abort_unless($event->isPubliclyVisible(), 404);

        $event->load(['galleryPhotos' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')]);

        return view('site.gallery.show', [
            'event' => $event,
        ]);
    }
}
