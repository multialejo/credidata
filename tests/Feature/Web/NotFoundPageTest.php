<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    public function test_missing_page_shows_the_custom_404_page_and_guest_recovery_link(): void
    {
        $this->get('/pagina-inexistente')
            ->assertNotFound()
            ->assertSeeText('Esta página no aparece en el mapa.')
            ->assertSee('href="'.url('/').'"', false)
            ->assertSeeText('Volver al inicio');
    }
}
