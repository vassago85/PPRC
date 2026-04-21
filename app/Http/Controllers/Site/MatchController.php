<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;

class MatchController extends Controller
{
    public function index()
    {
        $upcoming = Event::query()
            ->with('matchFormat')
            ->upcoming()
            ->limit(30)
            ->get()
            ->map(fn (Event $e) => $this->toCard($e));

        $past = Event::query()
            ->with('matchFormat')
            ->published()
            ->where('start_date', '<', now()->toDateString())
            ->orderByDesc('start_date')
            ->limit(20)
            ->get()
            ->map(fn (Event $e) => $this->toCard($e));

        return view('site.matches.index', [
            'upcoming' => $upcoming,
            'past' => $past,
        ]);
    }

    public function show(Event $event)
    {
        abort_unless($event->isPubliclyVisible(), 404);

        $event->loadCount('registrations');
        $event->load(['matchFormat', 'results', 'galleryPhotos']);

        return view('site.matches.show', [
            'event' => $event,
            'results' => $event->results()
                ->orderBy('rank')
                ->orderBy('shooter_name')
                ->get(),
            'resultsPublished' => $event->results_published_at !== null,
        ]);
    }

    private function toCard(Event $e): array
    {
        return [
            'title' => $e->title,
            'starts_at' => $e->start_date,
            'location' => $e->location_name,
            'format' => $e->matchFormat?->short_name ?? $e->matchFormat?->name,
            'banner_url' => $e->bannerUrl(),
            'url' => route('matches.show', ['event' => $e->slug]),
        ];
    }
}
