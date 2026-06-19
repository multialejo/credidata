<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Consulta;

class HistorialConsultas extends Component
{
    use WithPagination;

    public $filtroFechaDesde = '';
    public $filtroFechaHasta = '';
    public $filtroTipo = '';
    public $filtroResultado = '';

    public function render()
    {
        $cliente = auth()->user()->cliente;

        $query = Consulta::where('cliente_id', $cliente->uid);

        if ($this->filtroFechaDesde) {
            $query->whereDate('fecha', '>=', $this->filtroFechaDesde);
        }
        if ($this->filtroFechaHasta) {
            $query->whereDate('fecha', '<=', $this->filtroFechaHasta);
        }
        if ($this->filtroTipo) {
            $query->where('tipo', $this->filtroTipo);
        }
        if ($this->filtroResultado !== '') {
            $query->where('exitosa', $this->filtroResultado === 'exito');
        }

        return view('livewire.historial-consultas', [
            'consultas' => $query->latest('fecha')->paginate(15),
        ]);
    }

    public function exportarCsv()
    {
        $cliente = auth()->user()->cliente;
        $consultas = Consulta::where('cliente_id', $cliente->uid)
            ->latest('fecha')->get();

        $csv = "ID,Fecha,Tipo,Identificador,Creditos,Exitosa,IP\n";
        foreach ($consultas as $c) {
            $csv .= "{$c->id},{$c->fecha->format('Y-m-d H:i:s')},{$c->tipo},";
            $csv .= "{$c->identificador},{$c->creditos_gastados},";
            $csv .= ($c->exitosa ? 'Si' : 'No') . ",{$c->ip_origen}\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'historial-consultas-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function resetFilters()
    {
        $this->reset(['filtroFechaDesde', 'filtroFechaHasta', 'filtroTipo', 'filtroResultado']);
    }
}
