<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\LogActividad;

class GestionApiKey extends Component
{
    public $prefijo;
    public $creada;
    public $ultimoUso;
    public $revocada;
    public $ipsPermitidas;
    public $alcance;
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
        $this->revocada = $cliente->api_key_revocada;
        $this->ipsPermitidas = $cliente->api_key_ips_permitidas;
        $this->alcance = $cliente->api_key_alcance;
    }

    public function generar()
    {
        $cliente = auth()->user()->cliente;
        $key = 'cd_sk_' . Str::random(32);

        $cliente->update([
            'api_key_hash' => Hash::make($key),
            'api_key_prefijo' => substr($key, 0, 12),
            'api_key_creada' => now(),
            'api_key_revocada' => false,
            'api_key_revocada_en' => null,
        ]);

        LogActividad::create([
            'accion' => 'API_KEY_GENERADA',
            'actor_id' => $cliente->uid,
            'detalle' => ['prefijo' => $cliente->api_key_prefijo, 'origen' => 'dashboard'],
            'ip_origen' => request()->ip(),
        ]);

        $this->nuevaKey = $key;
        $this->cargarDatos();
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
            'actor_id' => $cliente->uid,
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
