<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DesignSystemComponentsTest extends TestCase
{
    public function test_renders_the_standard_page_components(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-page-shell max-width="5xl">
                <x-page-header eyebrow="Créditos" title="Recargar créditos" description="Elige un método de pago." />
                <x-alert variant="success">Operación completada.</x-alert>
            </x-page-shell>
        BLADE);

        $this->assertStringContainsString('ui-page-shell', $html);
        $this->assertStringContainsString('max-w-5xl', $html);
        $this->assertStringContainsString('ui-page-title', $html);
        $this->assertStringContainsString('ui-alert ui-alert--success', $html);
        $this->assertStringContainsString('Operación completada.', $html);
    }

    public function test_common_form_components_use_the_shared_visual_api(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" name="email" />
            <x-primary-button>Guardar</x-primary-button>
            <x-secondary-button>Cancelar</x-secondary-button>
        BLADE);

        $this->assertStringContainsString('class="ui-label"', $html);
        $this->assertStringContainsString('class="ui-input block w-full"', $html);
        $this->assertStringContainsString('class="ui-primary-button"', $html);
        $this->assertStringContainsString('class="ui-secondary-button"', $html);
    }
}
