<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PPRC — email delivery test</title>
</head>
<body style="margin:0;padding:24px;background:#f5f7fa;font-family:Arial,Helvetica,sans-serif;color:#0f172a;line-height:1.5;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:20px 28px;background:#0f172a;color:#ffffff;">
                <p style="margin:0;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#94a3b8;">
                    Pretoria Precision Rifle Club
                </p>
                <h1 style="margin:6px 0 0;font-size:18px;font-weight:600;color:#ffffff;">
                    Email delivery test
                </h1>
            </td>
        </tr>
        <tr>
            <td style="padding:28px;">
                <p style="margin:0 0 16px;font-size:15px;">
                    If you are reading this, outbound mail from the PPRC website is working with the current configuration.
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="font-size:14px;color:#334155;">
                    <tr>
                        <td style="padding:6px 0;width:140px;color:#64748b;">Sent at</td>
                        <td style="padding:6px 0;">{{ $sentAt }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#64748b;">App URL</td>
                        <td style="padding:6px 0;">{{ $appUrl }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#64748b;">Laravel mailer</td>
                        <td style="padding:6px 0;">{{ $mailer }}</td>
                    </tr>
                    @if($triggeredBy)
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Requested by</td>
                            <td style="padding:6px 0;">{{ $triggeredBy->name }} — {{ $triggeredBy->email }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
