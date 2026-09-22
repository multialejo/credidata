@props([
    'title' => null,
    'description' => null,
    'caption' => null,
])

<section {{ $attributes->class('ui-data-table') }}>
    <div wire:loading.flex role="status" class="ui-data-table__loading">
        <span class="sr-only">Actualizando resultados.</span>
    </div>
    @if($title || isset($actions))
        <header class="ui-data-table__header">
            @if($title)
                <div>
                    <h2 class="ui-data-table__title">{{ $title }}</h2>
                    @if($description)
                        <p class="ui-data-table__description">{{ $description }}</p>
                    @endif
                </div>
            @endif

            @isset($actions)
                <div class="ui-data-table__actions">
                    {{ $actions }}
                </div>
            @endisset
        </header>
    @endif

    @isset($filters)
        <div {{ $filters->attributes->class('ui-data-table__filters') }}>
            {{ $filters }}
        </div>
    @endisset

    <div class="ui-data-table__scroll">
        <table class="ui-data-table__table">
            @if($caption || $title)
                <caption class="sr-only">{{ $caption ?: $title }}</caption>
            @endif
            {{ $slot }}
        </table>
    </div>

    @isset($pagination)
        <div class="ui-data-table__pagination">
            {{ $pagination }}
        </div>
    @endisset

</section>
