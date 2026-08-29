<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use App\Services\ApiKeyService;

class ApiKeyGenerate extends Command
{
    protected $signature = 'apikey:generate {uid}';

    protected $description = 'Genera una API key para un cliente';

    public function handle(ApiKeyService $keys)
    {
        $cliente = Cliente::whereHas('usuario', fn ($q) => $q->where('uid', $this->argument('uid'))
        )->first();
        if (! $cliente) {
            $this->error('Cliente no encontrado');

            return 1;
        }

        $key = $keys->issue($cliente, ['scopes' => ['consulta:cedula', 'consulta:ruc'], 'ips' => []], null, 'sistema');

        $this->info("API key generada: {$key}");
        $this->warn('Guárdala, no se mostrará de nuevo.');

        return 0;
    }
}
