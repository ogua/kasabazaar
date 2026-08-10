<!doctype html>
<html>
<body style="font-family: sans-serif; color: #1C1917; background: #FAF9F7; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #E7E4DE; border-radius: 8px; padding: 24px;">
        <h2 style="color: #0F2247; margin-top: 0;">New message from the {{ config('app.name') }} contact form</h2>
        <p><strong>From:</strong> {{ $senderName }} &lt;{{ $senderEmail }}&gt;</p>
        <hr style="border: none; border-top: 1px solid #E7E4DE; margin: 16px 0;">
        <p style="white-space: pre-line; line-height: 1.6;">{{ $body }}</p>
    </div>
</body>
</html>
