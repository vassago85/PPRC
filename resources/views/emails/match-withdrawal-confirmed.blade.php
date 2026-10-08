<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $isCashRefund ? 'Refund issued for' : 'Withdrawal confirmed for' }} {{ $event?->title }}</title>
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
                                {{ $isCashRefund ? 'Refund issued' : 'Withdrawal confirmed' }}
                            </h1>
                            <p style="margin:8px 0 0;font-size:14px;color:#cbd5e1;">
                                {{ $event?->title ?? 'PPRC match' }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 36px 8px;">
                            <p style="margin:0 0 18px;font-size:17px;color:#0f172a;font-weight:600;">
                                Hi {{ e($firstName) }},
                            </p>

                            @if ($isCashRefund)
                                <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                    We've withdrawn your entry for <strong>{{ $event?->title ?? 'the match' }}</strong> and handed your refund back out of the match-day float.
                                </p>
                            @else
                                <p style="margin:0 0 16px;font-size:15px;color:#334155;line-height:1.65;">
                                    We've withdrawn your entry for <strong>{{ $event?->title ?? 'the match' }}</strong>. Your refund will be paid by EFT in the next weekly match-payment run — usually within seven days.
                                </p>
                            @endif

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding:12px 0;font-size:14px;color:#64748b;border-bottom:1px solid #e2e8f0;">Refund amount</td>
                                    <td style="padding:12px 0;font-size:14px;color:#0f172a;font-weight:600;text-align:right;border-bottom:1px solid #e2e8f0;">
                                        R {{ number_format($amountCents / 100, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;font-size:14px;color:#64748b;border-bottom:1px solid #e2e8f0;">Method</td>
                                    <td style="padding:12px 0;font-size:14px;color:#0f172a;font-weight:600;text-align:right;border-bottom:1px solid #e2e8f0;">
                                        {{ $method?->label() ?? 'EFT' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;font-size:14px;color:#64748b;border-bottom:1px solid #e2e8f0;">Status</td>
                                    <td style="padding:12px 0;font-size:14px;color:#0f172a;font-weight:600;text-align:right;border-bottom:1px solid #e2e8f0;">
                                        {{ $isCashRefund ? 'Paid' : 'Due at next weekly payout' }}
                                    </td>
                                </tr>
                                @if ($recordedOn)
                                    <tr>
                                        <td style="padding:12px 0;font-size:14px;color:#64748b;border-bottom:1px solid #e2e8f0;">Recorded</td>
                                        <td style="padding:12px 0;font-size:14px;color:#0f172a;font-weight:600;text-align:right;border-bottom:1px solid #e2e8f0;">
                                            {{ $recordedOn->format('d M Y') }}
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:12px 0;font-size:14px;color:#64748b;">Reference</td>
                                    <td style="padding:12px 0;font-size:14px;color:#0f172a;font-weight:600;text-align:right;">
                                        {{ $reference }}
                                    </td>
                                </tr>
                            </table>

                            @if (filled($note))
                                <p style="margin:16px 0 0;padding:12px 16px;background-color:#f1f5f9;border-left:3px solid #38bdf8;font-size:14px;color:#334155;line-height:1.6;">
                                    {!! nl2br(e($note)) !!}
                                </p>
                            @endif

                            @if ($isCashRefund)
                                <p style="margin:16px 0 0;font-size:13px;color:#64748b;line-height:1.6;">
                                    If anything about the amount looks off, drop us a line and we'll sort it.
                                </p>
                            @else
                                <p style="margin:16px 0 0;font-size:13px;color:#64748b;line-height:1.6;">
                                    EFT refunds go out together with the match director's payout and prize money, usually within a week. You'll see it land in the account you originally paid from — we don't send a second email when it clears.
                                </p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 36px 36px;">
                            <p style="margin:0;border-top:1px solid #e2e8f0;padding-top:18px;font-size:13px;color:#94a3b8;line-height:1.6;">
                                Questions? Reply to this email or contact
                                <a href="mailto:info@pretoriaprc.co.za" style="color:#0ea5e9;">info@pretoriaprc.co.za</a>.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
