<?php

namespace Tests\Unit;

use App\Services\CatastroService;
use Tests\TestCase;

class CatastroServiceTest extends TestCase
{
    public function test_normalizes_public_fields_and_repairs_mojibake(): void
    {
        $service = new CatastroService;
        $method = (new \ReflectionClass($service))->getMethod('normalizar');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            'NUMERO_ESTABLECIMIENTO' => '001',
            'RAZON_SOCIAL' => 'EMPRESA',
            'ACTIVIDAD_ECONOMICA' => 'INTERMEDIACIÃN',
            'DESCRIPCION_PARROQUIA_EST' => 'IÃAQUITO',
            'FECHA_INICIO_ACTIVIDADES' => '1993-02-01 00:00:00',
            'agente_retencion' => 'S',
            'correo_electronico' => null,
        ], '0100000017001_001');

        $this->assertSame('001', $result['numero']);
        $this->assertSame('INTERMEDIACIÓN', $result['actividadEconomica']['descripcion']);
        $this->assertSame('IÑAQUITO', $result['ubicacion']['parroquia']);
        $this->assertSame('1993-02-01', $result['fechas']['inicioActividades']);
        $this->assertTrue($result['agenteRetencion']);
        $this->assertNull($result['contacto']['email']);
    }
}
