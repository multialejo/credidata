<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva transferencia pendiente</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f7f5ef; margin: 0; padding: 24px; color: #14213d;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px;">
        <p style="color: #3155d9; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; margin: 0 0 10px; text-transform: uppercase;">Credidata · Créditos</p>
        <h1 style="color: #14213d; font-size: 22px; line-height: 1.25; margin: 0 0 16px;">Transferencia pendiente de validar</h1>
        <p style="color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">Un cliente envió un comprobante de transferencia. Revisa los datos y valida el pago desde el panel administrativo.</p>
        <table style="width: 100%; border-collapse: collapse; margin: 0 0 24px;">
            <tr><td style="padding: 10px 0; color: #64748b; font-size: 14px; border-bottom: 1px solid #e2e8f0;">Cliente</td><td style="padding: 10px 0; color: #14213d; font-size: 14px; text-align: right; border-bottom: 1px solid #e2e8f0;">{{ $recarga->cliente?->usuario?->nombre }}</td></tr>
            <tr><td style="padding: 10px 0; color: #64748b; font-size: 14px; border-bottom: 1px solid #e2e8f0;">Correo</td><td style="padding: 10px 0; color: #14213d; font-size: 14px; text-align: right; border-bottom: 1px solid #e2e8f0;">{{ $recarga->cliente?->usuario?->email }}</td></tr>
            <tr><td style="padding: 10px 0; color: #64748b; font-size: 14px; border-bottom: 1px solid #e2e8f0;">Monto</td><td style="padding: 10px 0; color: #14213d; font-size: 14px; text-align: right; border-bottom: 1px solid #e2e8f0;">${{ number_format((float) $recarga->monto_usd, 2) }}</td></tr>
            <tr><td style="padding: 10px 0; color: #64748b; font-size: 14px;">Referencia</td><td style="padding: 10px 0; color: #14213d; font-size: 14px; text-align: right;">{{ $recarga->referencia_externa }}</td></tr>
        </table>
    </div>
</body>
</html>
