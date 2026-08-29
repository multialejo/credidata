<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;

class ApiKeyAddScope extends Command
{
    protected $signature = 'apikey:add-scope {scope}';

    protected $description = 'Agrega un permiso a las API keys activas';

    public function handle(): int
    {
        $scope = $this->argument('scope');
        $updated = 0;
        Cliente::query()->where('api_key_revocada', false)->whereNotNull('api_key_hash')->chunkById(100, function ($clientes) use ($scope, &$updated): void {
            foreach ($clientes as $cliente) {
                $scopes = $cliente->api_key_alcance ?? [];
                if (! in_array($scope, $scopes, true)) {
                    $cliente->updateQuietly(['api_key_alcance' => array_values([...$scopes, $scope])]);
                    $updated++;
                }
            }
        });
        $this->info("Scopes actualizados: {$updated}");

        return self::SUCCESS;
    }
}
