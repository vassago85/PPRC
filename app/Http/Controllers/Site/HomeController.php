<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Event;

class HomeController extends Controller
{
    public function __invoke()
    {
        $upcomingMatches = Event::query()
            ->with('matchFormat')
            ->upcoming()
            ->limit(3)
            ->get()
            ->map(fn (Event $e) => [
                'title' => $e->title,
                'starts_at' => $e->start_date,
                'location' => $e->location_name,
                'format' => $e->matchFormat?->short_name ?? $e->matchFormat?->name,
                'banner_url' => $e->bannerUrl(),
                'url' => route('matches.show', ['event' => $e->slug]),
            ]);

        $recentResults = Event::query()
            ->with(['matchFormat', 'results' => fn ($q) => $q->orderBy('rank')])
            ->withResults()
            ->limit(4)
            ->get()
            ->map(function (Event $e) {
                $winner = $e->results->first();

                return [
                    'event_title' => $e->title,
                    'event_date' => $e->start_date,
                    'format' => $e->matchFormat?->short_name ?? $e->matchFormat?->name,
                    'winner' => $winner?->shooter_name,
                    'url' => route('matches.show', ['event' => $e->slug]).'#results',
                ];
            });

        return view('site.home', [
            'upcomingMatches' => $upcomingMatches,
            'recentResults' => $recentResults,
            'announcements' => Announcement::live()->limit(3)->get(),
        ]);
    }
}
