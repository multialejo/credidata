<?php

namespace App\Livewire;

use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use Livewire\Component;

class Recargas extends Component
{
    use InteractsWithFinancieroConfig;

    public const METODOS_DISPONIBLES = ['paypal', 'payphone'];

    public const METODOS_PROXIMAMENTE = ['transferencia'];

    public string $metodo = 'paypal';

    public float $monto = 0;

    public function selectMetodo(string $metodo): void
    {
        if (! in_array($metodo, self::METODOS_DISPONIBLES, true)) {
            return;
        }

        $this->metodo = $metodo;
    }

    protected function rules()
    {
        return [
            'monto' => 'required|numeric|min:'.$this->getRecargaMinimaUsd(),
        ];
    }

    public function updatedMonto(): void
    {
        $this->validateOnly('monto');
        $this->dispatch('monto-updated', $this->monto);
    }

    public function render()
    {
        return view('livewire.recargas', [
            'metodosDisponibles' => self::METODOS_DISPONIBLES,
            'metodosProximamente' => self::METODOS_PROXIMAMENTE,
        ]);
    }
}
