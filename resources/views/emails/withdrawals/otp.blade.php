<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #111; line-height: 1.5;">
    <p>Hi {{ $name }},</p>
    <p>Use this code to confirm your withdrawal request of <strong>₦{{ $amount }}</strong>:</p>
    <p style="font-size: 28px; letter-spacing: 6px; font-weight: bold;">{{ $code }}</p>
    <p>It expires in {{ $minutes }} minutes and can be used once.</p>
    <p>If you did not request this, do not share the code. Please tell an administrator.</p>
</body>
</html>
