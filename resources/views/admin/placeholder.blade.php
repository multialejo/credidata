<x-app-layout>
    <x-slot name="header">
        <x-page-header eyebrow="Administración" :title="$titulo" description="Este módulo estará disponible en una próxima actualización." class="mb-0" />
    </x-slot>

    <x-page-shell max-width="7xl">
            <section class="ui-card p-6">
                <div>
                    <p>Módulo en construcción — Sprint 7.</p>
                    <p class="mt-2 text-sm text-slate-500">Título actual: <strong>{{ $titulo }}</strong></p>
                </div>
            </section>
    </x-page-shell>
</x-app-layout>
