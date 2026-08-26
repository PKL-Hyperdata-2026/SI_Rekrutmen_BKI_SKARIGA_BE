<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>

<body style="margin:0;padding:24px;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%"
                    style="max-width:480px;background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:28px;">
                            <a href="{{ env('FRONTEND_URL') }}">
                                <img src="https://file.marvfiles.web.id/uploads/be007566-82c0-46d8-ac46-cbbd1fdf00fe.png"
                                    alt="SKARIGA" style="width:100px;display: block;margin-left: auto;margin-right: auto;cursor: pointer;">
                            </a>
                            <h1 style="margin:10px 0 12px;font-size:20px;color:#111827;text-align:center;">Permintaan
                                Reset Password</h1>
                            <h2 style="margin:8px 0 30px;font-size:16px;color:#646669;text-align:center;">
                                {!! str_replace('@', '<span></span>@', str_replace('.', '<span></span>.', e($email ?? 'example@email.com'))) !!}
                            </h2>
                            <hr>
                            <p style="margin:30px 0 16px;font-size:14px;line-height:1.6;color:#374151;">
                                Halo, kami menerima permintaan untuk mengatur ulang password akun dengan email
                                <strong>{!! str_replace('@', '<span></span>@', str_replace('.', '<span></span>.', e($email ?? 'example@email.com'))) !!}</strong>.
                            </p>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#374151;">
                                Klik tombol di bawah ini untuk membuat password baru. Tautan ini berlaku selama
                                <strong>{{ $expiresInMinutes ?? '60' }} menit</strong>.
                            </p>
                            <a href="{{ $resetUrl ?? "" }}"
                                style="display:flex;padding:12px 24px;background-color:#1f66a8;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;border-radius:6px;justify-content:center;align-items:center;text-align: center;">
                                Reset Password
                            </a>
                            <p style="margin:24px 0 30px;font-size:12px;line-height:1.6;color:#6b7280;text-align:center">
                                Jika kamu tidak merasa meminta reset password, abaikan email ini dan password kamu tidak
                                akan berubah.
                            </p>
                            <hr>
                            <p
                                style="max-width:480px;margin:20px auto 8px;font-size:11px;color:#9ca3af;text-align:center;">
                                &copy; {{ date('Y') }} SMK PGRI 3 Malang. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
