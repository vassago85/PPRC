<x-mail::message>
# Verify your email

Hi {{ $user->name }},

Use this code to verify your email address for **{{ config('app.name') }}**:

<x-mail::panel>
**{{ $pin }}**
</x-mail::panel>

This code expires in **{{ $expiresInMinutes }} minutes**.

If you did not create an account, you can ignore this message.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
