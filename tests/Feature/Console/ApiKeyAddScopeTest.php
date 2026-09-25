<?php

namespace Tests\Feature\Console;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyAddScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_scope_only_to_active_keys_and_is_idempotent(): void
    {
        $active = $this->client('active', ['consulta:cedula']);
        $nullScope = $this->client('null', null);
        $revoked = $this->client('revoked', ['consulta:cedula'], true);
        $withoutHash = $this->client('without-hash', ['consulta:cedula']);
        $withoutHash->update(['api_key_hash' => null]);

        $this->artisan('apikey:add-scope', ['scope' => 'consulta:ruc'])
            ->expectsOutput('Scopes actualizados: 2')->assertExitCode(0);

        $this->assertEquals(['consulta:cedula', 'consulta:ruc'], $active->refresh()->api_key_alcance);
        $this->assertEquals(['consulta:ruc'], $nullScope->refresh()->api_key_alcance);
        $this->assertEquals(['consulta:cedula'], $revoked->refresh()->api_key_alcance);
        $this->assertEquals(['consulta:cedula'], $withoutHash->refresh()->api_key_alcance);

        $this->artisan('apikey:add-scope', ['scope' => 'consulta:ruc'])
            ->expectsOutput('Scopes actualizados: 0')->assertExitCode(0);
    }

    public function test_rechaza_un_scope_inexistente_sin_escribirlo(): void
    {
        $active = $this->client('active-legacy', ['consulta:cedula']);

        // Un scope que no existe en ApiKeyService::SCOPES no debe persistirse: la página de
        // configuración lo cargaría sin casilla que desmarcar y el cliente quedaría sin poder guardar.
        $this->artisan('apikey:add-scope', ['scope' => 'colaboradores:legacy'])
            ->expectsOutputToContain('El scope "colaboradores:legacy" no existe.')
            ->assertExitCode(1);

        $this->assertEquals(['consulta:cedula'], $active->refresh()->api_key_alcance);
    }

    private function client(string $uid, ?array $scope, bool $revoked = false): Cliente
    {
        $user = Usuario::create([
            'uid' => $uid, 'email' => $uid.'@test.com', 'nombre' => $uid, 'roles' => json_encode(['cliente']),
        ]);

        return Cliente::create([
            'usuario_id' => $user->id, 'api_key_hash' => 'hash-'.$uid,
            'api_key_revocada' => $revoked, 'api_key_alcance' => $scope,
        ]);
    }
}
