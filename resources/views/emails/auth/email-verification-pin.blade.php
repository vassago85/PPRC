<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Verify your email</title>
    <!--[if mso]><style>table,td{font-family:Arial,sans-serif!important}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#0b1120;font-family:'Segoe UI',Roboto,Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#0b1120;">
        <tr>
            <td align="center" style="padding:32px 16px 40px;">

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.35);">

                    {{-- Hero --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f172a 100%);padding:40px 36px 32px;text-align:center;">
                            <img src="{{ asset('pprclogo.png') }}" alt="PPRC" width="64" height="64" style="display:inline-block;width:64px;height:64px;border-radius:50%;border:3px solid rgba(255,255,255,0.15);margin-bottom:14px;" />
                            <h1 style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">
                                Verify your email
                            </h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 36px 0;">
                            <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                Hi {{ e($user->name) }}, enter this code on the verification page to confirm your email address:
                            </p>
                        </td>
                    </tr>

                    {{-- PIN code --}}
                    <tr>
                        <td align="center" style="padding:8px 36px 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background:#f1f5f9;border:2px solid #e2e8f0;border-radius:12px;padding:18px 40px;">
                                        <p style="margin:0;font-size:36px;font-weight:800;color:#0f172a;letter-spacing:0.25em;font-family:'Courier New',Courier,monospace;">
                                            {{ $pin }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Expiry --}}
                    <tr>
                        <td align="center" style="padding:12px 36px 28px;">
                            <p style="margin:0;font-size:13px;color:#94a3b8;">
                                This code expires in <strong style="color:#64748b;">{{ $expiresInMinutes }} minutes</strong>
                            </p>
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding:0 36px;">
                            <div style="border-top:1px solid #e2e8f0;"></div>
                        </td>
                    </tr>

                    {{-- Safety --}}
                    <tr>
                        <td style="padding:20px 36px 28px;">
                            <p style="margin:0;font-size:13px;color:#64748b;line-height:1.55;">
                                If you did not create an account at Pretoria Precision Rifle Club, you can safely ignore this email.
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
