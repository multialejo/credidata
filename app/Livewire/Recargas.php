<?php

namespace App\Livewire;

use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use Livewire\Component;

class Recargas extends Component
{
    use InteractsWithFinancieroConfig;

    public const MONTOS_SUGERIDOS = [10, 25, 50, 100];

    public string $metodo = 'paypal';

    public float $monto = 0;

    public function selectMetodo(string $metodo): void
    {
        $pagables = $this->getMetodosPagoPagablesHabilitados();

        if (! in_array($metodo, $pagables, true)) {
            return;
        }

        $this->metodo = $metodo;
    }

    public function selectMonto(float $monto): void
    {
        $this->monto = $monto;
        $this->dispatch('monto-updated', $monto);
    }

    public function getCreditosEstimadosProperty(): int
    {
        return isset($this->monto)
            ? (int) floor($this->monto * $this->getTasaCambioUsdCreditos())
            : 0;
    }

    public function getMontoValidoProperty(): bool
    {
        return isset($this->monto) && $this->isMontoValido($this->monto);
    }

    protected function rules()
    {
        return [
            'monto' => 'required|numeric|min:'.$this->getRecargaMinimaUsd(),
        ];
    }

    public function updatedMonto(): void
    {
        $this->dispatch('monto-updated', (float) ($this->monto ?? 0));
        $this->validateOnly('monto');
    }

    public function render()
    {
        $recargaMinimaUsd = $this->getRecargaMinimaUsd();
        $habilitados = $this->getMetodosPagoHabilitados();
        $pagables = $this->getMetodosPagoPagablesHabilitados();

        if (! in_array($this->metodo, $pagables, true)) {
            $this->metodo = $pagables[0] ?? '';
        }

        $configs = [
            'paypal' => ['label' => 'PayPal', 'detail' => 'Pago seguro', 'icon' => 'wallet'],
            'payphone' => ['label' => 'PayPhone', 'detail' => 'Billetera PayPhone', 'icon' => 'device-phone-mobile'],
            'tarjeta' => ['label' => 'Tarjeta', 'detail' => 'Débito o crédito', 'icon' => 'credit-card'],
            'transferencia' => ['label' => 'Transferencia', 'detail' => 'Próximamente', 'icon' => 'arrows-right-left'],
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
            'saldoCreditos' => (int) auth()->user()->cliente->saldo_creditos,
        ]);
    }
}
