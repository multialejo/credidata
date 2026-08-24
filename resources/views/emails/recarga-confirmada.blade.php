<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recarga acreditada</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f9fafb; margin: 0; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="color: #111827; font-size: 20px; margin: 0 0 16px;">Recarga acreditada</h1>

        <p style="color: #374151; font-size: 14px; line-height: 1.5; margin: 0 0 24px;">
            Acreditamos <strong>{{ number_format($recarga->creditos_obtenidos) }}</strong>
            crédito(s) a tu cuenta por tu pago de
            <strong>${{ number_format((float) $recarga->monto_usd, 2) }}</strong>
            vía <strong>{{ ucfirst($recarga->metodo) }}</strong>.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 0 0 24px;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px; border-bottom: 1px solid #e5e7eb;">Créditos acreditados</td>
                <td style="padding: 8px 0; color: #111827; font-size: 14px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($recarga->creditos_obtenidos) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px; border-bottom: 1px solid #e5e7eb;">Saldo actual</td>
                <td style="padding: 8px 0; color: #111827; font-size: 14px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($saldoActual) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Fecha</td>
                <td style="padding: 8px 0; color: #111827; font-size: 14px; text-align: right;">{{ $recarga->fecha?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}</td>
            </tr>
        </table>

        <p style="color: #6b7280; font-size: 12px; margin: 0;">
            Referencia: {{ $recarga->referencia_externa }}
        </p>
    </div>
</body>
</html>
