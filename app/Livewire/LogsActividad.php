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

    public ?int $detalleId = null;

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
        'config.actualizada',
        'recarga.acreditada',
        'recarga.acreditada_manual',
        'recarga.fallida',
        'recarga.rechazada',
        'recarga.rechazada_manual',
        'registro.editado_por_staff',
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

    public function verDetalle(int $id): void
    {
        LogActividad::query()->findOrFail($id);
        $this->detalleId = $id;
        $this->dispatch('open-modal', 'log-detail');
    }

    public function cerrarDetalle(): void
    {
        $this->detalleId = null;
        $this->dispatch('close');
    }

    public function exportarCsv()
    {
        $logs = $this->consultaLogs()->get();

        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['fecha', 'accion', 'actor_email', 'ip_origen', 'detalle_json']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->fecha->format('Y-m-d H:i:s'),
                    $log->accion,
                    $log->actor?->email,
                    $log->ip_origen,
                    json_encode($log->detalle ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            fclose($handle);
        }, 'actividad-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $logs = $this->consultaLogs()->paginate(25);

        $detalleLog = $this->detalleId
            ? LogActividad::with('actor')->find($this->detalleId)
            : null;

        return view('livewire.logs-actividad', [
            'logs' => $logs,
            'accionesConocidas' => self::ACCIONES_CONOCIDAS,
            'detalleLog' => $detalleLog,
        ]);
    }

    private function consultaLogs()
    {
        return LogActividad::query()
            ->with('actor')
            ->when($this->accion !== '', fn ($q) => $q->where('accion', $this->accion))
            ->when($this->fechaDesde !== '', fn ($q) => $q->whereDate('fecha', '>=', $this->fechaDesde))
            ->when($this->fechaHasta !== '', fn ($q) => $q->whereDate('fecha', '<=', $this->fechaHasta))
            ->when($this->actorEmail !== '', function ($q) {
                $q->whereHas('actor', fn ($qu) => $qu->where('email', 'like', "%{$this->actorEmail}%"));
            })
            ->latest('fecha');
    }
}
