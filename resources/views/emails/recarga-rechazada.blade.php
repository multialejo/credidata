<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu recarga fue rechazada</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f9fafb; margin: 0; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="color: #111827; font-size: 20px; margin: 0 0 16px;">Tu recarga fue rechazada</h1>

        <p style="color: #374151; font-size: 14px; line-height: 1.5; margin: 0 0 24px;">
            Hola <strong>{{ $recarga->cliente?->usuario?->nombre ?? 'cliente' }}</strong>,
            lamentamos informarte que tu solicitud de recarga no pudo ser procesada.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 0 0 24px;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px; border-bottom: 1px solid #e5e7eb;">Método</td>
                <td style="padding: 8px 0; color: #111827; font-size: 14px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ ucfirst($recarga->metodo) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px; border-bottom: 1px solid #e5e7eb;">Monto</td>
                <td style="padding: 8px 0; color: #111827; font-size: 14px; text-align: right; border-bottom: 1px solid #e5e7eb;">${{ number_format((float) $recarga->monto_usd, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px; border-bottom: 1px solid #e5e7eb;">Créditos solicitados</td>
                <td style="padding: 8px 0; color: #111827; font-size: 14px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($recarga->creditos_obtenidos) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Fecha</td>
                <td style="padding: 8px 0; color: #111827; font-size: 14px; text-align: right;">{{ $recarga->fecha?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}</td>
            </tr>
        </table>

        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px; padding: 16px; margin: 0 0 24px;">
            <p style="color: #92400e; font-size: 12px; font-weight: bold; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.05em;">
                Motivo del rechazo
            </p>
            <p style="color: #78350f; font-size: 14px; line-height: 1.5; margin: 0;">
                {{ $motivo }}
            </p>
        </div>

        <p style="color: #374151; font-size: 14px; line-height: 1.5; margin: 0 0 24px;">
            Si crees que se trata de un error o necesitas más información, por favor contacta a nuestro equipo de soporte y con gusto te ayudaremos.
        </p>

        <p style="color: #6b7280; font-size: 12px; margin: 0;">
            Referencia: {{ $recarga->referencia_externa }}
        </p>
    </div>
</body>
</html>
