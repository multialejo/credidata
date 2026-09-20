<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ConfigParametro;

trait InteractsWithFinancieroConfig
{
    public const METODOS_PAGO_CLAVE = [
        'paypal' => 'metodoPaypalHabilitado',
        'payphone' => 'metodoPayphoneHabilitado',
        'tarjeta' => 'metodoTarjetaHabilitado',
        'transferencia' => 'metodoTransferenciaHabilitado',
    ];

    public const METODOS_PAGABLES = ['paypal', 'payphone', 'tarjeta'];

    public const METODOS_NO_PAGABLES = ['transferencia'];

    public const TODOS_LOS_METODOS = ['paypal', 'payphone', 'tarjeta', 'transferencia'];

    protected function getTasaCambioUsdCreditos(): int
    {
        $param = ConfigParametro::where('modulo', 'financiero')
            ->where('clave', 'tasaCambioUsdCreditos')
            ->first();

        return $param ? (int) json_decode($param->valor) : 10;
    }

    protected function getRecargaMinimaUsd(): float
    {
        $param = ConfigParametro::where('modulo', 'financiero')
            ->where('clave', 'recargaMinimaUsd')
            ->first();

        return $param ? (float) json_decode($param->valor) : 5.00;
    }

    protected function isMontoValido(float $monto): bool
    {
        return $monto >= $this->getRecargaMinimaUsd();
    }

    protected function getMetodosPagoHabilitados(): array
    {
        $habilitados = [];

        foreach (self::METODOS_PAGO_CLAVE as $codigo => $clave) {
            $param = ConfigParametro::where('modulo', 'recargas')
                ->where('clave', $clave)
                ->first();

            $valor = $param ? json_decode($param->valor) : true;

            if ($valor) {
                $habilitados[] = $codigo;
            }
        }

        return $habilitados;
    }

    protected function getMetodosPagoPagablesHabilitados(): array
    {
        return array_values(array_intersect($this->getMetodosPagoHabilitados(), self::METODOS_PAGABLES));
    }

    protected function isMetodoPagoHabilitado(string $codigo): bool
    {
        return in_array($codigo, $this->getMetodosPagoHabilitados(), true);
    }
}
