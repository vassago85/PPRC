<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PPRC website — new enquiry</title>
</head>
<body style="margin:0;padding:24px;background:#f5f7fa;font-family:Arial,Helvetica,sans-serif;color:#0f172a;line-height:1.5;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:20px 28px;background:#0f172a;color:#ffffff;">
                <p style="margin:0;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#94a3b8;">
                    Pretoria Precision Rifle Club
                </p>
                <h1 style="margin:6px 0 0;font-size:18px;font-weight:600;color:#ffffff;">
                    New enquiry from the website
                </h1>
            </td>
        </tr>
        <tr>
            <td style="padding:28px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="padding:0 0 14px;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:0.12em;">From</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 16px;font-size:15px;">
                            <strong style="color:#0f172a;">{{ $senderName }}</strong><br>
                            <a href="mailto:{{ $senderEmail }}" style="color:#1d8ac0;text-decoration:none;">{{ $senderEmail }}</a>
                        </td>
                    </tr>

                    @if ($senderSubject)
                        <tr>
                            <td style="padding:14px 0 6px;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:0.12em;">Subject</td>
                        </tr>
                        <tr>
                            <td style="padding:0 0 16px;font-size:15px;color:#0f172a;">{{ $senderSubject }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:14px 0 6px;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:0.12em;">Message</td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 8px;font-size:15px;color:#0f172a;white-space:pre-wrap;">{{ $messageBody }}</td>
                    </tr>
                </table>

                <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">

                <p style="margin:0;font-size:12px;color:#64748b;">
                    Reply directly to this email to respond to the sender.
                    @if ($ipAddress)
                        &middot; Submitted from IP <code style="font-family:ui-monospace,monospace;">{{ $ipAddress }}</code>.
                    @endif
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
