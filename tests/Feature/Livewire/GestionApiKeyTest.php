<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GestionApiKey;
use App\Models\ApiKey;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionApiKeyTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

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
        $this->cliente = Cliente::create(['usuario_id' => $this->usuario->id]);
    }

    public function test_cliente_puede_crear_claves_separadas_para_sus_aplicativos(): void
    {
        $component = Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('abrirFormulario')
            ->set('nombre', 'ERP')
            ->set('scopes', ['consulta:ruc'])
            ->call('crear')
            ->assertHasNoErrors()
            ->assertSet('nombreNuevaKey', 'ERP')
            ->assertSee('La API Key se creó. Cópiala ahora');

        $this->assertMatchesRegularExpression('/^cd_sk_[A-Za-z0-9]{8}_[a-f0-9]{64}$/', $component->get('nuevaKey'));

        $erp = $this->cliente->apiKeys()->where('nombre', 'ERP')->firstOrFail();
        $this->assertSame(['consulta:ruc'], $erp->alcance);
        $this->assertNotNull($erp->hash);

        $component = Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Colaboración')
            ->set('scopes', ['colaboradores:aportes'])
            ->call('crear')
            ->assertHasNoErrors();

        $this->assertSame(2, $this->cliente->apiKeys()->count());
        $this->assertSame(['colaboradores:aportes'], $this->cliente->apiKeys()->where('nombre', 'Colaboración')->firstOrFail()->alcance);
        $this->assertSame(['consulta:ruc'], $erp->fresh()->alcance);
    }

    public function test_el_nombre_de_aplicativo_debe_ser_unico_por_cliente(): void
    {
        $key = $this->crearKey('ERP', ['consulta:ruc']);

        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('abrirFormulario')
            ->set('nombre', 'ERP')
            ->call('crear')
            ->assertHasErrors(['nombre' => 'unique']);

        $this->assertSame(1, $this->cliente->apiKeys()->count());
        $this->assertFalse($key->fresh()->revocada);
    }

    public function test_no_permite_crear_clave_sin_permisos(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('abrirFormulario')
            ->set('nombre', 'Sin permisos')
            ->set('scopes', [])
            ->call('crear')
            ->assertHasErrors('scopes');

        $this->assertSame(0, $this->cliente->apiKeys()->count());
    }

    public function test_configuracion_actualiza_solo_la_clave_seleccionada(): void
    {
        $erp = $this->crearKey('ERP', ['consulta:ruc']);
        $crm = $this->crearKey('CRM', ['consulta:cedula']);

        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('editar', $erp->id)
            ->set('nombre', 'ERP principal')
            ->set('scopes', ['consulta:ruc', 'colaboradores:aportes'])
            ->set('ips', "192.0.2.4\n198.51.100.8")
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Se guardaron los cambios de la API Key.');

        $this->assertSame('ERP principal', $erp->fresh()->nombre);
        $this->assertSame(['consulta:ruc', 'colaboradores:aportes'], $erp->fresh()->alcance);
        $this->assertSame(['192.0.2.4', '198.51.100.8'], $erp->fresh()->ips_permitidas);
        $this->assertSame('CRM', $crm->fresh()->nombre);
        $this->assertSame(['consulta:cedula'], $crm->fresh()->alcance);
    }

    public function test_rotar_y_revocar_no_afectan_otras_claves(): void
    {
        $erp = $this->crearKey('ERP', ['consulta:ruc']);
        $crm = $this->crearKey('CRM', ['consulta:cedula']);

        $component = Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('rotar', $erp->id)
            ->assertHasNoErrors()
            ->assertSet('nombreNuevaKey', 'ERP');

        $this->assertMatchesRegularExpression('/^cd_sk_[A-Za-z0-9]{8}_[a-f0-9]{64}$/', $component->get('nuevaKey'));

        $component
            ->call('revocar', $erp->id)
            ->assertHasNoErrors();

        $this->assertTrue($erp->fresh()->revocada);
        $this->assertFalse($crm->fresh()->revocada);
        $this->assertSame(['consulta:cedula'], $crm->fresh()->alcance);
    }

    public function test_acceso_completo_reemplaza_permisos_especificos_de_su_familia(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('abrirFormulario')
            ->set('scopes', ['consulta:cedula', 'consulta:ruc', 'colaboradores:aportes', 'consulta:*'])
            ->assertSet('scopes', ['colaboradores:aportes', 'consulta:*'])
            ->assertSee('El acceso completo incluye las consultas actuales y futuras.');
    }

    public function test_wildcard_de_colaboradores_no_arrastra_permisos_de_consulta(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('abrirFormulario')
            ->set('scopes', ['consulta:cedula', 'colaboradores:aportes', 'colaboradores:*'])
            ->assertSet('scopes', ['consulta:cedula', 'colaboradores:*']);
    }

    public function test_agregar_ip_actual_la_agrega_sin_duplicarla(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('abrirFormulario')
            ->set('ipDetectada', '203.0.113.8')
            ->set('ips', "192.0.2.5\n203.0.113.8")
            ->call('agregarIpActual')
            ->assertSet('ips', "192.0.2.5\n203.0.113.8");
    }

    public function test_muestra_estado_vacio_y_explica_claves_dedicadas(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->assertSee('Todavía no hay API Keys')
            ->assertSee('Para colaborar, crea una clave aparte')
            ->assertSee('Crear primera API Key');
    }

    public function test_un_scope_desconocido_se_descarta_al_guardar_configuracion(): void
    {
        $apiKey = $this->crearKey('ERP', ['consulta:cedula', 'colaboradores:legacy']);

        Livewire::actingAs($this->usuario)
            ->test(GestionApiKey::class)
            ->call('editar', $apiKey->id)
            ->assertSet('scopes', ['consulta:cedula'])
            ->assertDontSee('colaboradores:legacy')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(['consulta:cedula'], $apiKey->fresh()->alcance);
    }

    private function crearKey(string $nombre, array $scopes): ApiKey
    {
        return $this->cliente->apiKeys()->create([
            'nombre' => $nombre,
            'prefijo' => substr(md5($nombre), 0, 8),
            'hash' => bcrypt("secret-{$nombre}"),
            'alcance' => $scopes,
        ]);
    }
}
