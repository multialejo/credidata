<?php

namespace App\Livewire;

use App\Models\ConfigParametro;
use App\Models\LogActividad;
use Closure;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ConfigGeneral extends Component
{
    public ?string $editando = null;

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

        $valorAnterior = $param->valor;
        $valorNuevo = json_encode(json_decode($this->valorEditando));

        DB::transaction(function () use ($valorNuevo, $valorAnterior, $modulo, $clave): void {
            ConfigParametro::where('modulo', $modulo)
                ->where('clave', $clave)
                ->update([
                    'valor' => $valorNuevo,
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
                    'valor_anterior' => json_decode($valorAnterior),
                    'valor_nuevo' => json_decode($valorNuevo),
                ],
                'ip_origen' => request()->ip(),
            ]);
        });

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
