<?php

namespace App\Livewire;

use App\Rules\EcuadorianIdentificador;
use App\Services\ColaboracionService;
use Livewire\Component;

class EnviarAporte extends Component
{
    public string $identificador = '';
    public string $tipoDato = 'telefono';
    public string $valor = '';
    public ?string $resultado = null;

    public ?int $recompensaAcreditada = null;

    public function enviar(ColaboracionService $service): void
    {
        $this->validate(['identificador' => ['required', 'string', new EcuadorianIdentificador], 'tipoDato' => ['required', 'in:telefono,email,direccion'], 'valor' => ['required', 'string', 'max:500']]);
        $aporte = $service->registrarAporte(auth()->user()->colaborador, $this->identificador, $this->tipoDato, $this->valor, request()->ip());
        $this->resultado = $aporte->estado;
        $this->recompensaAcreditada = $aporte->recompensa_creditos ? (int) $aporte->recompensa_creditos : null;
        $this->reset(['identificador', 'tipoDato', 'valor']);
    }

    public function render() { return view('livewire.enviar-aporte'); }
}
