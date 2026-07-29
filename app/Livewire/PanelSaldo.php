<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Consulta;
use App\Models\Recarga;

class PanelSaldo extends Component
{
    public $saldo;
    public $ultimosMovimientos;

    public function mount()
    {
        $cliente = auth()->user()->cliente;
        $this->saldo = $cliente->saldo_creditos;

        $consultas = Consulta::where('cliente_id', $cliente->id)
            ->latest('fecha')->take(5)->get()
            ->map(fn($c) => [
                'tipo' => 'consulta',
                'descripcion' => "Consulta {$c->tipo}: {$c->identificador}",
                'monto' => (int) -$c->creditos_gastados,
                'fecha' => $c->fecha,
            ]);

        $recargas = Recarga::where('cliente_id', $cliente->id)
            ->latest('fecha')->take(5)->get()
            ->map(fn($r) => [
                'tipo' => 'recarga',
                'descripcion' => "Recarga vía {$r->metodo}",
                'monto' => (int) $r->creditos_obtenidos,
                'fecha' => $r->fecha,
                'estado' => $r->estado,
            ]);

        $this->ultimosMovimientos = $consultas->toBase()->concat($recargas)
            ->sortByDesc('fecha')->take(5)->values();
    }

    public function render()
    {
        return view('livewire.panel-saldo');
    }
}
