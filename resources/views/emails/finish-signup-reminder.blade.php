@php
    $isVerify = $variant === 'verify';
    $statusLabel = $isVerify ? 'Confirm your email' : 'Choose your membership';
    $headline = $isVerify ? 'Finish setting up your account' : 'One step left to join';
    $ctaLabel = $isVerify ? 'Confirm my email →' : 'Choose my membership →';
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
                            <p style="margin:0;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#d97706;font-weight:700;">
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
                            @if ($isVerify)
                                <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                    You started creating a Pretoria Precision Rifle Club account a while back but never
                                    confirmed your email address, so your registration is still incomplete.
                                </p>
                                <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                    Log in and we'll send you a quick verification PIN to finish. It only takes a minute.
                                </p>
                            @else
                                <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                    Thanks for verifying your email — but you haven't chosen a membership yet, so your
                                    application isn't complete. You're almost there!
                                </p>
                                <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                    Pick a membership in the portal and follow the payment instructions to get fully set up.
                                </p>
                            @endif
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td align="center" style="padding:12px 36px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:12px;background-color:#16a34a;background:linear-gradient(135deg,#16a34a 0%,#22c55e 100%);box-shadow:0 4px 14px rgba(22,163,74,0.35);">
                                        <a href="{{ $actionUrl }}" target="_blank" style="display:inline-block;padding:16px 36px;font-size:16px;font-weight:700;color:#ffffff !important;text-decoration:none;letter-spacing:0.02em;">
                                            {{ $ctaLabel }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Gentle notice --}}
                    <tr>
                        <td style="padding:0 36px;">
                            <div style="border-top:1px solid #e2e8f0;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 36px 8px;">
                            <p style="margin:0 0 14px;font-size:14px;color:#475569;line-height:1.6;">
                                If you no longer want to join, you can simply ignore this email — we'll quietly archive the
                                incomplete signup and won't email you about it again. You can always start over later.
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
                                Need a hand? Reach the membership secretary at
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
