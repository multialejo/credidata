<?php

namespace App\Livewire;

use App\Models\LogActividad;
use App\Services\ApiKeyService;
use Livewire\Component;

class GestionApiKey extends Component
{
    public $prefijo;

    public $creada;

    public $ultimoUso;

    public $rotacionSugerida;

    public $revocada;

    public $ipsPermitidas;

    public $alcance;

    public string $alias = '';

    public string $ips = '';

    public array $scopes = [];

    public $nuevaKey;

    public function mount()
    {
        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        $cliente = auth()->user()->cliente;
        $this->prefijo = $cliente->api_key_prefijo;
        $this->creada = $cliente->api_key_creada;
        $this->ultimoUso = $cliente->api_key_ultimo_uso;
        $this->rotacionSugerida = $cliente->api_key_rotacion_sugerida_en;
        $this->revocada = $cliente->api_key_revocada;
        $this->ipsPermitidas = $cliente->api_key_ips_permitidas;
        $this->alcance = $cliente->api_key_alcance;
        $this->alias = $cliente->api_key_alias ?? '';
        $this->ips = implode("\n", $cliente->api_key_ips_permitidas ?? []);
        $this->scopes = $cliente->api_key_alcance ?? [];
    }

    public function generar(ApiKeyService $keys)
    {
        $this->validate(['alias' => ['nullable', 'string', 'max:100'], 'scopes' => ['array']]);
        $cliente = auth()->user()->cliente;
        $options = $keys->validateOptions(['alias' => $this->alias, 'ips' => preg_split('/\R/', $this->ips) ?: [], 'scopes' => $this->scopes]);
        $this->nuevaKey = $cliente->api_key_revocada || ! $cliente->api_key_hash
            ? $keys->issue($cliente, $options, auth()->id(), request()->ip())
            : $keys->rotate($cliente, $options, auth()->id(), request()->ip());
        $this->cargarDatos();
    }

    public function guardarConfiguracion(ApiKeyService $keys): void
    {
        $this->validate(['alias' => ['nullable', 'string', 'max:100'], 'scopes' => ['array']]);
        $cliente = auth()->user()->cliente;
        $options = $keys->validateOptions(['alias' => $this->alias, 'ips' => preg_split('/\R/', $this->ips) ?: [], 'scopes' => $this->scopes]);
        $cliente->update(['api_key_alias' => $options['alias'], 'api_key_ips_permitidas' => $options['ips'], 'api_key_alcance' => $options['scopes']]);
        LogActividad::create(['accion' => 'API_KEY_CONFIGURADA', 'actor_id' => auth()->id(), 'actor_sistema' => false,
            'detalle' => ['prefijo' => $cliente->api_key_prefijo, 'scopes' => $options['scopes']], 'ip_origen' => request()->ip()]);
        $this->cargarDatos();
        session()->flash('status', 'Configuración de API Key actualizada.');
    }

    public function revocar()
    {
        $cliente = auth()->user()->cliente;
        $cliente->update([
            'api_key_revocada' => true,
            'api_key_revocada_en' => now(),
        ]);

        LogActividad::create([
            'accion' => 'API_KEY_REVOCADA',
            'actor_id' => $cliente->usuario->id,
            'detalle' => ['prefijo' => $cliente->api_key_prefijo, 'origen' => 'dashboard'],
            'ip_origen' => request()->ip(),
        ]);

        $this->nuevaKey = null;
        $this->cargarDatos();
    }

    public function render()
    {
        return view('livewire.gestion-api-key');
    }
}
