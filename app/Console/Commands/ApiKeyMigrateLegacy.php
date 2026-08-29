<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\LogActividad;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApiKeyMigrateLegacy extends Command
{
    protected $signature = 'apikey:migrate-legacy {--dry-run : Solo informa las keys que serían revocadas}';
    protected $description = 'Revoca las API Keys legacy que no pueden validarse por prefijo';

    public function handle(): int
    {
        $query = Cliente::where('api_key_formato', 'legacy')->where('api_key_revocada', false)->whereNotNull('api_key_hash');
        $count = (clone $query)->count();
        if ($this->option('dry-run')) {
            $this->info("{$count} API Key(s) legacy serían revocadas.");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($query): void {
            $query->each(function (Cliente $cliente): void {
                $cliente->update(['api_key_revocada' => true, 'api_key_revocada_en' => now()]);
                LogActividad::create(['accion' => 'API_KEY_LEGACY_REVOCADA', 'actor_id' => $cliente->usuario_id,
                    'actor_sistema' => true, 'detalle' => ['prefijo' => $cliente->api_key_prefijo], 'ip_origen' => 'sistema']);
            });
        });
        $this->info("{$count} API Key(s) legacy revocadas. Cada cliente debe generar una nueva.");

        return self::SUCCESS;
    }
}
