<?php

namespace App\Livewire;

use App\Models\LogActividad;
use Livewire\Component;
use Livewire\WithPagination;

class LogsActividad extends Component
{
    use WithPagination;

    public $accion = '';

    public $actor_email = '';

    public $fecha_desde;

    public $fecha_hasta;

    public function buscar(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = LogActividad::with('actor')
            ->when($this->accion, fn ($q) => $q->where('accion', $this->accion))
            ->when($this->actor_email, fn ($q) => $q->whereHas('actor', fn ($u) => $u->where('email', 'like', "%{$this->actor_email}%")))
            ->when($this->fecha_desde, fn ($q) => $q->where('fecha', '>=', $this->fecha_desde))
            ->when($this->fecha_hasta, fn ($q) => $q->where('fecha', '<=', $this->fecha_hasta.' 23:59:59'))
            ->latest('fecha')
            ->paginate(25);

        return view('livewire.logs-actividad', ['logs' => $logs]);
    }
}
