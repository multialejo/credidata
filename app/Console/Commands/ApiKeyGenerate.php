<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\LogActividad;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKeyGenerate extends Command
{
    protected $signature = 'apikey:generate {uid}';
    protected $description = 'Genera una API key para un cliente';

    public function handle()
    {
        $cliente = Cliente::where('uid', $this->argument('uid'))->first();
        if (!$cliente) {
            $this->error('Cliente no encontrado');
            return 1;
        }

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
            'detalle' => ['prefijo' => $cliente->api_key_prefijo],
            'ip_origen' => 'sistema',
        ]);

        $this->info("API key generada: {$key}");
        $this->warn('Guárdala, no se mostrará de nuevo.');

        return 0;
    }
}
