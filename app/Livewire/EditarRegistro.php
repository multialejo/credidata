<?php

namespace App\Livewire;

use App\Models\LogActividad;
use App\Rules\EcuadorianIdentificador;
use Google\Cloud\Firestore\DocumentSnapshot;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Livewire\Component;
use Throwable;

class EditarRegistro extends Component
{
    /**
     * Campos de contacto editables del documento `sujetos/{id}`.
     * Fuera de esta lista no se edita nada: los datos de fuentes oficiales
     * (Dinardap/SRI) y la estructura cruda del documento no se exponen.
     */
    public const CAMPOS_EDITABLES = ['telefonos', 'emails', 'direcciones'];

    /** Identificador (cédula 10 o RUC 13) del sujeto a buscar. */
    public string $identificador = '';

    /** true cuando un documento fue cargado exitosamente para edición. */
    public bool $encontrado = false;

    /** Teléfonos de contacto (uno por línea en la UI). */
    public string $telefonos = '';

    /** Emails de contacto (uno por línea en la UI). */
    public string $emails = '';

    /** Direcciones de contacto (una por línea en la UI). */
    public string $direcciones = '';

    public function buscar(): void
    {
        $this->validate([
            'identificador' => ['required', new EcuadorianIdentificador],
        ]);

        $doc = $this->leerDocumento();

        if ($doc === null || ! $doc->exists()) {
            $this->encontrado = false;
            $this->reset(['telefonos', 'emails', 'direcciones']);
            $this->addError('identificador', 'No se encontró un registro con ese identificador.');

            return;
        }

        $contacto = $doc->data()['contacto'] ?? [];

        $this->telefonos = $this->aTexto($contacto['telefonos'] ?? []);
        $this->emails = $this->aTexto($contacto['emails'] ?? []);
        $this->direcciones = $this->aTexto($contacto['direcciones'] ?? []);
        $this->encontrado = true;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->validate([
            'identificador' => ['required', new EcuadorianIdentificador],
        ]);

        $doc = $this->leerDocumento();

        if ($doc === null || ! $doc->exists()) {
            $this->addError('identificador', 'El registro ya no existe en el sistema.');

            return;
        }

        $data = $doc->data();

        $antes = array_merge(
            ['telefonos' => [], 'emails' => [], 'direcciones' => []],
            $data['contacto'] ?? [],
        );

        $despues = [
            'telefonos' => $this->aLista($this->telefonos),
            'emails' => $this->aLista($this->emails),
            'direcciones' => $this->aLista($this->direcciones),
        ];

        $data['contacto'] = array_merge($antes, $despues);

        Firebase::firestore()
            ->database()
            ->document("sujetos/{$this->identificador}")
            ->set($data);

        $camposEditados = array_values(array_filter(
            self::CAMPOS_EDITABLES,
            fn (string $campo) => ($antes[$campo] ?? []) !== $despues[$campo],
        ));

        LogActividad::create([
            'accion' => 'registro.editado_por_staff',
            'actor_id' => auth()->id(),
            'actor_sistema' => false,
            'detalle' => [
                'identificador' => $this->identificador,
                'campos_editados' => $camposEditados,
                'antes' => $antes,
                'despues' => $despues,
            ],
            'ip_origen' => request()->ip(),
        ]);

        session()->flash('status', 'Registro actualizado correctamente.');
    }

    public function nuevaBusqueda(): void
    {
        $this->reset(['identificador', 'encontrado', 'telefonos', 'emails', 'direcciones']);
        $this->resetValidation();
    }

    /**
     * Lee `sujetos/{identificador}` de Firestore con el mismo patrón de
     * `DinardapService::obtenerCache`: null ante error o documento inexistente.
     */
    private function leerDocumento(): ?DocumentSnapshot
    {
        try {
            return Firebase::firestore()
                ->database()
                ->document("sujetos/{$this->identificador}")
                ->snapshot();
        } catch (Throwable $e) {
            Log::warning('Firestore: error al leer sujeto para edición', [
                'identificador' => $this->identificador,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function aTexto(array $valores): string
    {
        return implode("\n", $valores);
    }

    private function aLista(string $texto): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\n", $texto)),
            fn (string $valor) => $valor !== '',
        ));
    }
}