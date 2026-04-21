<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PPRC shop — orders open</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #0f172a; max-width: 36rem; margin: 0 auto; padding: 1.5rem;">
    <p>Hi{{ $subscriber->name ? ' '.$subscriber->name : '' }},</p>
    <p><strong>{{ $run->title }}</strong> is now open for orders at Pretoria Precision Rifle Club.</p>
    @if ($run->announcement)
        <p>{{ $run->announcement }}</p>
    @endif
    <p style="margin: 2rem 0;">
        <a href="{{ $shopUrl }}" style="display: inline-block; background: #0f172a; color: #fff; padding: 0.75rem 1.25rem; text-decoration: none; border-radius: 0.375rem;">View products</a>
    </p>
    <p>Members can place orders in the portal:</p>
    <p><a href="{{ $portalUrl }}">{{ $portalUrl }}</a></p>
    <p style="margin-top: 2rem; font-size: 0.8125rem; color: #64748b;">
        <a href="{{ $unsubscribeUrl }}" style="color: #64748b;">Unsubscribe from shop waitlist</a>
    </p>
    <p>— {{ config('app.name') }}</p>
</body>
</html>
