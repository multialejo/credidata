<?php

namespace Tests\Unit;

use App\Models\ConfigParametro;
use App\Services\DinardapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DinardapServiceTest extends TestCase
{
    use RefreshDatabase;

    private TestableDinardapService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TestableDinardapService();
    }

    // --- normalizar() ---

    public function test_normalizar_returns_defaults_on_null(): void
    {
        $result = $this->service->publicNormalizar(null);

        $this->assertEquals('', $result['nombres']);
        $this->assertNull($result['fechaNacimiento']);
        $this->assertNull($result['lugarNacimiento']);
        $this->assertNull($result['estadoCivilCodigo']);
        $this->assertNull($result['conyuge']);
        $this->assertEquals('', $result['ubicacion']['provincia']);
        $this->assertEquals('', $result['ubicacion']['canton']);
        $this->assertEquals('', $result['ubicacion']['parroquia']);
    }

    public function test_normalizar_maps_camelCase_keys(): void
    {
        $input = [
            'nombres' => 'Juan',
            'fechaNacimiento' => '1990-01-01',
            'lugarNacimiento' => 'Quito',
            'estadoCivilCodigo' => 1,
            'conyuge' => 'María',
            'ubicacion' => [
                'provincia' => 'Pichincha',
                'canton' => 'Quito',
                'parroquia' => 'La Carolina',
            ],
        ];

        $result = $this->service->publicNormalizar($input);

        $this->assertEquals('Juan', $result['nombres']);
        $this->assertEquals('1990-01-01', $result['fechaNacimiento']);
        $this->assertEquals('Quito', $result['lugarNacimiento']);
        $this->assertEquals(1, $result['estadoCivilCodigo']);
        $this->assertEquals('María', $result['conyuge']);
        $this->assertEquals('Pichincha', $result['ubicacion']['provincia']);
    }

    public function test_normalizar_maps_snake_case_keys(): void
    {
        $input = [
            'nombre' => 'Carlos',
            'fecha_nacimiento' => '1985-06-15',
            'lugar_nacimiento' => 'Guayaquil',
            'estado_civil_codigo' => 2,
            'nombreConyuge' => 'Ana',
        ];

        $result = $this->service->publicNormalizar($input);

        $this->assertEquals('Carlos', $result['nombres']);
        $this->assertEquals('1985-06-15', $result['fechaNacimiento']);
        $this->assertEquals('Guayaquil', $result['lugarNacimiento']);
        $this->assertEquals(2, $result['estadoCivilCodigo']);
    }

    public function test_normalizar_maps_nombreCompleto_to_nombres(): void
    {
        $result = $this->service->publicNormalizar(['nombreCompleto' => 'Pedro Pablo']);

        $this->assertEquals('Pedro Pablo', $result['nombres']);
    }

    public function test_normalizar_maps_flat_ubicacion(): void
    {
        $input = [
            'provincia' => 'Azuay',
            'canton' => 'Cuenca',
            'parroquia' => 'El Valle',
        ];

        $result = $this->service->publicNormalizar($input);

        $this->assertEquals('Azuay', $result['ubicacion']['provincia']);
        $this->assertEquals('Cuenca', $result['ubicacion']['canton']);
        $this->assertEquals('El Valle', $result['ubicacion']['parroquia']);
    }

    public function test_normalizar_fills_missing_fields(): void
    {
        $result = $this->service->publicNormalizar([]);

        $this->assertEquals('', $result['nombres']);
        $this->assertNull($result['fechaNacimiento']);
        $this->assertNull($result['lugarNacimiento']);
        $this->assertNull($result['estadoCivilCodigo']);
        $this->assertNull($result['conyuge']);
    }

    // --- cacheEsValido() ---

    public function test_cache_es_valido_returns_false_on_null(): void
    {
        $this->assertFalse($this->service->publicCacheEsValido(null));
    }

    public function test_cache_es_valido_returns_false_without_timestamp(): void
    {
        $this->assertFalse($this->service->publicCacheEsValido([]));
    }

    public function test_cache_es_valido_returns_true_within_ttl(): void
    {
        ConfigParametro::create([
            'modulo' => 'dinardap',
            'clave' => 'cacheTTLMinutos',
            'valor' => json_encode('1440'),
        ]);

        $cache = ['ultimaActualizacion' => now()->toIso8601String()];

        $this->assertTrue($this->service->publicCacheEsValido($cache));
    }

    public function test_cache_es_valido_returns_false_when_expired(): void
    {
        ConfigParametro::create([
            'modulo' => 'dinardap',
            'clave' => 'cacheTTLMinutos',
            'valor' => json_encode('10'),
        ]);

        $cache = ['ultimaActualizacion' => now()->subHours(2)->toIso8601String()];

        $this->assertFalse($this->service->publicCacheEsValido($cache));
    }

    public function test_cache_es_valido_uses_default_ttl_when_no_config_param(): void
    {
        $cache = ['ultimaActualizacion' => now()->toIso8601String()];

        $this->assertTrue($this->service->publicCacheEsValido($cache));
    }

    public function test_cache_es_valido_uses_default_ttl_when_expired(): void
    {
        $cache = ['ultimaActualizacion' => now()->subDays(2)->toIso8601String()];

        $this->assertFalse($this->service->publicCacheEsValido($cache));
    }
}

class TestableDinardapService extends DinardapService
{
    public function publicNormalizar(?array $raw): array
    {
        return $this->normalizar($raw);
    }

    public function publicCacheEsValido(?array $cache): bool
    {
        return $this->cacheEsValido($cache);
    }
}
