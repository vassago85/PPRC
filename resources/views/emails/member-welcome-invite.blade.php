<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Welcome to PPRC</title>
</head>
<body style="margin:0;padding:24px;background:#f5f7fa;font-family:Arial,Helvetica,sans-serif;color:#0f172a;line-height:1.6;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:24px 28px;background:#0f172a;color:#ffffff;">
                <p style="margin:0;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#94a3b8;">
                    Pretoria Precision Rifle Club
                </p>
                <h1 style="margin:6px 0 0;font-size:20px;font-weight:600;color:#ffffff;">
                    Welcome{{ $firstName ? ', ' . $firstName : '' }}.
                </h1>
            </td>
        </tr>
        <tr>
            <td style="padding:28px;font-size:15px;color:#0f172a;">
                <p style="margin:0 0 14px;">
                    Your PPRC membership record has been moved to our new member portal.
                    To finish setting up your account, choose a password using the button
                    below. This link is unique to you and will expire after a short period
                    for security.
                </p>

                <p style="margin:20px 0 28px;">
                    <a href="{{ $setupUrl }}"
                       style="display:inline-block;padding:14px 22px;background:#1d8ac0;color:#ffffff;border-radius:10px;font-weight:600;text-decoration:none;">
                        Set your password
                    </a>
                </p>

                <p style="margin:0 0 14px;font-size:13px;color:#475569;">
                    If the button doesn't work, paste this link into your browser:
                </p>
                <p style="margin:0 0 18px;font-size:13px;word-break:break-all;">
                    <a href="{{ $setupUrl }}" style="color:#1d8ac0;text-decoration:none;">{{ $setupUrl }}</a>
                </p>

                <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">

                <p style="margin:0 0 6px;font-size:13px;color:#475569;">
                    Your login email is <strong>{{ $user->email }}</strong>.
                </p>
                <p style="margin:0;font-size:13px;color:#475569;">
                    If you weren't expecting this email you can safely ignore it — no account
                    change happens until someone clicks the link above.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
