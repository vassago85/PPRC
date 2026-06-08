<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Match payment</title>
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
                            <p style="margin:0;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#d97706;font-weight:700;">
                                {{ $isReminder ? 'Payment reminder' : 'Match entry payment' }}
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
                                @if ($isReminder)
                                    This is a reminder that your entry fee for <strong>{{ e($event?->title ?? 'this match') }}</strong> is still outstanding. Please pay using the details below.
                                @else
                                    Thanks for entering <strong>{{ e($event?->title ?? 'the match') }}</strong>. Please settle your entry fee using the banking details below to confirm your spot.
                                @endif
                            </p>
                            <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                <strong>Already paid?</strong> Reply to this email with your proof of payment, or send it to
                                <a href="mailto:matches@pretoriaprc.co.za" style="color:#1d8ac0;text-decoration:none;font-weight:600;">matches@pretoriaprc.co.za</a>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:8px 36px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:18px 20px 8px;text-align:center;">
                                        <p style="margin:0;font-size:11px;color:#92400e;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Your payment reference</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 10px;text-align:center;">
                                        <p style="margin:0;font-family:Consolas,Monaco,monospace;font-size:24px;font-weight:700;color:#0f172a;letter-spacing:0.06em;">{{ $reference }}</p>
                                        <p style="margin:6px 0 0;font-size:12px;color:#64748b;">Use this exact reference when paying</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 18px;text-align:center;">
                                        <p style="margin:0;font-size:13px;color:#64748b;">Amount to pay</p>
                                        <p style="margin:4px 0 0;font-size:28px;font-weight:700;color:#0f172a;">R {{ number_format($amountCents / 100, 2) }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if ($bankName || $accountNumber)
                        <tr>
                            <td style="padding:0 36px 24px;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                                    <tr>
                                        <td style="padding:14px 20px 10px;">
                                            <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Bank details</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:0 20px 16px;">
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                @if ($accountName)
                                                    <tr>
                                                        <td style="padding:4px 0;font-size:12px;color:#94a3b8;width:40%;">Account name</td>
                                                        <td style="padding:4px 0;font-size:14px;color:#0f172a;font-weight:600;">{{ e($accountName) }}</td>
                                                    </tr>
                                                @endif
                                                @if ($bankName)
                                                    <tr>
                                                        <td style="padding:4px 0;font-size:12px;color:#94a3b8;">Bank</td>
                                                        <td style="padding:4px 0;font-size:14px;color:#0f172a;font-weight:600;">{{ e($bankName) }}</td>
                                                    </tr>
                                                @endif
                                                @if ($accountNumber)
                                                    <tr>
                                                        <td style="padding:4px 0;font-size:12px;color:#94a3b8;">Account number</td>
                                                        <td style="padding:4px 0;font-family:Consolas,Monaco,monospace;font-size:14px;color:#0f172a;font-weight:600;">{{ e($accountNumber) }}</td>
                                                    </tr>
                                                @endif
                                                @if ($branchCode)
                                                    <tr>
                                                        <td style="padding:4px 0;font-size:12px;color:#94a3b8;">Branch code</td>
                                                        <td style="padding:4px 0;font-family:Consolas,Monaco,monospace;font-size:14px;color:#0f172a;font-weight:600;">{{ e($branchCode) }}</td>
                                                    </tr>
                                                @endif
                                                @if ($accountType)
                                                    <tr>
                                                        <td style="padding:4px 0;font-size:12px;color:#94a3b8;">Account type</td>
                                                        <td style="padding:4px 0;font-size:14px;color:#0f172a;font-weight:600;text-transform:capitalize;">{{ e($accountType) }}</td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if ($bankNotes)
                        <tr>
                            <td style="padding:0 36px 20px;">
                                <p style="margin:0;font-size:13px;color:#64748b;line-height:1.55;">{{ e($bankNotes) }}</p>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td align="center" style="padding:4px 36px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:12px;background-color:#d97706;background:linear-gradient(135deg,#d97706 0%,#f59e0b 100%);box-shadow:0 4px 14px rgba(217,119,6,0.35);">
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
                                Questions? Contact the match director at
                                <a href="mailto:matches@pretoriaprc.co.za" style="color:#1d8ac0;text-decoration:none;font-weight:600;">matches@pretoriaprc.co.za</a>
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
