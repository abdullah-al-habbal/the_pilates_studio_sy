{{-- filePath: resources/views/emails/auth/otp.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 480px; margin: 40px auto; background: #ffffff; border-radius: 8px; padding: 40px; }
        .otp-code { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #1a1a1a; text-align: center; margin: 32px 0; }
        .footer { font-size: 12px; color: #999; text-align: center; margin-top: 32px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hello, {{ $user->fullname }}</h2>
        <p>Use the code below to verify your email address. It expires in <strong>15 minutes</strong>.</p>
        <div class="otp-code">{{ $otp }}</div>
        <p>If you did not request this, please ignore this email.</p>
        <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
    </div>
</body>
</html>
