<?php

namespace App\Livewire;

use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use Livewire\Component;

class Recargas extends Component
{
    use InteractsWithFinancieroConfig;

    public const METODOS_DISPONIBLES = ['paypal', 'payphone', 'tarjeta'];

    public const METODOS_PROXIMAMENTE = ['transferencia'];

    public const MONTOS_SUGERIDOS = [10, 25, 50, 100];

    public string $metodo = 'paypal';

    public float $monto = 0;

    public function selectMetodo(string $metodo): void
    {
        if (! in_array($metodo, self::METODOS_DISPONIBLES, true)) {
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

        return view('livewire.recargas', [
            'metodosDisponibles' => self::METODOS_DISPONIBLES,
            'metodosProximamente' => self::METODOS_PROXIMAMENTE,
            'montosSugeridos' => array_values(array_filter(
                self::MONTOS_SUGERIDOS,
                fn (int $monto) => $monto >= $recargaMinimaUsd,
            )),
            'recargaMinimaUsd' => $recargaMinimaUsd,
            'saldoCreditos' => (int) auth()->user()->cliente->saldo_creditos,
        ]);
    }
}
