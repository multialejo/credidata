<?php

namespace App\Livewire;

use App\Models\LogActividad;
use App\Services\ApiKeyService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GestionApiKey extends Component
{
    public string $nombre = '';

    public string $ips = '';

    public string $ipDetectada = '';

    public array $scopes = ['consulta:cedula', 'consulta:ruc'];

    public bool $mostrarFormulario = false;

    public ?int $editandoId = null;

    public ?string $nuevaKey = null;

    public string $nombreNuevaKey = '';

    public function mount(): void
    {
        $this->ipDetectada = request()->ip() ?? '';
    }

    public function updatedScopes(): void
    {
        foreach (['consulta', 'colaboradores'] as $familia) {
            if (in_array("{$familia}:*", $this->scopes, true)) {
                $this->scopes = array_values(array_filter(
                    $this->scopes,
                    fn (string $scope): bool => str_starts_with($scope, "{$familia}:") ? $scope === "{$familia}:*" : true,
                ));
            }
        }
    }

    public function abrirFormulario(): void
    {
        $this->resetValidation();
        $this->reset(['nombre', 'ips', 'scopes', 'editandoId']);
        $this->scopes = ['consulta:cedula', 'consulta:ruc'];
        $this->mostrarFormulario = true;
    }

    public function editar(int $apiKeyId): void
    {
        $apiKey = $this->cliente()->apiKeys()->findOrFail($apiKeyId);

        $this->resetValidation();
        $this->mostrarFormulario = true;
        $this->editandoId = $apiKey->id;
        $this->nombre = $apiKey->nombre;
        $this->ips = implode("\n", $apiKey->ips_permitidas ?? []);
        $this->scopes = array_values(array_intersect($apiKey->alcance ?? [], ApiKeyService::SCOPES));
    }

    public function cancelarEdicion(): void
    {
        $this->mostrarFormulario = false;
        $this->editandoId = null;
        $this->reset(['nombre', 'ips', 'scopes']);
        $this->resetValidation();
    }

    public function crear(ApiKeyService $keys): void
    {
        $cliente = $this->cliente();
        $this->validarFormulario($cliente->id);
        $options = $keys->validateOptions($this->opcionesFormulario());
        $secret = $keys->issue($cliente, $options, auth()->id(), request()->ip());

        $this->mostrarClave($secret, $options['name']);
        $this->cancelarEdicion();
        session()->flash('status', 'La API Key se creó. Cópiala ahora; después no volverá a mostrarse.');
    }

    public function guardar(ApiKeyService $keys): void
    {
        $cliente = $this->cliente();
        $apiKey = $cliente->apiKeys()->findOrFail($this->editandoId);
        $this->validarFormulario($cliente->id, $apiKey->id);
        $options = $keys->validateOptions($this->opcionesFormulario());

        $apiKey->update([
            'nombre' => $options['name'],
            'ips_permitidas' => $options['ips'],
            'alcance' => $options['scopes'],
        ]);

        LogActividad::create([
            'accion' => 'API_KEY_CONFIGURADA',
            'actor_id' => auth()->id(),
            'actor_sistema' => false,
            'detalle' => ['api_key_id' => $apiKey->id, 'nombre' => $apiKey->nombre, 'prefijo' => $apiKey->prefijo, 'scopes' => $options['scopes']],
            'ip_origen' => request()->ip(),
        ]);

        $this->cancelarEdicion();
        session()->flash('status', 'Se guardaron los cambios de la API Key.');
    }

    public function rotar(int $apiKeyId, ApiKeyService $keys): void
    {
        $apiKey = $this->cliente()->apiKeys()->findOrFail($apiKeyId);
        $secret = $keys->rotate($apiKey, [], auth()->id(), request()->ip());

        $this->mostrarClave($secret, $apiKey->nombre);
        session()->flash('status', 'La clave anterior quedó invalidada. Copia la nueva clave ahora.');
    }

    public function revocar(int $apiKeyId, ApiKeyService $keys): void
    {
        $apiKey = $this->cliente()->apiKeys()->findOrFail($apiKeyId);
        $keys->revoke($apiKey, auth()->id(), request()->ip());

        if ($this->editandoId === $apiKey->id) {
            $this->cancelarEdicion();
        }

        session()->flash('status', "Se revocó la API Key de {$apiKey->nombre}.");
    }

    public function agregarIpActual(): void
    {
        if ($this->ipDetectada === '') {
            return;
        }

        $ips = array_values(array_filter(array_map('trim', preg_split('/\R/', $this->ips) ?: [])));
        if (! in_array($this->ipDetectada, $ips, true)) {
            $ips[] = $this->ipDetectada;
        }

        $this->ips = implode("\n", $ips);
    }

    public function ocultarNuevaKey(): void
    {
        $this->nuevaKey = null;
        $this->nombreNuevaKey = '';
    }

    private function validarFormulario(int $clienteId, ?int $apiKeyId = null): void
    {
        $nombreUnico = Rule::unique('api_keys', 'nombre')->where('cliente_id', $clienteId);
        if ($apiKeyId !== null) {
            $nombreUnico->ignore($apiKeyId);
        }

        $this->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                $nombreUnico,
            ],
            'ips' => ['nullable', 'string'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', Rule::in(ApiKeyService::SCOPES)],
        ]);
    }

    private function opcionesFormulario(): array
    {
        return [
            'name' => $this->nombre,
            'ips' => preg_split('/\R/', $this->ips) ?: [],
            'scopes' => $this->scopes,
        ];
    }

    private function mostrarClave(string $secret, string $nombre): void
    {
        $this->nuevaKey = $secret;
        $this->nombreNuevaKey = $nombre;
    }

    private function cliente()
    {
        return auth()->user()->cliente;
    }

    public function render()
    {
        return view('livewire.gestion-api-key', [
            'apiKeys' => $this->cliente()->apiKeys()->latest('id')->get(),
        ]);
    }
}
