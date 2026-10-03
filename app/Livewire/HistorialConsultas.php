<?php

namespace App\Livewire;

use App\Models\Consulta;
use Livewire\Component;
use Livewire\WithPagination;

class HistorialConsultas extends Component
{
    use WithPagination;

    public $filtroFechaDesde = '';

    public $filtroFechaHasta = '';

    public $filtroTipo = '';

    public $filtroResultado = '';

    public function render()
    {
        $query = $this->consultaQuery();
        $total = (clone $query)->count();
        $exitosas = (clone $query)->where('exitosa', true)->count();

        return view('livewire.historial-consultas', [
            'consultas' => $query->latest('fecha')->paginate(15),
            'estadisticas' => [
                'total' => $total,
                'creditos' => (clone $query)->sum('creditos_gastados'),
                'tasa_exito' => $total > 0 ? round(($exitosas / $total) * 100) : 0,
            ],
        ]);
    }

    private function consultaQuery()
    {
        $cliente = auth()->user()->cliente;
        $query = Consulta::with('apiKey')->where('cliente_id', $cliente->id);

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

        return $query;
    }

    public function exportarCsv()
    {
        $cliente = auth()->user()->cliente;
        $consultas = Consulta::with('apiKey')->where('cliente_id', $cliente->id)
            ->latest('fecha')->get();

        $csv = "ID,Fecha,Tipo,Identificador,API Key,Prefijo API Key,Creditos,Exitosa,IP\n";
        foreach ($consultas as $c) {
            $csv .= "{$c->id},{$c->fecha->format('Y-m-d H:i:s')},{$c->tipo},";
            $csv .= "{$c->identificador},";
            $csv .= ($c->apiKey?->nombre ?? 'Sin API key asociada').",";
            $csv .= ($c->apiKey ? 'cd_sk_'.$c->apiKey->prefijo : '').",";
            $csv .= "{$c->creditos_gastados},";
            $csv .= ($c->exitosa ? 'Si' : 'No').",{$c->ip_origen}\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'historial-consultas-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function resetFilters()
    {
        $this->reset(['filtroFechaDesde', 'filtroFechaHasta', 'filtroTipo', 'filtroResultado']);
    }
}
