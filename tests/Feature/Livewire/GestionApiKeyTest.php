<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GestionApiKey;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionApiKeyTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::create([
            'uid' => 'api-key-ui-client',
            'email' => 'api-key-ui@example.com',
            'nombre' => 'API Key UI',
            'estado' => 'activo',
            'roles' => ['cliente'],
        ]);

        Cliente::create([
            'usuario_id' => $this->usuario->id,
            'api_key_prefijo' => 'abcd1234',
            'api_key_alcance' => ['consulta:cedula', 'consulta:ruc'],
        ]);
    }

    public function test_acceso_completo_reemplaza_permisos_especificos(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->set('scopes', ['consulta:cedula', 'consulta:ruc', 'consulta:*'])
            ->assertSet('scopes', ['consulta:*'])
            ->assertSee('El acceso completo incluye todas las consultas actuales y futuras.');
    }

    public function test_wildcard_de_colaboradores_no_arrastra_los_permisos_de_consulta(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->set('scopes', ['consulta:cedula', 'colaboradores:aportes', 'colaboradores:*'])
            ->assertSet('scopes', ['consulta:cedula', 'colaboradores:*']);
    }

    public function test_wildcard_de_consulta_no_arrastra_los_permisos_de_colaboracion(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->set('scopes', ['colaboradores:aportes', 'consulta:cedula', 'consulta:ruc', 'consulta:*'])
            ->assertSet('scopes', ['colaboradores:aportes', 'consulta:*']);
    }

    public function test_las_familias_de_permisos_son_independientes(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->set('scopes', ['consulta:*', 'colaboradores:*'])
            ->assertSet('scopes', ['consulta:*', 'colaboradores:*']);
    }

    public function test_agregar_ip_actual_la_agrega_sin_duplicarla(): void
    {
        Livewire::actingAs($this->usuario)
            ->withQueryParams([])
            ->test(GestionApiKey::class)
            ->set('ipDetectada', '203.0.113.8')
            ->set('ips', "192.0.2.5\n203.0.113.8")
            ->call('agregarIpActual')
            ->assertSet('ips', "192.0.2.5\n203.0.113.8");
    }

    public function test_un_scope_desconocido_no_deja_al_cliente_sin_poder_guardar(): void
    {
        $this->usuario->cliente->update(['api_key_alcance' => ['consulta:cedula', 'colaboradores:legacy']]);

        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->assertOk()
            ->assertSet('scopes', ['consulta:cedula'])
            ->assertDontSee('colaboradores:legacy')
            ->call('guardarConfiguracion')
            ->assertHasNoErrors();

        $this->assertSame(['consulta:cedula'], $this->usuario->cliente->fresh()->api_key_alcance);
    }

    public function test_vista_explica_restriccion_por_ip_y_separa_acciones_riesgosas(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->assertSee('Si no agregas IPs, la clave podrá usarse desde cualquier dirección.')
            ->assertSee('Ciclo de vida de la API Key')
            ->assertSee('Regenerar invalida inmediatamente la clave actual.')
            ->assertSee('Aportes')
            ->assertDontSee('Acceso completo a colaboración')
            ->assertSee('Revocar clave');
    }
}
