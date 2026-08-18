<?php

namespace App\Livewire;

use App\Models\ConfigParametro;
use App\Models\LogActividad;
use Closure;
use Livewire\Component;

class ConfigGeneral extends Component
{
    /** Clave "modulo.clave" de la fila en edición, o null si ninguna. */
    public ?string $editando = null;

    /** Valor JSON que se está editando. */
    public string $valorEditando = '';

    public function iniciarEdicion(string $modulo, string $clave): void
    {
        $param = ConfigParametro::where('modulo', $modulo)
            ->where('clave', $clave)
            ->firstOrFail();

        $this->editando = "{$modulo}.{$clave}";
        $this->valorEditando = $param->valor;
    }

    public function cancelarEdicion(): void
    {
        $this->editando = null;
        $this->valorEditando = '';
    }

    public function guardar(string $modulo, string $clave): void
    {
        $this->validate([
            'valorEditando' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                $decoded = json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail('El valor debe ser JSON válido.');

                    return;
                }

                if (! is_scalar($decoded)) {
                    $fail('El valor debe ser un escalar (string, int, float o bool).');
                }
            }],
        ]);

        $param = ConfigParametro::where('modulo', $modulo)
            ->where('clave', $clave)
            ->firstOrFail();

        $valorAnterior = json_decode($param->valor);
        $valorNuevo = json_decode($this->valorEditando);

        // Usamos update() por query builder (no $model->save()) porque la PK es
        // compuesta ['modulo','clave'] y este es el patrón probado en el proyecto.
        ConfigParametro::where('modulo', $modulo)
            ->where('clave', $clave)
            ->update([
                'valor' => json_encode($valorNuevo),
                'actualizado_por' => auth()->user()->staff->id,
                'actualizado_en' => now(),
            ]);

        LogActividad::create([
            'accion' => 'config.actualizada',
            'actor_id' => auth()->id(),
            'actor_sistema' => false,
            'detalle' => [
                'modulo' => $modulo,
                'clave' => $clave,
                'valor_anterior' => $valorAnterior,
                'valor_nuevo' => $valorNuevo,
            ],
            'ip_origen' => request()->ip(),
        ]);

        $this->cancelarEdicion();

        session()->flash('status', 'Parámetro actualizado correctamente.');
    }

    public function render()
    {
        $parametros = ConfigParametro::query()
            ->orderBy('modulo')
            ->orderBy('clave')
            ->get()
            ->groupBy('modulo');

        return view('livewire.config-general', ['parametros' => $parametros]);
    }
}