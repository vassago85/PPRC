@php
    $isLapsed = $variant === 'lapsed';
    $heroAccent = $isLapsed ? '#dc2626' : '#d97706';
    $heroAccentDeep = $isLapsed ? '#991b1b' : '#92400e';
    $headline = $isLapsed ? 'Your membership has lapsed' : 'Renewal reminder';
    $statusLabel = $isLapsed ? 'Lapsed membership' : 'Expiring soon';
@endphp
<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $headline }}</title>
    <!--[if mso]><style>table,td{font-family:Arial,sans-serif!important}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#0b1120;font-family:'Segoe UI',Roboto,Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#0b1120;">
        <tr>
            <td align="center" style="padding:32px 16px 40px;">

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.35);">

                    {{-- Hero --}}
                    <tr>
                        <td style="background-color:#0f172a;background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f172a 100%);padding:40px 36px 32px;text-align:center;">
                            <img src="{{ asset('pprclogo.png') }}" alt="PPRC" width="80" height="80" style="display:inline-block;width:80px;height:80px;border-radius:50%;border:3px solid rgba(255,255,255,0.15);margin-bottom:16px;" />
                            <p style="margin:0;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:{{ $heroAccent }};font-weight:700;">
                                {{ $statusLabel }}
                            </p>
                            <h1 style="margin:8px 0 0;font-size:26px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">
                                {{ $headline }}
                            </h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 36px 0;">
                            <p style="margin:0 0 18px;font-size:17px;color:#0f172a;font-weight:600;">
                                Hi {{ e($member->first_name) }},
                            </p>
                            @if ($isLapsed)
                                <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                    Your PPRC membership lapsed on
                                    <strong>{{ $expiryDate?->format('d F Y') ?? '—' }}</strong>
                                    ({{ $days }} {{ $days === 1 ? 'day' : 'days' }} ago). You're not booted off the site — your profile and history are still intact — but you're no longer eligible for member entry fees, endorsement letters, or club discounts until you renew.
                                </p>
                                <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                    Renewing takes about a minute through the member portal.
                                </p>
                            @else
                                <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                    Your PPRC membership expires on
                                    <strong>{{ $expiryDate?->format('d F Y') ?? '—' }}</strong>
                                    — that's <strong>{{ $days }} {{ $days === 1 ? 'day' : 'days' }}</strong> from now. Renewing before that date keeps your membership number, endorsement eligibility and member entry fees uninterrupted.
                                </p>
                            @endif
                        </td>
                    </tr>

                    {{-- Details card --}}
                    <tr>
                        <td style="padding:8px 36px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                                @if ($member->membership_number)
                                    <tr>
                                        <td style="padding:14px 20px 6px;">
                                            <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Membership number</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:0 20px 14px;">
                                            <p style="margin:0;font-size:20px;color:#0f172a;font-weight:700;letter-spacing:0.04em;">{{ $member->membership_number }}</p>
                                        </td>
                                    </tr>
                                    <tr><td style="padding:0 20px;"><div style="border-top:1px solid #e2e8f0;"></div></td></tr>
                                @endif
                                <tr>
                                    <td style="padding:14px 20px 6px;">
                                        <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">{{ $isLapsed ? 'Lapsed since' : 'Expires on' }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 16px;">
                                        <p style="margin:0;font-size:15px;color:#0f172a;font-weight:600;">
                                            {{ $expiryDate?->format('d F Y') ?? '—' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Renew CTA --}}
                    <tr>
                        <td align="center" style="padding:4px 36px 18px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:12px;background-color:#16a34a;background:linear-gradient(135deg,#16a34a 0%,#22c55e 100%);box-shadow:0 4px 14px rgba(22,163,74,0.35);">
                                        <a href="{{ $renewUrl }}" target="_blank" style="display:inline-block;padding:16px 36px;font-size:16px;font-weight:700;color:#ffffff !important;text-decoration:none;letter-spacing:0.02em;">
                                            Renew my membership &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Cancel option (no commitment) --}}
                    <tr>
                        <td style="padding:0 36px;">
                            <div style="border-top:1px solid #e2e8f0;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 36px 8px;">
                            <p style="margin:0 0 8px;font-size:13px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">Don't want to renew?</p>
                            <p style="margin:0 0 14px;font-size:14px;color:#475569;line-height:1.6;">
                                We get it — life moves on. You can cancel your membership in two clicks and we won't email you about renewals again.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding:0 36px 28px;">
                            <a href="{{ $cancelUrl }}" target="_blank" style="display:inline-block;padding:10px 20px;font-size:13px;font-weight:600;color:#475569 !important;text-decoration:none;border:1px solid #cbd5e1;border-radius:10px;background:#ffffff;">
                                Cancel my membership
                            </a>
                            <p style="margin:10px 0 0;font-size:12px;color:#94a3b8;line-height:1.5;">
                                The link asks you to confirm before anything happens. It's valid for 30 days.
                            </p>
                        </td>
                    </tr>

                    {{-- Contact --}}
                    <tr>
                        <td style="padding:0 36px;">
                            <div style="border-top:1px solid #e2e8f0;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 36px 28px;">
                            <p style="margin:0;font-size:13px;color:#64748b;line-height:1.55;">
                                Questions or paying offline? Reach the membership secretary at
                                <a href="mailto:membership@pretoriaprc.co.za" style="color:#1d8ac0;text-decoration:none;font-weight:600;">membership@pretoriaprc.co.za</a>
                            </p>
                        </td>
                    </tr>

                </table>

                {{-- Footer --}}
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;">
                    <tr>
                        <td align="center" style="padding:24px 20px 0;">
                            <p style="margin:0 0 4px;font-size:12px;color:#475569;">
                                Pretoria Precision Rifle Club
                            </p>
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
