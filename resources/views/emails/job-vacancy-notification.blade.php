<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $title ?? 'Lowongan Pekerjaan Baru' }}</title>
</head>

<body style="margin:0;padding:24px;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center">
        <table role="presentation" width="100%"
          style="max-width:480px;background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
          <tr>
            <td style="padding:28px;">
              <a href="{{ config('app.frontend_url') }}" style="text-decoration:none;">
                <img src="https://file.marvfiles.web.id/uploads/be007566-82c0-46d8-ac46-cbbd1fdf00fe.png" alt="SKARIGA"
                  style="width:100px;display:block;margin:0 auto;cursor:pointer;border:0;">
              </a>

              <div style="text-align:center;margin:16px 0 8px;">
                <span
                  style="display:inline-block;padding:4px 12px;border-radius:9999px;font-size:11px;font-weight:bold;color:#1f66a8;background-color:#e0f2fe;border:1px solid #bae6fd;letter-spacing:0.5px;">
                  LOWONGAN KERJA BARU
                </span>
              </div>

              <h1 style="margin:8px 0 6px;font-size:20px;color:#111827;text-align:center;line-height:1.3;">
                {{ $position ?? $title }}
              </h1>
              <h2 style="margin:0 0 20px;font-size:15px;color:#646669;text-align:center;font-weight:normal;">
                {{ $companyName ?? 'Perusahaan Mitra BKI' }}
              </h2>

              <hr style="border:0;border-top:1px solid #e5e7eb;margin:20px 0;">

              <p style="margin:20px 0 12px;font-size:14px;line-height:1.6;color:#374151;">
                Halo <strong>{{ $userName ?? 'Siswa/Alumni' }}</strong>,
              </p>

              <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#374151;">
                {{ $bodyMessage ?? $message }}
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                style="background-color:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:24px;">
                <tr>
                  <td style="padding:16px;">
                    <table role="presentation" width="100%" cellpadding="4" cellspacing="0" style="font-size:13px;">
                      <tr>
                        <td style="color:#64748b;width:35%;vertical-align:top;">Posisi</td>
                        <td style="color:#0f172a;font-weight:bold;vertical-align:top;">{{ $position ?? '-' }}</td>
                      </tr>
                      <tr>
                        <td style="color:#64748b;vertical-align:top;">Perusahaan</td>
                        <td style="color:#0f172a;font-weight:bold;vertical-align:top;">{{ $companyName ?? '-' }}</td>
                      </tr>
                      @if(!empty($quota))
                      <tr>
                        <td style="color:#64748b;vertical-align:top;">Kuota Rekrutmen</td>
                        <td style="color:#0f172a;font-weight:bold;vertical-align:top;">{{ $quota }} Orang</td>
                      </tr>
                      @endif
                    </table>
                  </td>
                </tr>
              </table>

              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                style="margin: 0 auto;">
                <tr>
                  <td align="center">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td align="center" bgcolor="#1f66a8" style="border-radius:6px;">
                          <a href="{!! $actionUrl ?? config('app.frontend_url') . '/student/lowongan' !!}"
                            target="_blank"
                            style="display:inline-block;padding:12px 24px;background-color:#1f66a8;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;border-radius:6px;text-align:center;">
                            Lihat Lowongan Kerja
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <p style="margin:24px 0 20px;font-size:12px;line-height:1.6;color:#6b7280;text-align:center">
                Segera periksa kualifikasi dan ajukan lamaran melalui portal Sistem Informasi Rekrutmen BKI SKARIGA.
              </p>

              <hr style="border:0;border-top:1px solid #e5e7eb;margin:20px 0;">

              <p style="max-width:480px;margin:20px auto 8px;font-size:11px;color:#9ca3af;text-align:center;">
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
