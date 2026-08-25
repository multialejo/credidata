<?php

namespace App\Livewire;

use App\Models\ConfigParametro;
use App\Models\LogActividad;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ConfigGeneral extends Component
{
    public $parametros = [];

    public $editando = [];

    public $valores = [];

    public function mount(): void
    {
        $this->parametros = ConfigParametro::orderBy('modulo')->orderBy('clave')->get()->groupBy('modulo');

        foreach ($this->parametros as $modulo => $items) {
            foreach ($items as $item) {
                $key = "{$modulo}.{$item->clave}";
                $this->editando[$key] = false;
                $this->valores[$key] = $item->valor;
            }
        }
    }

    public function toggleEditar(string $modulo, string $clave): void
    {
        $key = "{$modulo}.{$clave}";
        $this->editando[$key] = ! $this->editando[$key];
    }

    public function guardar(string $modulo, string $clave): void
    {
        $key = "{$modulo}.{$clave}";
        $parametro = ConfigParametro::where('modulo', $modulo)->where('clave', $clave)->first();

        if (! $parametro) {
            return;
        }

        $valor = $this->valores[$key];

        if (! is_null(json_decode($valor, true)) || $valor === 'null') {
            // JSON value is valid
        } else {
            $this->dispatch('config-error', message: 'El valor no es JSON válido.');

            return;
        }

        $valorAnterior = $parametro->valor;

        DB::transaction(function () use ($parametro, $valor, $valorAnterior, $modulo, $clave) {
            $parametro->update([
                'valor' => $valor,
                'actualizado_por' => auth()->id(),
                'actualizado_en' => now(),
            ]);

            LogActividad::create([
                'actor_id' => auth()->id(),
                'accion' => 'config.actualizada',
                'detalle' => json_encode([
                    'modulo' => $modulo,
                    'clave' => $clave,
                    'valor_anterior' => $valorAnterior,
                    'valor_nuevo' => $valor,
                ]),
            ]);
        });

        $this->editando[$key] = false;
    }

    public function render()
    {
        return view('livewire.config-general');
    }
}
