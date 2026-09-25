<?php

namespace App\Livewire;

use App\Models\ConfigParametro;
use App\Services\ColaboracionService;
use Livewire\Component;

class ActivarColaborador extends Component
{
    public bool $aceptaTerminos = false;

    public function activar(ColaboracionService $service): void
    {
        $this->validate(['aceptaTerminos' => ['accepted']]);
        $service->activar(auth()->user(), request()->ip());
        session()->flash('status', 'Tu perfil de colaborador está activo.');
    }

    public function render()
    {
        $colaborador = auth()->user()->colaborador;
        $pendientes = 0;
        $aportesHoy = 0;
        $limiteDiario = 10;

        if ($colaborador) {
            $pendientes = $colaborador->aportes()->where('estado', 'pendiente')->count();
            $aportesHoy = $colaborador->aportes()->whereBetween('fecha', [now()->startOfDay(), now()])->count();
            $valorLimite = ConfigParametro::where('modulo', 'colaboracion')
                ->where('clave', 'limiteDiarioPorColaborador')
                ->value('valor');
            $limiteDecodificado = json_decode($valorLimite ?? 'null', true);
            $limiteDiario = is_numeric($limiteDecodificado) && $limiteDecodificado >= 0
                ? (int) $limiteDecodificado
                : 10;
        }

        return view('livewire.activar-colaborador', [
            'colaborador' => $colaborador,
            'terminos' => ColaboracionService::TERMINOS,
            'version' => ColaboracionService::TERMINOS_VERSION,
            'pendientes' => $pendientes,
            'aportesHoy' => $aportesHoy,
            'limiteDiario' => $limiteDiario,
        ]);
    }
}
