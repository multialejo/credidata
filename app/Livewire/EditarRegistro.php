<?php

namespace App\Livewire;

use App\Models\LogActividad;
use App\Services\FirebaseService;
use Livewire\Component;

class EditarRegistro extends Component
{
    public $identificador = '';

    public $documento = null;

    public $camposEditables = [
        'telefonos',
        'emails',
        'direccion',
    ];

    public $valores = [];

    public $guardado = false;

    public function buscar(): void
    {
        $this->guardado = false;
        $this->documento = null;
        $this->valores = [];

        $firebase = app(FirebaseService::class);
        $doc = $firebase->firestore()->collection('sujetos')->document($this->identificador)->snapshot();

        if (! $doc->exists()) {
            $this->dispatch('registro-no-encontrado', identificador: $this->identificador);

            return;
        }

        $data = $doc->data();
        $this->documento = $data;

        foreach ($this->camposEditables as $campo) {
            $this->valores[$campo] = data_get($data, $campo, '');
        }
    }

    public function guardar(): void
    {
        $antes = [];
        $despues = [];

        foreach ($this->camposEditables as $campo) {
            $antes[$campo] = data_get($this->documento, $campo, '');
            $despues[$campo] = $this->valores[$campo];
        }

        $firebase = app(FirebaseService::class);
        $firebase->firestore()->collection('sujetos')->document($this->identificador)->set($this->valores, ['merge' => true]);

        LogActividad::create([
            'actor_id' => auth()->id(),
            'accion' => 'registro.editado_por_staff',
            'detalle' => json_encode([
                'identificador' => $this->identificador,
                'campos_editados' => array_keys($antes),
                'antes' => $antes,
                'despues' => $despues,
            ]),
        ]);

        $this->guardado = true;
        $this->documento = array_merge($this->documento, $this->valores);
    }

    public function render()
    {
        return view('livewire.editar-registro');
    }
}
