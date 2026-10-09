<?php

namespace Tests\Unit;

use App\Exceptions\SaldoInsuficienteException;
use App\Models\ApiKey;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\LogActividad;
use App\Models\Usuario;
use App\Services\ConsultaCobroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ConsultaCobroServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConsultaCobroService $service;

    private Cliente $cliente;

    private ApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = Usuario::create([
            'uid' => 'cobro-uid', 'email' => 'cobro@test.com', 'nombre' => 'Cobro',
            'roles' => json_encode(['cliente']),
        ]);
        $this->cliente = Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 10]);
        $this->apiKey = $this->cliente->apiKeys()->create([
            'nombre' => 'Cobro', 'prefijo' => 'c0br0000', 'hash' => 'x', 'alcance' => ['consulta:cedula'],
        ]);
        $this->service = new ConsultaCobroService;
    }

    public function test_cobrar_debita_y_registra_traza_de_saldo(): void
    {
        $cobro = $this->service->cobrar($this->cliente, $this->apiKey, 'cedula', '1713175071', 3, ['nombres' => 'X'], ['dinardap'], '10.0.0.1');

        $this->assertSame(10.0, $cobro->saldoAnterior);
        $this->assertSame(7.0, $cobro->saldoNuevo);
        $this->assertSame(7.0, (float) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(3, $cobro->consulta->creditos_gastados);
        $this->assertTrue($cobro->consulta->exitosa);

        $log = LogActividad::sole();
        $this->assertSame('CONSULTA_CEDULA', $log->accion);
        $this->assertSame($this->cliente->usuario_id, $log->actor_id);
        $this->assertEquals([
            'consulta_id' => $cobro->consulta->id,
            'cedula' => '1713175071',
            'api_key_id' => $this->apiKey->id,
            'creditos_gastados' => 3,
            'saldo_anterior' => 10,
            'saldo_nuevo' => 7,
            'motivo' => 'consulta.cedula',
        ], $log->detalle);
    }

    public function test_cobrar_con_saldo_insuficiente_lanza_excepcion_sin_efectos(): void
    {
        try {
            $this->service->cobrar($this->cliente, $this->apiKey, 'ruc', '0100001437001', 11, null, ['catastro_sri'], '10.0.0.1');
            $this->fail('Se esperaba SaldoInsuficienteException');
        } catch (SaldoInsuficienteException $e) {
            $this->assertSame(10.0, $e->saldoActual);
            $this->assertSame(11, $e->costo);
        }

        $this->assertSame(10.0, (float) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(0, Consulta::count());
        $this->assertSame(0, LogActividad::count());
    }

    public function test_rollback_completo_si_falla_el_log(): void
    {
        LogActividad::creating(fn () => throw new RuntimeException('log caído'));

        try {
            $this->service->cobrar($this->cliente, $this->apiKey, 'cedula', '1713175071', 1, null, [], '10.0.0.1');
            $this->fail('Se esperaba RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertSame('log caído', $e->getMessage());
        }

        $this->assertSame(10.0, (float) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(0, Consulta::count());
    }

    public function test_registrar_sin_cobro_no_toca_saldo(): void
    {
        $registro = $this->service->registrarSinCobro($this->cliente, $this->apiKey, 'cedula', '1713175071', '10.0.0.1');

        $this->assertSame(10.0, (float) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(0, $registro->consulta->creditos_gastados);
        $this->assertFalse($registro->consulta->exitosa);

        $detalle = LogActividad::sole()->detalle;
        $this->assertSame(0, $detalle['creditos_gastados']);
        $this->assertEquals(10, $detalle['saldo_anterior']);
        $this->assertEquals(10, $detalle['saldo_nuevo']);
    }

    public function test_costo_consulta_ignora_costo_base_de_otro_modulo(): void
    {
        ConfigParametro::create(['modulo' => 'consulta', 'clave' => 'costoConsultaBase', 'valor' => json_encode(5)]);

        $this->assertSame(1, $this->service->costoConsulta());

        ConfigParametro::create(['modulo' => 'financiero', 'clave' => 'costoConsultaBase', 'valor' => json_encode('3')]);

        $this->assertSame(3, $this->service->costoConsulta());
    }
}
