<?php

namespace Tests\Feature\Console;

use App\Services\CatastroService;
use Mockery\MockInterface;
use Tests\TestCase;

class CatastroTestCommandTest extends TestCase
{
    public function test_command_reports_normalized_establishments(): void
    {
        $this->mock(CatastroService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('buscarEstablecimientos')->once()->andReturn([
                ['numero' => '001', 'razonSocial' => 'EMPRESA', 'actividadEconomica' => [], 'ubicacion' => [], 'fechas' => [], 'contacto' => [],
                    'estadoContribuyente' => 'ACTIVO', 'estadoEstablecimiento' => 'ABI', 'regimenRimpe' => null,
                    'agenteRetencion' => false, 'contribuyenteEspecial' => false, 'artesanoCalificado' => false],
            ]);
        });

        $this->artisan('catastro:test', ['ruc' => '0100001437001'])
            ->expectsOutputToContain('Colección: catastro_sri')
            ->expectsOutputToContain('Establecimientos: 1')
            ->assertExitCode(0);
    }

    public function test_command_rejects_invalid_ruc(): void
    {
        $this->artisan('catastro:test', ['ruc' => '1713175071'])
            ->expectsOutputToContain('RUC inválido')
            ->assertExitCode(1);
    }
}
