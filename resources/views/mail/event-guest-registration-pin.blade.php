<x-mail::message>
# Match registration

You asked to register as a guest for **{{ $event->title }}** on {{ $event->start_date?->format('l j F Y') }}.

Your one-time confirmation code is:

<x-mail::panel>
**{{ $pin }}**
</x-mail::panel>

This code expires in 15 minutes. If you did not request this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
