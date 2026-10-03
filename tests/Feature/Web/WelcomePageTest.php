<?php

namespace Tests\Feature\Web;

use App\Models\ConfigParametro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_current_pricing_and_fictional_consistent_examples(): void
    {
        ConfigParametro::create([
            'modulo' => 'financiero',
            'clave' => 'costoConsultaBase',
            'valor' => json_encode(3),
        ]);
        ConfigParametro::create([
            'modulo' => 'financiero',
            'clave' => 'tasaCambioUsdCreditos',
            'valor' => json_encode(10),
        ]);
        ConfigParametro::create([
            'modulo' => 'financiero',
            'clave' => 'recargaMinimaUsd',
            'valor' => json_encode(2.50),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSeeText('3 créditos (aproximadamente $0,30 USD)')
            ->assertSeeText('Recarga mínima de $2,50 USD.');
    }
}
