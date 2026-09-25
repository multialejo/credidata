<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\ApiKeyService;
use Illuminate\Console\Command;

class ApiKeyAddScope extends Command
{
    protected $signature = 'apikey:add-scope {scope}';

    protected $description = 'Agrega un permiso a las API keys activas';

    public function handle(): int
    {
        $scope = $this->argument('scope');
        if (! in_array($scope, ApiKeyService::SCOPES, true)) {
            $this->error("El scope \"{$scope}\" no existe. Válidos: ".implode(', ', ApiKeyService::SCOPES));

            return self::FAILURE;
        }

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
