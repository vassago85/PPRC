<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Payment received</title>
    <!--[if mso]><style>table,td{font-family:Arial,sans-serif!important}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#0b1120;font-family:'Segoe UI',Roboto,Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#0b1120;">
        <tr>
            <td align="center" style="padding:32px 16px 40px;">

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.35);">

                    <tr>
                        <td style="background-color:#0f172a;background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f172a 100%);padding:40px 36px 32px;text-align:center;">
                            <img src="{{ asset('pprclogo.png') }}" alt="PPRC" width="80" height="80" style="display:inline-block;width:80px;height:80px;border-radius:50%;border:3px solid rgba(255,255,255,0.15);margin-bottom:16px;" />
                            <p style="margin:0;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#10b981;font-weight:700;">
                                Payment received
                            </p>
                            <h1 style="margin:8px 0 0;font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">
                                {{ $event?->title ?? 'Your match entry' }}
                            </h1>
                            @if ($event?->start_date)
                                <p style="margin:8px 0 0;font-size:14px;color:#cbd5e1;">
                                    {{ $event->start_date->format('l, d F Y') }}
                                    @if ($event->location_name)
                                        &middot; {{ e($event->location_name) }}
                                    @endif
                                </p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 36px 0;">
                            <p style="margin:0 0 18px;font-size:17px;color:#0f172a;font-weight:600;">
                                Hi {{ e($firstName) }},
                            </p>
                            <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                Good news — we've received your entry fee for <strong>{{ e($event?->title ?? 'the match') }}</strong> and your spot is confirmed. See you at the range!
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:8px 36px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:18px 20px 8px;text-align:center;">
                                        <p style="margin:0;font-size:11px;color:#047857;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Amount received</p>
                                        <p style="margin:6px 0 0;font-size:28px;font-weight:700;color:#0f172a;">R {{ number_format($amountCents / 100, 2) }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 18px;text-align:center;">
                                        <p style="margin:0;font-size:12px;color:#64748b;">Reference <strong style="font-family:Consolas,Monaco,monospace;color:#0f172a;">{{ $reference }}</strong>@if ($paidOn) &middot; confirmed {{ $paidOn->format('d M Y') }}@endif</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:4px 36px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:12px;background-color:#059669;background:linear-gradient(135deg,#059669 0%,#10b981 100%);box-shadow:0 4px 14px rgba(5,150,105,0.35);">
                                        <a href="{{ $matchUrl }}" target="_blank" style="display:inline-block;padding:16px 36px;font-size:16px;font-weight:700;color:#ffffff !important;text-decoration:none;letter-spacing:0.02em;">
                                            View match details &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 36px 28px;">
                            <p style="margin:0;font-size:13px;color:#64748b;line-height:1.55;">
                                Questions? Contact us at
                                <a href="mailto:info@pretoriaprc.co.za" style="color:#1d8ac0;text-decoration:none;font-weight:600;">info@pretoriaprc.co.za</a>
                            </p>
                        </td>
                    </tr>

                </table>

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;">
                    <tr>
                        <td align="center" style="padding:24px 20px 0;">
                            <p style="margin:0 0 4px;font-size:12px;color:#475569;">Pretoria Precision Rifle Club</p>
                            <p style="margin:0;font-size:11px;color:#334155;">
                                <a href="{{ url('/') }}" style="color:#64748b;text-decoration:none;">pretoriaprc.co.za</a>
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
