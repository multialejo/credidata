<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    public function test_renders_the_standard_table_structure(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-data-table title="Consultas" description="Historial de consultas.">
                <x-slot:actions><button type="button">Exportar</button></x-slot:actions>
                <x-slot:filters><input aria-label="Buscar"></x-slot:filters>
                <thead><tr><th scope="col">Fecha</th></tr></thead>
                <tbody><tr><td>21/09/2026</td></tr></tbody>
                <x-slot:pagination>1</x-slot:pagination>
            </x-data-table>
        BLADE);

        $this->assertStringContainsString('ui-data-table', $html);
        $this->assertStringContainsString('ui-data-table__filters', $html);
        $this->assertStringContainsString('ui-data-table__pagination', $html);
        $this->assertStringContainsString('<table class="ui-data-table__table">', $html);
        $this->assertStringContainsString('<caption class="sr-only">Consultas</caption>', $html);
        $this->assertStringContainsString('Historial de consultas.', $html);
    }
}
