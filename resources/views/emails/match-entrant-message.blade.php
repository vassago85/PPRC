<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subjectLine }}</title>
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
                            <p style="margin:0;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#38bdf8;font-weight:700;">
                                Pretoria Precision Rifle Club
                            </p>
                            <h1 style="margin:8px 0 0;font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">
                                {{ $event?->title ?? 'Match update' }}
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
                        <td style="padding:32px 36px 8px;">
                            <p style="margin:0 0 18px;font-size:17px;color:#0f172a;font-weight:600;">
                                Hi {{ e($firstName) }},
                            </p>
                            <div style="margin:0;font-size:15px;color:#334155;line-height:1.65;">
                                {!! nl2br(e($body)) !!}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:24px 36px 32px;">
                            <a href="{{ $matchUrl }}" target="_blank"
                               style="display:inline-block;background:#38bdf8;color:#04263a !important;text-decoration:none;font-weight:700;font-size:15px;padding:14px 28px;border-radius:10px;">
                                View the match
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 36px 36px;">
                            <p style="margin:0;border-top:1px solid #e2e8f0;padding-top:18px;font-size:13px;color:#94a3b8;line-height:1.6;">
                                You received this because you are entered in this PPRC match. Questions?
                                Reply to this email or contact <a href="mailto:info@pretoriaprc.co.za" style="color:#0ea5e9;">info@pretoriaprc.co.za</a>.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
