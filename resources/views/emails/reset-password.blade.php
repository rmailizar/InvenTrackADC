<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>

<body style="margin:0;padding:0;background:#f4f7f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f6;margin:0;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;">
                    <tr>
                        <td align="center" style="padding:8px 0 22px;">
                        <img src="{{ $message->embed(public_path('images/logo-web.png')) }}" alt="Next Logistic" width="180" style="display:block;width:180px;max-width:70%;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;border-radius:14px;padding:34px 36px;box-shadow:0 16px 40px rgba(15,23,42,0.08);">
                            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#0f172a;font-weight:800;">
                                Halo, {{ $user->name }}!
                            </h1>

                            <p style="margin:0 0 18px;font-size:16px;line-height:1.7;color:#4b5563;">
                                Kami menerima permintaan untuk mengatur ulang password akun Anda.
                            </p>

                            <p style="margin:0 0 28px;font-size:16px;line-height:1.7;color:#4b5563;">
                                Klik tombol di bawah ini untuk membuat password baru.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 30px;">
                                <tr>
                                    <td align="center" bgcolor="#10b981" style="border-radius:10px;box-shadow:0 10px 20px rgba(16,185,129,0.24);">
                                        <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 26px;background:#10b981;color:#ffffff;text-decoration:none;border-radius:10px;font-size:16px;font-weight:700;">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin:0 0 24px;padding:14px 16px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;color:#047857;font-size:14px;line-height:1.6;">
                                Link reset password ini hanya berlaku selama <strong>{{ $expireMinutes }} menit</strong>.
                            </div>

                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#6b7280;">
                                Jika Anda tidak meminta reset password, abaikan email ini. Password Anda tidak akan berubah.
                            </p>

                            <p style="margin:0;font-size:14px;line-height:1.7;color:#6b7280;">
                                Jika tombol tidak bisa dibuka, salin dan tempel link berikut ke browser:
                            </p>

                            <p style="margin:8px 0 0;font-size:13px;line-height:1.6;word-break:break-all;">
                                <a href="{{ $resetUrl }}" style="color:#059669;text-decoration:underline;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:22px 10px 0;color:#94a3b8;font-size:12px;line-height:1.6;">
                            &copy; {{ date('Y') }} {{ $appName }}. Email ini dikirim otomatis, mohon tidak membalas email ini.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
