<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm your PPRC shop waitlist</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #0f172a; max-width: 36rem; margin: 0 auto; padding: 1.5rem;">
    <p>Hi{{ $subscriber->name ? ' '.$subscriber->name : '' }},</p>
    <p>Thanks for joining the PPRC apparel shop waitlist. Confirm your email using the link below. Until you confirm, you will not receive emails when a new order window opens.</p>
    <p style="margin: 2rem 0;">
        <a href="{{ $confirmUrl }}" style="display: inline-block; background: #0f172a; color: #fff; padding: 0.75rem 1.25rem; text-decoration: none; border-radius: 0.375rem;">Confirm email</a>
    </p>
    <p style="font-size: 0.875rem; color: #64748b;">If you did not sign up, you can ignore this message.</p>
    <p>— {{ config('app.name') }}</p>
</body>
</html>
