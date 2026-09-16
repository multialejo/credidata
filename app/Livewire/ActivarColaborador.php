<?php

namespace App\Livewire;

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
        return view('livewire.activar-colaborador', ['colaborador' => auth()->user()->colaborador, 'terminos' => ColaboracionService::TERMINOS, 'version' => ColaboracionService::TERMINOS_VERSION]);
    }
}
