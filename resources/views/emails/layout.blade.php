<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>{{ $title ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#111827;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="560" style="max-width:560px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:20px 28px;border-bottom:1px solid #e5e7eb;background:#0f172a;color:#ffffff;">
                            <p style="margin:0;font-weight:600;font-size:16px;">
                                {{ config('app.name', 'Ticket System') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            {!! $slot !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;border-top:1px solid #e5e7eb;background:#f9fafb;color:#6b7280;font-size:12px;line-height:1.5;">
                            This is an automated message. Replies to this email are not monitored &mdash; reply from the ticket link instead.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
