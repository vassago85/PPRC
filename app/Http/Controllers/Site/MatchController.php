<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;

class MatchController extends Controller
{
    public function index()
    {
        // `withCount('registrations')` so each card's registrationState() can
        // check Full-ness without firing a separate query per card.
        $upcoming = Event::query()
            ->with('matchFormat')
            ->withCount('registrations')
            ->upcoming()
            ->limit(30)
            ->get()
            ->map(fn (Event $e) => $this->toCard($e, upcoming: true));

        $past = Event::query()
            ->with('matchFormat')
            ->published()
            ->where('start_date', '<', now()->toDateString())
            ->orderByDesc('start_date')
            ->limit(20)
            ->get()
            ->map(fn (Event $e) => $this->toCard($e, upcoming: false));

        return view('site.matches.index', [
            'upcoming' => $upcoming,
            'past' => $past,
        ]);
    }

    /**
     * Park the match URL as the "intended" destination and send the visitor
     * through the login flow. The Fortify-powered login + verified-PIN
     * controllers all call `redirect()->intended(...)`, so a member who signs
     * in from a match page lands back on the same match ready to register.
     *
     * Logged-in members skip the detour; they already have an account and
     * going via /login just to bounce back would be noise.
     */
    public function signIn(Event $event)
    {
        abort_unless($event->isPubliclyVisible(), 404);

        $target = route('matches.show', ['event' => $event->slug]).'#enter';

        if (auth()->check()) {
            return redirect($target);
        }

        session()->put('url.intended', $target);

        return redirect()->route('login');
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
            ->with([
                'member:id,user_id,first_name,last_name,membership_number',
                'member.user:id',
                'member.user.roles:id,name',
            ])
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

    /**
     * `$upcoming` controls whether the card bothers computing the registration
     * state — past matches never show a status badge so skipping it avoids
     * work and avoids a card that reads "Match finished" where the eye expects
     * a date.
     */
    private function toCard(Event $e, bool $upcoming): array
    {
        return [
            'title' => $e->title,
            'starts_at' => $e->start_date,
            'location' => $e->location_name,
            'format' => $e->matchFormat?->short_name ?? $e->matchFormat?->name,
            'banner_url' => $e->bannerUrl(),
            'url' => route('matches.show', ['event' => $e->slug]),
            'registration_state' => $upcoming ? $e->registrationState() : null,
            'registrations_open_at' => $upcoming ? $e->registrations_open_at : null,
        ];
    }
}
