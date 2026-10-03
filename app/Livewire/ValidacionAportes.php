<?php

namespace App\Livewire;

use App\Models\Aporte;
use App\Services\ColaboracionService;
use Livewire\Component;
use Livewire\WithPagination;

class ValidacionAportes extends Component
{
    use WithPagination;

    public ?int $detalleId = null;

    public string $comentario = '';

    public string $valorActual = '';

    public function ver(int $id, ColaboracionService $service): void
    {
        if ($this->detalleId === $id) {
            $this->cerrarDetalle();

            return;
        }

        $this->detalleId = $id;
        $this->comentario = '';
        $aporte = Aporte::findOrFail($id);
        $actual = $service->valorActual($aporte);
        $this->valorActual = is_array($actual) ? implode(', ', $actual) : (string) $actual;
        $this->dispatch('open-modal', 'contribution-detail');
    }

    public function cerrarDetalle(): void
    {
        $this->detalleId = null;
        $this->comentario = '';
        $this->valorActual = '';
        $this->dispatch('close');
    }

    public function decidir(int $id, bool $aprobar, ColaboracionService $service): void
    {
        $this->validate(['comentario' => ['nullable', 'string', 'max:500']]);
        $service->decidir(Aporte::findOrFail($id), auth()->user(), $aprobar, $this->comentario ?: null, request()->ip());
        $this->cerrarDetalle();
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.validacion-aportes', ['aportes' => Aporte::with('colaborador.usuario')->where('estado', 'pendiente')->latest('fecha')->paginate(15)]);
    }
}
