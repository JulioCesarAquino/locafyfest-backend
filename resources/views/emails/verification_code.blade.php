<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocafyFest</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:'Segoe UI',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#d3a307,#f0b429);padding:32px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;letter-spacing:-0.5px;">🎉 LocafyFest</h1>
                            <p style="margin:6px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Sistema de Locação para Eventos</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 40px 32px;">
                            <p style="margin:0 0 8px;font-size:16px;color:#374151;">Olá, <strong>{{ $userName }}</strong> 👋</p>
                            @if($type === 'email_verification')
                                <p style="margin:0 0 24px;font-size:15px;color:#6b7280;line-height:1.6;">
                                    Obrigado por se cadastrar! Use o código abaixo para confirmar seu e-mail:
                                </p>
                            @else
                                <p style="margin:0 0 24px;font-size:15px;color:#6b7280;line-height:1.6;">
                                    Recebemos uma solicitação para redefinir sua senha. Use o código abaixo:
                                </p>
                            @endif
                            <!-- Code box -->
                            <div style="background:#fefce8;border:2px solid #d3a307;border-radius:12px;padding:28px;text-align:center;margin-bottom:24px;">
                                <p style="margin:0 0 8px;font-size:12px;font-weight:600;color:#92400e;text-transform:uppercase;letter-spacing:1px;">Seu código</p>
                                <p style="margin:0;font-size:42px;font-weight:800;letter-spacing:12px;color:#d3a307;font-family:monospace;">{{ $code }}</p>
                            </div>
                            <p style="margin:0;font-size:13px;color:#9ca3af;text-align:center;">
                                ⏱ Este código expira em <strong>15 minutos</strong>.<br>
                                Se você não solicitou isso, ignore este e-mail.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#9ca3af;">© 2026 LocafyFest · Todos os direitos reservados</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
