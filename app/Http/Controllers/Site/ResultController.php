<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;

class ResultController extends Controller
{
    public function index()
    {
        $events = Event::query()
            ->with(['matchFormat', 'results' => fn ($q) => $q->orderBy('rank')])
            ->withResults()
            ->limit(25)
            ->get();

        $cards = $events->map(function (Event $e) {
            $winner = $e->results->first();

            return [
                'event_title' => $e->title,
                'event_date' => $e->start_date,
                'format' => $e->matchFormat?->short_name ?? $e->matchFormat?->name,
                'winner' => $winner?->shooter_name,
                'url' => route('matches.show', ['event' => $e->slug]).'#results',
            ];
        });

        return view('site.results.index', [
            'events' => $events,
            'cards' => $cards,
        ]);
    }
}
