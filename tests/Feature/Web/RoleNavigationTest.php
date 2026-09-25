<?php

namespace Tests\Feature\Web;

use App\Models\Cliente;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_ve_solo_navegacion_administrativa(): void
    {
        $usuario = Usuario::create([
            'email' => 'staff-nav@test.com',
            'nombre' => 'Staff',
            'roles' => ['staff'],
        ]);
        Staff::create(['usuario_id' => $usuario->id, 'rol_staff' => 'admin']);

        $response = $this->actingAs($usuario)->get(route('admin.clientes'));

        $response->assertOk()
            ->assertSeeTextInOrder(['Clientes', 'Logs', 'Configuración', 'Registros', 'Recargas'])
            ->assertDontSee('href="'.route('dashboard').'"', false)
            ->assertDontSee('href="'.route('dashboard.consultas').'"', false)
            ->assertDontSee('href="'.route('dashboard.api-key').'"', false)
            ->assertDontSee('href="'.route('dashboard.documentacion').'"', false)
            ->assertDontSee('href="'.route('dashboard.recibos').'"', false)
            ->assertSee('data-testid="home-link" href="'.route('admin.clientes').'"', false);
    }

    public function test_support_no_ve_logs_ni_config_y_no_puede_acceder_a_esas_rutas(): void
    {
        $usuario = Usuario::create([
            'email' => 'support-nav@test.com',
            'nombre' => 'Support',
            'roles' => ['staff'],
        ]);
        Staff::create(['usuario_id' => $usuario->id, 'rol_staff' => 'support']);

        $this->actingAs($usuario)
            ->get(route('admin.clientes'))
            ->assertOk()
            ->assertSeeText('Clientes')
            ->assertSeeText('Registros')
            ->assertSeeText('Aportes')
            ->assertSeeText('Recargas')
            ->assertDontSeeText('Logs')
            ->assertDontSeeText('Configuración');

        $this->get(route('admin.logs'))->assertForbidden();
        $this->get(route('admin.config'))->assertForbidden();
        $this->get(route('admin.registros'))->assertOk();
        $this->get(route('admin.aportes'))->assertOk();
        $this->get(route('admin.recargas'))->assertOk();
    }

    public function test_cliente_ve_solo_navegacion_de_cliente(): void
    {
        $usuario = Usuario::create([
            'email' => 'cliente-nav@test.com',
            'nombre' => 'Cliente',
            'email_verified_at' => now(),
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id]);

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertOk()
            ->assertSeeTextInOrder(['Saldo', 'Historial', 'API Key', 'Recibos', 'Documentación'])
            ->assertSeeText('Colaboración')
            ->assertDontSee('href="'.route('admin.logs').'"', false)
            ->assertDontSee('href="'.route('admin.config').'"', false)
            ->assertDontSee('href="'.route('admin.registros').'"', false)
            ->assertDontSee('href="'.route('admin.recargas').'"', false)
            ->assertSee('data-testid="home-link" href="'.route('dashboard').'"', false);
    }

    public function test_cliente_puede_abrir_documentacion_de_api(): void
    {
        $usuario = Usuario::create([
            'email' => 'cliente-docs@test.com',
            'nombre' => 'Cliente',
            'email_verified_at' => now(),
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id]);

        $this->actingAs($usuario)
            ->get(route('dashboard.documentacion'))
            ->assertOk()
            ->assertSeeText('Quickstart')
            ->assertSeeText('Consulta por cédula')
            ->assertSeeText('Consulta por RUC')
            ->assertSeeText('consulta:cedula')
            ->assertSeeText('Endpoint de aportes')
            ->assertSeeText('colaboradores:aportes')
            ->assertSeeText('recompensa_creditos')
            ->assertSeeText('Prueba rápida con curl')
            ->assertSeeText('fechaNacimiento')
            ->assertSeeText('contacto.telefonos')
            ->assertSeeText('actividadEconomica.codigo')
            ->assertSeeText('fechas.inicioActividades')
            ->assertSeeText('contacto.email')
            ->assertSee('/api/v1/consulta/cedula', false)
            ->assertSee('/api/v1/consulta/ruc', false)
            ->assertSee('{"cedula":"1713175071"}', false)
            ->assertDontSee("-d '{\"ruc\":\"0991234567001\"}'", false)
            ->assertSeeText('En esta página')
            ->assertSee('href="#quickstart"', false)
            ->assertSee('href="#cedula-fields"', false)
            ->assertSee('href="#ruc-fields"', false)
            ->assertSee('href="#curl-example"', false)
            ->assertSee('xl:self-stretch', false)
            ->assertSee('sticky top-6', false)
            ->assertSee('style="color: #f8fafc !important"', false)
            ->assertSee('class="lg:pl-64"', false)
            ->assertSee('name="viewport"', false);
    }
}
