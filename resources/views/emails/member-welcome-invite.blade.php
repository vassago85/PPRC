<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Welcome to PPRC</title>
    <!--[if mso]><style>table,td{font-family:Arial,sans-serif!important}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#0b1120;font-family:'Segoe UI',Roboto,Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">

    {{-- Outer wrapper --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#0b1120;">
        <tr>
            <td align="center" style="padding:32px 16px 40px;">

                {{-- Card --}}
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.35);">

                    {{-- Hero banner --}}
                    <tr>
                        <td style="background-color:#0f172a;background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f172a 100%);padding:40px 36px 32px;text-align:center;">
                            <img src="{{ asset('pprclogo.png') }}" alt="PPRC" width="80" height="80" style="display:inline-block;width:80px;height:80px;border-radius:50%;border:3px solid rgba(255,255,255,0.15);margin-bottom:16px;" />
                            <h1 style="margin:0 0 6px;font-size:26px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">
                                Welcome to PPRC
                            </h1>
                            <p style="margin:0;font-size:13px;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;font-weight:500;">
                                Pretoria Precision Rifle Club
                            </p>
                        </td>
                    </tr>

                    {{-- Greeting --}}
                    <tr>
                        <td style="padding:32px 36px 0;">
                            <p style="margin:0 0 18px;font-size:17px;color:#0f172a;font-weight:600;">
                                Hi{{ $firstName ? ' ' . e($firstName) : '' }},
                            </p>
                            <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                Your membership record has been set up on our new member portal. To claim your account, set a secure password using the button below.
                            </p>
                            <p style="margin:0 0 6px;font-size:15px;color:#334155;line-height:1.65;">
                                This link is unique to you and expires after a short period for your security.
                            </p>
                        </td>
                    </tr>

                    {{-- CTA button --}}
                    <tr>
                        <td align="center" style="padding:28px 36px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:12px;background-color:#0ea5e9;background:linear-gradient(135deg,#1d8ac0 0%,#0ea5e9 100%);box-shadow:0 4px 14px rgba(29,138,192,0.4);">
                                        <a href="{{ $setupUrl }}" target="_blank" style="display:inline-block;padding:16px 36px;font-size:16px;font-weight:700;color:#ffffff !important;text-decoration:none;letter-spacing:0.02em;">
                                            Set your password &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Fallback link --}}
                    <tr>
                        <td style="padding:0 36px 24px;">
                            <p style="margin:0 0 6px;font-size:12px;color:#94a3b8;">
                                If the button doesn't work, copy and paste this link:
                            </p>
                            <p style="margin:0;font-size:12px;word-break:break-all;">
                                <a href="{{ $setupUrl }}" style="color:#1d8ac0;text-decoration:none;">{{ $setupUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding:0 36px;">
                            <div style="border-top:1px solid #e2e8f0;"></div>
                        </td>
                    </tr>

                    {{-- Account info --}}
                    <tr>
                        <td style="padding:20px 36px 12px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td width="32" valign="top" style="padding-right:12px;">
                                        <div style="width:32px;height:32px;background:#f1f5f9;border-radius:8px;text-align:center;line-height:32px;font-size:16px;">&#128272;</div>
                                    </td>
                                    <td valign="top">
                                        <p style="margin:0 0 2px;font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Your login email</p>
                                        <p style="margin:0;font-size:14px;color:#0f172a;font-weight:600;">{{ $user->email }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Safety note --}}
                    <tr>
                        <td style="padding:8px 36px 28px;">
                            <p style="margin:0;font-size:13px;color:#64748b;line-height:1.55;">
                                If you weren't expecting this email you can safely ignore it — no account change happens until the link above is clicked.
                            </p>
                        </td>
                    </tr>

                </table>
                {{-- /Card --}}

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
