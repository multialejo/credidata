<?php

namespace App\Livewire;

use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use Livewire\Component;

class Recargas extends Component
{
    use InteractsWithFinancieroConfig;

    public const MONTOS_SUGERIDOS = [10, 25, 50, 100];

    public string $metodo = 'paypal';

    public string $monto = '';

    public bool $mostrarModalTransferencia = false;

    public function selectMetodo(string $metodo): void
    {
        $pagables = $this->getMetodosPagoPagablesHabilitados();

        if (! in_array($metodo, $pagables, true)) {
            return;
        }

        $this->metodo = $metodo;
    }

    public function abrirModalTransferencia(): void
    {
        if (! $this->isMontoValido($this->montoFloat())) {
            return;
        }

        $this->mostrarModalTransferencia = true;
    }

    public function cerrarModalTransferencia(): void
    {
        $this->mostrarModalTransferencia = false;
    }

    public function selectMonto(float $monto): void
    {
        $this->monto = (string) $monto;
        $this->dispatch('monto-updated', $monto);
    }

    public function montoFloat(): float
    {
        return is_numeric($this->monto) ? (float) $this->monto : 0.0;
    }

    public function getCreditosEstimadosProperty(): int
    {
        $monto = $this->montoFloat();

        return $monto > 0
            ? (int) floor($monto * $this->getTasaCambioUsdCreditos())
            : 0;
    }

    public function getMontoValidoProperty(): bool
    {
        $monto = $this->montoFloat();

        return $monto > 0 && $this->isMontoValido($monto);
    }

    protected function rules()
    {
        return [
            'monto' => 'required|numeric|min:'.$this->getRecargaMinimaUsd(),
        ];
    }

    public function updatedMonto(): void
    {
        $this->dispatch('monto-updated', $this->montoFloat());
        $this->validateOnly('monto');
    }

    public function render()
    {
        $recargaMinimaUsd = $this->getRecargaMinimaUsd();
        $habilitados = $this->getMetodosPagoHabilitados();
        $pagables = $this->getMetodosPagoPagablesHabilitados();
        $datosTransferencia = $this->getDatosTransferencia();

        if (! in_array($this->metodo, $pagables, true)) {
            $this->metodo = $pagables[0] ?? '';
        }

        $configs = [
            'paypal' => ['label' => 'PayPal', 'detail' => 'Pago seguro', 'icon' => 'wallet'],
            'payphone' => ['label' => 'PayPhone', 'detail' => 'Billetera PayPhone', 'icon' => 'device-phone-mobile'],
            'tarjeta' => ['label' => 'Tarjeta', 'detail' => 'Débito o crédito', 'icon' => 'credit-card'],
            'transferencia' => ['label' => 'Transferencia', 'detail' => $datosTransferencia ? 'Depósito bancario' : 'No disponible', 'icon' => 'arrows-right-left'],
        ];

        $metodosHabilitados = [];
        foreach ($habilitados as $codigo) {
            $config = $configs[$codigo];
            $config['pagable'] = in_array($codigo, $pagables, true);
            $metodosHabilitados[$codigo] = $config;
        }

        return view('livewire.recargas', [
            'metodosDisponibles' => $pagables,
            'metodosHabilitados' => $metodosHabilitados,
            'montosSugeridos' => array_values(array_filter(
                self::MONTOS_SUGERIDOS,
                fn (int $monto) => $monto >= $recargaMinimaUsd,
            )),
            'recargaMinimaUsd' => $recargaMinimaUsd,
            'recargaMaximaPayphoneUsd' => $this->getRecargaMaximaPayphoneUsd(),
            'datosTransferencia' => $datosTransferencia,
            'saldoCreditos' => (int) auth()->user()->cliente->saldo_creditos,
        ]);
    }
}
