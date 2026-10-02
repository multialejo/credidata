<x-mail::message>
@if (! empty($greeting))
# {{ $greeting }}
@else
# @lang('Hello!')
@endif

@foreach ($introLines as $line)
{{ $line }}

@endforeach

@isset($actionText)
<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>
@endisset

@foreach ($outroLines as $line)
{{ $line }}

@endforeach

@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards,')<br>
{{ config('app.name') }}
@endif
</x-mail::message>
