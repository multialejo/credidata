<?php

namespace Tests\Unit;

use App\Http\Middleware\ValidateApiKey;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ValidateApiKeyUserResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_resolver_expone_al_dueno_de_la_key_y_rechaza_guards_explicitos(): void
    {
        $usuario = Usuario::create(['uid' => 'resolver', 'email' => 'resolver@test.com', 'nombre' => 'Resolver', 'roles' => ['cliente']]);
        $cliente = Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 0]);
        $key = app(ApiKeyService::class)->issue($cliente, ['scopes' => ['consulta:cedula'], 'ips' => []], $usuario->id);

        $request = Request::create('/api/v1/consulta/cedula', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$key,
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $captured = null;
        (new ValidateApiKey(app(ApiKeyService::class)))->handle($request, function (Request $passed) use (&$captured) {
            $captured = $passed;
        }, 'consulta:cedula');

        $this->assertNotNull($captured, 'El middleware debe dejar pasar la petición.');
        $this->assertSame($usuario->id, $captured->user()?->id);
        $this->assertNull($captured->user('sanctum'));
        $this->assertNull($captured->user('web'));
        $this->assertNull(auth()->user());
    }
}
