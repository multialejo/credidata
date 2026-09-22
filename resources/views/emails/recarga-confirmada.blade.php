<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recarga acreditada</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f7f5ef; margin: 0; padding: 24px; color: #14213d;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px;">
        <p style="color: #3155d9; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; margin: 0 0 10px; text-transform: uppercase;">Credidata · Créditos</p>
        <h1 style="color: #14213d; font-size: 22px; line-height: 1.25; margin: 0 0 16px;">Recarga acreditada</h1>
        <p style="color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
            Acreditamos <strong>{{ number_format($recarga->creditos_obtenidos) }}</strong> crédito(s) a tu cuenta por tu pago de
            <strong>${{ number_format((float) $recarga->monto_usd, 2) }}</strong> vía <strong>{{ ucfirst($recarga->metodo) }}</strong>.
        </p>
        <table style="width: 100%; border-collapse: collapse; margin: 0 0 24px;">
            <tr><td style="padding: 10px 0; color: #64748b; font-size: 14px; border-bottom: 1px solid #e2e8f0;">Créditos acreditados</td><td style="padding: 10px 0; color: #14213d; font-size: 14px; font-weight: 700; text-align: right; border-bottom: 1px solid #e2e8f0;">{{ number_format($recarga->creditos_obtenidos) }}</td></tr>
            <tr><td style="padding: 10px 0; color: #64748b; font-size: 14px; border-bottom: 1px solid #e2e8f0;">Saldo actual</td><td style="padding: 10px 0; color: #14213d; font-size: 14px; font-weight: 700; text-align: right; border-bottom: 1px solid #e2e8f0;">{{ number_format($saldoActual) }}</td></tr>
            <tr><td style="padding: 10px 0; color: #64748b; font-size: 14px;">Fecha</td><td style="padding: 10px 0; color: #14213d; font-size: 14px; text-align: right;">{{ $recarga->fecha?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}</td></tr>
        </table>
        <p style="color: #64748b; font-size: 12px; margin: 0;">Referencia: {{ $recarga->referencia_externa }}</p>
    </div>
</body>
</html>
