<?php

namespace Tests\Feature\Livewire;

use App\Livewire\EditarRegistro;
use App\Models\LogActividad;
use App\Models\Staff;
use App\Models\Usuario;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\DocumentSnapshot;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class EditarRegistroTest extends TestCase
{
    use RefreshDatabase;

    private const CEDULA = '1713175071';

    private const CEDULA_B = '1713175089';

    private Usuario $staffUsuario;

    private Staff $staff;

    private Usuario $clienteUsuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffUsuario = Usuario::create([
            'uid' => 'staff-uid',
            'email' => 'staff@test.com',
            'nombre' => 'Staff Admin',
            'roles' => json_encode(['staff']),
        ]);
        $this->staff = Staff::create([
            'usuario_id' => $this->staffUsuario->id,
            'rol_staff' => 'admin',
            'fecha_asignacion' => now(),
        ]);

        $this->clienteUsuario = Usuario::create([
            'uid' => 'cliente-uid',
            'email' => 'cliente@test.com',
            'nombre' => 'Juan Cliente',
            'roles' => json_encode(['cliente']),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Documento Firestore típico de `sujetos/{id}`: mezcla de datos de fuentes
     * oficiales (Dinardap/SRI) y contacto editable.
     */
    private function documentoBase(array $overrides = []): array
    {
        return array_merge([
            'tipoIdentificador' => 'cedula',
            'nombres' => 'Juan Carlos Pérez García',
            'fechaNacimiento' => '1985-06-15',
            'lugarNacimiento' => 'Quito',
            'estadoCivilCodigo' => 1,
            'conyuge' => 'María Fernanda López',
            'ubicacion' => [
                'provincia' => 'Pichincha',
                'canton' => 'Quito',
                'parroquia' => 'La Carolina',
            ],
            'fuentesUtilizadas' => ['dinardap'],
            'ultimaActualizacion' => '2025-06-18T10:00:00+00:00',
            'contacto' => [
                'telefonos' => ['0991234567'],
                'emails' => ['cliente@test.com'],
                'direcciones' => ['Av. Amazonas N37-123'],
            ],
        ], $overrides);
    }

    /**
     * Mockea la cadena Firebase::firestore()->database()->document("sujetos/{$id}")
     * y devuelve el mock del DocumentReference para configurar `set()`.
     */
    private function mockDocumento(string $identificador, array $data, bool $exists = true): MockInterface
    {
        $snapshot = Mockery::mock(DocumentSnapshot::class);
        $snapshot->shouldReceive('exists')->andReturn($exists);
        $snapshot->shouldReceive('data')->andReturn($data);

        $document = Mockery::mock(DocumentReference::class);
        $document->shouldReceive('snapshot')->andReturn($snapshot);

        $database = Mockery::mock(FirestoreClient::class);
        $database->shouldReceive('document')->with("sujetos/{$identificador}")->andReturn($document);

        $firestore = Mockery::mock(Firestore::class);
        $firestore->shouldReceive('database')->andReturn($database);

        Firebase::shouldReceive('firestore')->andReturn($firestore);

        return $document;
    }

    // --- Acceso ---

    public function test_staff_puede_ver_la_pagina(): void
    {
        $this->actingAs($this->staffUsuario)
            ->get('/admin/registros')
            ->assertOk()
            ->assertSee('Identificador (cédula o RUC)')
            ->assertDontSeeHtml('id="identificador" wire:model="identificador" placeholder="Ej. 1713175071" disabled');
    }

    public function test_cliente_no_staff_recibe_403(): void
    {
        $this->actingAs($this->clienteUsuario)
            ->get('/admin/registros')
            ->assertForbidden();
    }

    public function test_visitante_es_redirigido_a_login(): void
    {
        $this->get('/admin/registros')
            ->assertRedirect(route('login'));
    }

    // --- Búsqueda: validación del identificador ---

    public function test_identificador_invalido_rechaza_sin_consultar_firestore(): void
    {
        Firebase::shouldReceive('firestore')->never();

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', '12345')
            ->call('buscar')
            ->assertHasErrors(['identificador' => 'El identificador no es una cédula ni un RUC ecuatoriano válido.'])
            ->assertSet('encontrado', false);
    }

    // --- Búsqueda: sujeto inexistente ---

    public function test_sujeto_inexistente_muestra_error(): void
    {
        $this->mockDocumento(self::CEDULA, [], exists: false);

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->assertHasErrors(['identificador' => 'No se encontró un registro con ese identificador.'])
            ->assertSet('encontrado', false);
    }

    // --- Búsqueda: carga de campos de contacto ---

    public function test_buscar_carga_campos_de_contacto_del_documento(): void
    {
        $this->mockDocumento(self::CEDULA, $this->documentoBase());

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->assertSet('encontrado', true)
            ->assertSet('telefonos', '0991234567')
            ->assertSet('emails', 'cliente@test.com')
            ->assertSet('direcciones', 'Av. Amazonas N37-123');
    }

    public function test_buscar_carga_multiples_valores_uno_por_linea(): void
    {
        $this->mockDocumento(self::CEDULA, $this->documentoBase([
            'contacto' => [
                'telefonos' => ['0991234567', '022345678'],
                'emails' => ['cliente@test.com', 'secundario@test.com'],
                'direcciones' => ['Av. Amazonas N37-123'],
            ],
        ]));

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->assertSet('encontrado', true)
            ->assertSet('telefonos', "0991234567\n022345678")
            ->assertSet('emails', "cliente@test.com\nsecundario@test.com");
    }

    public function test_buscar_documento_sin_contacto_carga_campos_vacios(): void
    {
        $this->mockDocumento(self::CEDULA, $this->documentoBase(['contacto' => null]));

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->assertSet('encontrado', true)
            ->assertSet('telefonos', '')
            ->assertSet('emails', '')
            ->assertSet('direcciones', '');
    }

    // --- Edición: persiste en Firestore ---

    public function test_guardar_persiste_contacto_editado_con_set_preservando_resto(): void
    {
        $document = $this->mockDocumento(self::CEDULA, $this->documentoBase());

        $captured = null;
        $document->shouldReceive('set')
            ->once()
            ->with(Mockery::on(function (array $data) use (&$captured): bool {
                $captured = $data;

                return true;
            }));

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->set('telefonos', "0991234567\n0997654321")
            ->set('emails', 'nuevo@test.com')
            ->set('direcciones', 'Av. Amazonas N37-123')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertNotNull($captured, 'set() debe haber sido llamado con el documento completo');
        $this->assertSame(['0991234567', '0997654321'], $captured['contacto']['telefonos']);
        $this->assertSame(['nuevo@test.com'], $captured['contacto']['emails']);
        $this->assertSame(['Av. Amazonas N37-123'], $captured['contacto']['direcciones']);
        // Los datos de fuentes oficiales NO se tocan.
        $this->assertSame('Juan Carlos Pérez García', $captured['nombres']);
        $this->assertSame('1985-06-15', $captured['fechaNacimiento']);
        $this->assertSame(['dinardap'], $captured['fuentesUtilizadas']);
        $this->assertSame('2025-06-18T10:00:00+00:00', $captured['ultimaActualizacion']);
    }

    // --- Log de auditoría ---

    public function test_guardar_crea_log_con_antes_despues_por_campo(): void
    {
        $document = $this->mockDocumento(self::CEDULA, $this->documentoBase());
        $document->shouldReceive('set')->andReturn([]);

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->set('telefonos', '0997654321')
            ->set('emails', 'nuevo@test.com')
            ->call('guardar');

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'registro.editado_por_staff',
            'actor_id' => $this->staffUsuario->id,
            'actor_sistema' => false,
        ]);

        $log = LogActividad::where('accion', 'registro.editado_por_staff')->firstOrFail();
        $this->assertSame(self::CEDULA, $log->detalle['identificador']);
        $this->assertSame(['telefonos', 'emails'], $log->detalle['campos_editados']);
        $this->assertSame(['0991234567'], $log->detalle['antes']['telefonos']);
        $this->assertSame(['cliente@test.com'], $log->detalle['antes']['emails']);
        $this->assertSame(['Av. Amazonas N37-123'], $log->detalle['antes']['direcciones']);
        $this->assertSame(['0997654321'], $log->detalle['despues']['telefonos']);
        $this->assertSame(['nuevo@test.com'], $log->detalle['despues']['emails']);
        $this->assertSame(['Av. Amazonas N37-123'], $log->detalle['despues']['direcciones']);
    }

    // --- Privacidad: no se exponen campos oficiales ni estructura cruda ---

    public function test_vista_no_expone_campos_de_fuentes_oficiales_ni_estructura(): void
    {
        $this->mockDocumento(self::CEDULA, $this->documentoBase());

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->assertDontSee('fechaNacimiento')
            ->assertDontSee('fuentesUtilizadas')
            ->assertDontSee('ultimaActualizacion')
            ->assertDontSee('tipoIdentificador');
    }

    // --- Bloqueo del campo de búsqueda al editar ---

    public function test_campo_identificador_y_boton_buscar_se_deshabilitan_tras_cargar_registro(): void
    {
        $this->mockDocumento(self::CEDULA, $this->documentoBase());

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->assertSet('encontrado', true)
            ->assertSet('telefonos', '0991234567')
            ->assertSeeHtml('disabled')
            ->assertSeeHtml('cursor-not-allowed');
    }

    public function test_nueva_busqueda_desbloquea_y_limpia_el_campo(): void
    {
        $this->mockDocumento(self::CEDULA, $this->documentoBase());

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->assertSet('encontrado', true)
            ->call('nuevaBusqueda')
            ->assertSet('encontrado', false)
            ->assertSet('identificador', '')
            ->assertSet('telefonos', '')
            ->assertSet('emails', '')
            ->assertSet('direcciones', '');
    }

    public function test_buscar_dos_veces_requiere_nueva_busqueda_entre_registros(): void
    {
        $snapshot1 = Mockery::mock(DocumentSnapshot::class);
        $snapshot1->shouldReceive('exists')->andReturn(true);
        $snapshot1->shouldReceive('data')->andReturn($this->documentoBase([
            'contacto' => ['telefonos' => ['0991111111'], 'emails' => ['a@test.com'], 'direcciones' => ['Dir A']],
        ]));

        $doc1 = Mockery::mock(DocumentReference::class);
        $doc1->shouldReceive('snapshot')->andReturn($snapshot1);

        $snapshot2 = Mockery::mock(DocumentSnapshot::class);
        $snapshot2->shouldReceive('exists')->andReturn(true);
        $snapshot2->shouldReceive('data')->andReturn($this->documentoBase([
            'contacto' => ['telefonos' => ['0992222222'], 'emails' => ['b@test.com'], 'direcciones' => ['Dir B']],
        ]));

        $doc2 = Mockery::mock(DocumentReference::class);
        $doc2->shouldReceive('snapshot')->andReturn($snapshot2);

        $database = Mockery::mock(FirestoreClient::class);
        $database->shouldReceive('document')
            ->with('sujetos/'.self::CEDULA)->andReturn($doc1);
        $database->shouldReceive('document')
            ->with('sujetos/'.self::CEDULA_B)->andReturn($doc2);

        $firestore = Mockery::mock(Firestore::class);
        $firestore->shouldReceive('database')->andReturn($database);

        Firebase::shouldReceive('firestore')->andReturn($firestore);

        Livewire::actingAs($this->staffUsuario)
            ->test(EditarRegistro::class)
            ->set('identificador', self::CEDULA)
            ->call('buscar')
            ->assertSet('encontrado', true)
            ->assertSet('telefonos', '0991111111')
            ->call('nuevaBusqueda')
            ->set('identificador', self::CEDULA_B)
            ->call('buscar')
            ->assertSet('encontrado', true)
            ->assertSet('telefonos', '0992222222')
            ->assertSet('emails', 'b@test.com');
    }
}
