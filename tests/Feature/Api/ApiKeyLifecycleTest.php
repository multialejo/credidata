<?php

namespace Tests\Feature\Api;

use App\Models\ApiKey;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_rotar_o_revocar_una_clave_no_afecta_otra_clave_del_cliente(): void
    {
        $usuario = Usuario::create([
            'uid' => 'api-key-lifecycle',
            'email' => 'api-key-lifecycle@example.com',
            'nombre' => 'API key lifecycle',
            'estado' => 'activo',
            'roles' => ['cliente'],
        ]);
        $cliente = Cliente::create(['usuario_id' => $usuario->id]);
        $keys = app(ApiKeyService::class);
        $erpSecret = $keys->issue($cliente, ['name' => 'ERP', 'scopes' => ['consulta:ruc']]);
        $crmSecret = $keys->issue($cliente, ['name' => 'CRM', 'scopes' => ['consulta:cedula']]);
        $erp = ApiKey::where('nombre', 'ERP')->firstOrFail();
        $crm = ApiKey::where('nombre', 'CRM')->firstOrFail();

        $rotatedSecret = $keys->rotate($erp, ['scopes' => ['consulta:ruc']], $usuario->id, '127.0.0.1');
        $this->withToken($erpSecret)->postJson('/api/v1/consulta/ruc')->assertUnauthorized();
        $this->withToken($rotatedSecret)->postJson('/api/v1/consulta/ruc')->assertUnprocessable();

        $keys->revoke($erp->fresh(), $usuario->id, '127.0.0.1');
        $this->withToken($rotatedSecret)->postJson('/api/v1/consulta/ruc')->assertUnauthorized();
        $this->withToken($crmSecret)->postJson('/api/v1/consulta/ruc')->assertForbidden();

        $this->assertTrue($erp->fresh()->revocada);
        $this->assertFalse($crm->fresh()->revocada);
        $this->assertSame(['consulta:cedula'], $crm->fresh()->alcance);
    }
}
