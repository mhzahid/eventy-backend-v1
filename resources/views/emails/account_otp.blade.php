<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0; padding:0; background-color:#f4f4f4; font-family:Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4; padding:40px 0;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0" border="0"
                    style="background-color:#ffffff; border-radius:8px; padding:40px;">
                    <tr>
                        <td style="font-size:22px; font-weight:bold; color:#1a1a1a; padding-bottom:12px;">
                            Verify Your {{ ucfirst($purpose) }} Change
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px; color:#555555; padding-bottom:8px;">
                            Use the OTP below to confirm your {{ $purpose }} change. It expires in
                            <strong>{{ $expiresAt }} minutes</strong>.
                        </td>
                    </tr>
                    <tr>
                        <td align="center"
                            style="font-size:36px; font-weight:bold; letter-spacing:8px; color:#1a1a1a; padding:24px 0;">
                            {{ $otp }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px; color:#555555; padding-bottom:24px;">
                            If you did not request this change, please secure your account immediately.
                        </td>
                    </tr>
                    <tr>
                        <td align="center"
                            style="font-size:12px; color:#999999; border-top:1px solid #eeeeee; padding-top:16px;">
                            &copy; {{ date('Y') }} {{ config('app.name') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
