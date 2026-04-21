@component('emails.layout', ['title' => "Ticket {$ticket->ticket_number} received"])
    <p style="margin:0 0 16px;font-size:16px;">Hi {{ $ticket->name }},</p>

    <p style="margin:0 0 16px;line-height:1.6;">
        Thanks for contacting us. We've logged your request as
        <strong style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ $ticket->ticket_number }}</strong>
        and our team will review it shortly.
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:20px 0;border:1px solid #e5e7eb;border-radius:8px;">
        @if($ticket->subject)
            <tr>
                <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;">
                    <p style="margin:0;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">Subject</p>
                    <p style="margin:4px 0 0;font-weight:600;">{{ $ticket->subject }}</p>
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding:12px 16px;">
                <p style="margin:0;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">Your message</p>
                <p style="margin:8px 0 0;white-space:pre-wrap;line-height:1.6;">{{ $ticket->message }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:20px 0;line-height:1.6;">
        Track the status and see new replies any time:
    </p>

    <p style="margin:0 0 24px;">
        <a href="{{ $viewUrl }}"
           style="display:inline-block;padding:10px 20px;background:#0f172a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">
            View ticket
        </a>
    </p>

    <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.5;">
        Or copy this link: <br>
        <span style="word-break:break-all;">{{ $viewUrl }}</span>
    </p>
@endcomponent
