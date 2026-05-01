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

        // Group all squadded entries by squad number for the public squad
        // list. Unsquadded entries collapse into an "Unassigned" bucket so
        // shooters can still see who has signed up. Order within a squad
        // follows firing_order if it's set, then name.
        $squads = $event->registrations()
            ->with('member:id,first_name,last_name,membership_number')
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderByRaw('squad_number IS NULL, squad_number')
            ->orderByRaw('firing_order IS NULL, firing_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($r) => $r->squad_number);

        return view('site.matches.show', [
            'event' => $event,
            'squads' => $squads,
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
