<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Membership Approved</title>
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
                            <h1 style="margin:0 0 6px;font-size:26px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">
                                You're in! &#127919;
                            </h1>
                            <p style="margin:0;font-size:13px;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;font-weight:500;">
                                Membership approved
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 36px 0;">
                            <p style="margin:0 0 18px;font-size:17px;color:#0f172a;font-weight:600;">
                                Hi {{ e($member->first_name) }},
                            </p>
                            <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                Great news — your <strong>{{ e($typeName) }}</strong> membership at Pretoria Precision Rifle Club has been approved and is now <span style="color:#16a34a;font-weight:700;">active</span>.
                            </p>
                        </td>
                    </tr>

                    {{-- Details card --}}
                    <tr>
                        <td style="padding:8px 36px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                                @if($member->membership_number)
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
                                        <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Membership type</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 14px;">
                                        <p style="margin:0;font-size:15px;color:#0f172a;font-weight:600;">{{ e($typeName) }}</p>
                                    </td>
                                </tr>
                                <tr><td style="padding:0 20px;"><div style="border-top:1px solid #e2e8f0;"></div></td></tr>
                                <tr>
                                    <td style="padding:14px 20px 6px;">
                                        <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Valid until</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 16px;">
                                        <p style="margin:0;font-size:15px;color:#0f172a;font-weight:600;">
                                            {{ $membership->period_end ? $membership->period_end->format('d F Y') : 'Lifetime — no renewal needed' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td align="center" style="padding:4px 36px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:12px;background-color:#16a34a;background:linear-gradient(135deg,#16a34a 0%,#22c55e 100%);box-shadow:0 4px 14px rgba(22,163,74,0.35);">
                                        <a href="{{ $portalUrl }}" target="_blank" style="display:inline-block;padding:16px 36px;font-size:16px;font-weight:700;color:#ffffff !important;text-decoration:none;letter-spacing:0.02em;">
                                            Go to your portal &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- What you can do --}}
                    <tr>
                        <td style="padding:0 36px;">
                            <div style="border-top:1px solid #e2e8f0;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 36px 24px;">
                            <p style="margin:0 0 10px;font-size:13px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">What you can do now</p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td width="24" valign="top" style="padding:4px 8px 4px 0;font-size:14px;">&#9989;</td>
                                    <td style="padding:4px 0;font-size:14px;color:#334155;">Register for upcoming matches and events</td>
                                </tr>
                                <tr>
                                    <td width="24" valign="top" style="padding:4px 8px 4px 0;font-size:14px;">&#9989;</td>
                                    <td style="padding:4px 0;font-size:14px;color:#334155;">Download your membership certificate</td>
                                </tr>
                                <tr>
                                    <td width="24" valign="top" style="padding:4px 8px 4px 0;font-size:14px;">&#9989;</td>
                                    <td style="padding:4px 0;font-size:14px;color:#334155;">View your match results and history</td>
                                </tr>
                                <tr>
                                    <td width="24" valign="top" style="padding:4px 8px 4px 0;font-size:14px;">&#9989;</td>
                                    <td style="padding:4px 0;font-size:14px;color:#334155;">Request a firearm licence endorsement letter</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Contact --}}
                    <tr>
                        <td style="padding:0 36px 28px;">
                            <p style="margin:0;font-size:13px;color:#64748b;line-height:1.55;">
                                Questions? Contact the membership secretary at
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
