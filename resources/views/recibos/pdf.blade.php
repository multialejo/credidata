<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo de recarga #{{ $recarga->id }}</title>
    <style>
        @page { margin: 48px; }
        body { color: #14213d; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .brand { color: #3155d9; font-size: 13px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 26px; margin: 12px 0 4px; }
        .muted { color: #475569; }
        .receipt-number { color: #475569; font-size: 11px; }
        .rule { border: 0; border-top: 1px solid #e2e8f0; margin: 28px 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 12px 8px; text-align: left; }
        th { color: #475569; font-size: 10px; text-transform: uppercase; }
        .amount { color: #14213d; font-size: 20px; font-weight: bold; text-align: right; }
        .status { color: #047857; font-weight: bold; }
        .footer { color: #475569; font-size: 10px; margin-top: 44px; }
    </style>
</head>
<body>
    <div class="brand">Credidata</div>
    <h1>Recibo de recarga</h1>
    <div class="receipt-number">Recibo #{{ $recarga->id }}</div>

    <hr class="rule">

    <table>
        <tbody>
            <tr>
                <th>Cliente</th>
                <td>{{ $cliente->nombre }}</td>
            </tr>
            <tr>
                <th>Fecha</th>
                <td>{{ $recarga->fecha->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Método de pago</th>
                <td>{{ ucfirst($recarga->metodo) }}</td>
            </tr>
            <tr>
                <th>Estado</th>
                <td class="status">{{ $recarga->estado->label() }}</td>
            </tr>
            <tr>
                <th>Créditos acreditados</th>
                <td>{{ number_format($recarga->creditos_obtenidos, 0) }}</td>
            </tr>
            <tr>
                <th>Total pagado</th>
                <td class="amount">{{ $recarga->monto_usd > 0 ? '$'.number_format($recarga->monto_usd, 2).' USD' : '—' }}</td>
            </tr>
        </tbody>
    </table>

    <p class="footer">Comprobante generado el {{ now()->format('d/m/Y H:i') }}.</p>
</body>
</html>
