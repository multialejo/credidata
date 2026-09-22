@props([
    'title',
    'description' => null,
])

<section {{ $attributes->class('ui-data-table') }}>
    <header class="ui-data-table__header">
        <div>
            <h2 class="ui-data-table__title">{{ $title }}</h2>
            @if($description)
                <p class="ui-data-table__description">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="ui-data-table__actions">
                {{ $actions }}
            </div>
        @endisset
    </header>

    @isset($filters)
        <div class="ui-data-table__filters">
            {{ $filters }}
        </div>
    @endisset

    <div class="ui-data-table__scroll">
        <table class="ui-data-table__table">
            <caption class="sr-only">{{ $title }}</caption>
            {{ $slot }}
        </table>
    </div>

    @isset($pagination)
        <div class="ui-data-table__pagination">
            {{ $pagination }}
        </div>
    @endisset

    <p wire:loading.delay role="status" class="sr-only">Actualizando resultados.</p>
</section>
