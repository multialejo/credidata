<?php

namespace App\Livewire;

use App\Models\Cliente;
use Livewire\Component;
use Livewire\WithPagination;

class GestionClientes extends Component
{
    use WithPagination;

    public $busqueda = '';

    public $clienteSeleccionado;

    public $detalleAbierto = false;

    public function buscar(): void
    {
        $this->resetPage();
    }

    public function verDetalle($clienteId): void
    {
        $this->clienteSeleccionado = Cliente::with(['consultas' => fn ($q) => $q->latest('fecha')->take(20), 'recargas' => fn ($q) => $q->latest('fecha')->take(20)])->find($clienteId);
        $this->detalleAbierto = true;
    }

    public function cerrarDetalle(): void
    {
        $this->detalleAbierto = false;
        $this->clienteSeleccionado = null;
    }

    public function render()
    {
        $clientes = Cliente::with('usuario')
            ->when($this->busqueda, fn ($q) => $q->whereHas('usuario', fn ($uq) => $uq->where('nombre', 'like', "%{$this->busqueda}%")->orWhere('email', 'like', "%{$this->busqueda}%"))->orWhere('api_key_prefijo', 'like', "%{$this->busqueda}%"))
            ->withCount('consultas')
            ->withCount('recargas')
            ->paginate(10);

        return view('livewire.gestion-clientes', ['clientes' => $clientes]);
    }
}
