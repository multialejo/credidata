<?php

namespace App\Livewire;

use App\Models\Cliente;
use Livewire\Component;
use Livewire\WithPagination;

class GestionClientes extends Component
{
    use WithPagination;

    public string $buscar = '';

    public string $estado = '';

    public ?int $expandidoId = null;

    protected $queryString = [
        'buscar' => ['except' => ''],
        'estado' => ['except' => ''],
        'expandidoId' => ['except' => null],
    ];

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    public function toggleDetalle(int $clienteId): void
    {
        $this->expandidoId = $this->expandidoId === $clienteId ? null : $clienteId;
    }

    public function resetFilters(): void
    {
        $this->reset(['buscar', 'estado', 'expandidoId']);
        $this->resetPage();
    }

    public function render()
    {
        $clientes = Cliente::query()
            ->with('usuario')
            ->withCount([
                'consultas',
                'consultas as consultas_hoy_count' => fn ($q) => $q->whereDate('fecha', today()),
            ])
            ->when($this->buscar !== '', function ($q) {
                $q->where(function ($q) {
                    $q->whereHas('usuario', function ($qu) {
                        $qu->where('nombre', 'like', "%{$this->buscar}%")
                           ->orWhere('email', 'like', "%{$this->buscar}%");
                    })->orWhere('api_key_prefijo', 'like', "%{$this->buscar}%");
                });
            })
            ->when($this->estado !== '', fn ($q) => $q->whereHas('usuario', fn ($qu) => $qu->where('estado', $this->estado)))
            ->orderBy('id', 'desc')
            ->paginate(10);

        $detalle = null;
        if ($this->expandidoId) {
            $detalle = Cliente::with([
                'consultas' => fn ($q) => $q->latest('fecha')->take(20),
                'recargas' => fn ($q) => $q->latest('fecha')->take(20),
            ])->find($this->expandidoId);
        }

        return view('livewire.gestion-clientes', [
            'clientes' => $clientes,
            'detalle' => $detalle,
        ]);
    }
}