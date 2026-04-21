@component('emails.layout', ['title' => "New reply on {$ticket->ticket_number}"])
    <p style="margin:0 0 16px;font-size:16px;">Hi {{ $ticket->name }},</p>

    <p style="margin:0 0 16px;line-height:1.6;">
        Your ticket
        <strong style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ $ticket->ticket_number }}</strong>
        has a new reply from {{ $authorName }}.
    </p>

    <div style="margin:20px 0;padding:16px 20px;background:#f9fafb;border:1px solid #e5e7eb;border-left:4px solid #0f172a;border-radius:6px;">
        <p style="margin:0;white-space:pre-wrap;line-height:1.6;">{{ $reply->message }}</p>
    </div>

    <p style="margin:0 0 24px;">
        <a href="{{ $viewUrl }}"
           style="display:inline-block;padding:10px 20px;background:#0f172a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">
            View conversation &amp; reply
        </a>
    </p>

    <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.5;">
        Or copy this link: <br>
        <span style="word-break:break-all;">{{ $viewUrl }}</span>
    </p>
@endcomponent
