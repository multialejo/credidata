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

        if ($this->expandidoId !== null) {
            $this->dispatch('open-modal', 'client-detail');
        }
    }

    public function cerrarDetalle(): void
    {
        $this->expandidoId = null;
        $this->dispatch('close');
    }

    public function exportarCsv()
    {
        $clientes = $this->consultaClientes()
            ->with([
                'usuario',
                'apiKeys' => fn ($q) => $q->latest('id'),
                'consultas' => fn ($q) => $q->latest('fecha'),
                'recargas' => fn ($q) => $q->latest('fecha'),
            ])
            ->get();

        return response()->streamDownload(function () use ($clientes): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'nombre', 'saldo_creditos', 'estado', 'api_keys_count', 'consultas_hoy_count', 'consultas_count', 'detalle_json']);

            foreach ($clientes as $cliente) {
                $detalle = [
                    'api_keys' => $cliente->apiKeys->map(fn ($apiKey) => [
                        'nombre' => $apiKey->nombre,
                        'estado' => $apiKey->revocada ? 'revocada' : 'activa',
                        'prefijo' => $apiKey->prefijo,
                        'ultimo_uso' => $apiKey->ultimo_uso_en?->toISOString(),
                        'permisos' => $apiKey->alcance ?? [],
                        'ips_permitidas' => $apiKey->ips_permitidas ?? [],
                    ])->values()->all(),
                    'consultas_recientes' => $cliente->consultas->take(20)->map(fn ($consulta) => [
                        'fecha' => $consulta->fecha?->toISOString(),
                        'tipo' => $consulta->tipo,
                        'identificador' => $consulta->identificador,
                        'creditos_gastados' => $consulta->creditos_gastados,
                    ])->values()->all(),
                    'recargas_recientes' => $cliente->recargas->take(20)->map(fn ($recarga) => [
                        'fecha' => $recarga->fecha?->toISOString(),
                        'metodo' => $recarga->metodo,
                        'monto_usd' => (float) $recarga->monto_usd,
                        'creditos_obtenidos' => $recarga->creditos_obtenidos,
                        'estado' => $recarga->estado->value ?? $recarga->estado,
                    ])->values()->all(),
                ];

                fputcsv($handle, [
                    $cliente->usuario?->email,
                    $cliente->usuario?->nombre,
                    $cliente->saldo_creditos,
                    $cliente->usuario?->estado,
                    $cliente->api_keys_count,
                    $cliente->consultas_hoy_count ?? 0,
                    $cliente->consultas_count ?? 0,
                    json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            fclose($handle);
        }, 'clientes-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function resetFilters(): void
    {
        $this->reset(['buscar', 'estado', 'expandidoId']);
        $this->resetPage();
    }

    public function render()
    {
        $clientes = $this->consultaClientes()->paginate(10);

        $detalle = null;
        if ($this->expandidoId) {
            $detalle = Cliente::with([
                'apiKeys' => fn ($q) => $q->latest('id'),
                'consultas' => fn ($q) => $q->latest('fecha')->take(20),
                'recargas' => fn ($q) => $q->latest('fecha')->take(20),
            ])->find($this->expandidoId);
        }

        return view('livewire.gestion-clientes', [
            'clientes' => $clientes,
            'detalle' => $detalle,
        ]);
    }

    private function consultaClientes()
    {
        return Cliente::query()
            ->with('usuario')
            ->withCount([
                'apiKeys',
                'consultas',
                'consultas as consultas_hoy_count' => fn ($q) => $q->whereDate('fecha', today()),
            ])
            ->when($this->buscar !== '', function ($q) {
                $q->where(function ($q) {
                    $q->whereHas('usuario', function ($qu) {
                        $qu->where('nombre', 'like', "%{$this->buscar}%")
                            ->orWhere('email', 'like', "%{$this->buscar}%");
                    })->orWhereHas('apiKeys', fn ($keyQuery) => $keyQuery->where('prefijo', 'like', "%{$this->buscar}%"));
                });
            })
            ->when($this->estado !== '', fn ($q) => $q->whereHas('usuario', fn ($qu) => $qu->where('estado', $this->estado)))
            ->orderBy('id', 'desc');
    }
}
