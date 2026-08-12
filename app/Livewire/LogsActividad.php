<?php

namespace App\Livewire;

use App\Models\LogActividad;
use Livewire\Component;
use Livewire\WithPagination;

class LogsActividad extends Component
{
    use WithPagination;

    public string $accion = '';

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public string $actorEmail = '';

    /**
     * Acciones conocidas en el sistema. Mantener sincronizadas con los
     * `LogActividad::create(['accion' => ...])` repartidos por el código.
     */
    private const ACCIONES_CONOCIDAS = [
        'API_KEY_GENERADA',
        'API_KEY_REVOCADA',
        'AUTH_TOKEN_EMITIDO',
        'CLIENTE_REGISTRADO',
        'CONSULTA_CEDULA',
        'CREDITOS_ASIGNADOS',
        'STAFF_CREADO',
        'recarga.acreditada',
        'recarga.fallida',
        'recarga.rechazada',
    ];

    protected $queryString = [
        'accion' => ['except' => ''],
        'fechaDesde' => ['except' => ''],
        'fechaHasta' => ['except' => ''],
        'actorEmail' => ['except' => ''],
    ];

    public function updatingAccion(): void
    {
        $this->resetPage();
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage();
    }

    public function updatingActorEmail(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['accion', 'fechaDesde', 'fechaHasta', 'actorEmail']);
        $this->resetPage();
    }

    public function render()
    {
        $logs = LogActividad::query()
            ->with('actor')
            ->when($this->accion !== '', fn ($q) => $q->where('accion', $this->accion))
            ->when($this->fechaDesde !== '', fn ($q) => $q->whereDate('fecha', '>=', $this->fechaDesde))
            ->when($this->fechaHasta !== '', fn ($q) => $q->whereDate('fecha', '<=', $this->fechaHasta))
            ->when($this->actorEmail !== '', function ($q) {
                $q->whereHas('actor', fn ($qu) => $qu->where('email', 'like', "%{$this->actorEmail}%"));
            })
            ->latest('fecha')
            ->paginate(25);

        return view('livewire.logs-actividad', [
            'logs' => $logs,
            'accionesConocidas' => self::ACCIONES_CONOCIDAS,
        ]);
    }
}
