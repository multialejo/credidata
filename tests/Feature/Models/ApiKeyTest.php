<?php

namespace Tests\Feature\Models;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_puede_tener_claves_independientes_por_aplicacion(): void
    {
        $cliente = $this->crearCliente('developer-one');
        $erp = $cliente->apiKeys()->create([
            'nombre' => 'ERP',
            'prefijo' => 'cd_sk_erp00001',
            'hash' => 'hashed-secret-erp',
            'alcance' => ['consulta:ruc'],
            'ips_permitidas' => ['192.0.2.1'],
        ]);
        $crm = $cliente->apiKeys()->create([
            'nombre' => 'CRM',
            'prefijo' => 'cd_sk_crm00001',
            'hash' => 'hashed-secret-crm',
            'alcance' => ['consulta:cedula'],
        ]);

        $this->assertCount(2, $cliente->apiKeys);
        $this->assertSame(['consulta:ruc'], $erp->alcance);
        $this->assertSame(['consulta:cedula'], $crm->alcance);
        $this->assertSame('developer-one', $erp->cliente->usuario->uid);
    }

    public function test_nombre_de_aplicacion_es_unico_dentro_del_cliente(): void
    {
        $cliente = $this->crearCliente('developer-two');
        $atributos = [
            'nombre' => 'ERP',
            'prefijo' => 'cd_sk_erp00002',
            'hash' => 'hashed-secret',
        ];
        $cliente->apiKeys()->create($atributos);

        $this->expectException(QueryException::class);

        $cliente->apiKeys()->create([...$atributos, 'prefijo' => 'cd_sk_erp00003']);
    }

    private function crearCliente(string $uid): Cliente
    {
        $usuario = Usuario::create([
            'uid' => $uid,
            'email' => "{$uid}@example.com",
            'nombre' => $uid,
            'estado' => 'activo',
            'roles' => ['cliente'],
        ]);

        return Cliente::create(['usuario_id' => $usuario->id]);
    }
}
