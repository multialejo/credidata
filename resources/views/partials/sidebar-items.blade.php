@props(['variant' => 'desktop'])

@php
$isDesktop = $variant === 'desktop';
@endphp

@if(auth()->user()->staff)
    @if($isDesktop)
        <x-nav-link :href="route('admin.clientes')" :active="request()->routeIs('admin.clientes')" icon="users">Clientes</x-nav-link>
        <x-nav-link :href="route('admin.logs')" :active="request()->routeIs('admin.logs')" icon="document-text">Logs</x-nav-link>
        <x-nav-link :href="route('admin.config')" :active="request()->routeIs('admin.config')" icon="cog-6-tooth">Configuración</x-nav-link>
        <x-nav-link :href="route('admin.registros')" :active="request()->routeIs('admin.registros')" icon="clipboard-document-list">Registros</x-nav-link>
        <x-nav-link :href="route('admin.recargas')" :active="request()->routeIs('admin.recargas')" icon="arrow-path">Recargas</x-nav-link>
        <x-nav-link :href="route('admin.aportes')" :active="request()->routeIs('admin.aportes')" icon="sparkles">Aportes</x-nav-link>
    @else
        <x-responsive-nav-link :href="route('admin.clientes')" :active="request()->routeIs('admin.clientes')" icon="users">Clientes</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.logs')" :active="request()->routeIs('admin.logs')" icon="document-text">Logs</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.config')" :active="request()->routeIs('admin.config')" icon="cog-6-tooth">Configuración</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.registros')" :active="request()->routeIs('admin.registros')" icon="clipboard-document-list">Registros</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.recargas')" :active="request()->routeIs('admin.recargas')" icon="arrow-path">Recargas</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.aportes')" :active="request()->routeIs('admin.aportes')" icon="sparkles">Aportes</x-responsive-nav-link>
    @endif
@else
    @if($isDesktop)
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Resumen</x-nav-link>
        <x-nav-link :href="route('dashboard.consultas')" :active="request()->routeIs('dashboard.consultas')" icon="clock">Historial</x-nav-link>
        <x-nav-link :href="route('dashboard.api-key')" :active="request()->routeIs('dashboard.api-key')" icon="key">API Key</x-nav-link>
        <x-nav-link :href="route('dashboard.recibos')" :active="request()->routeIs('dashboard.recibos')" icon="document-text">Recibos</x-nav-link>
        <x-nav-link :href="route('dashboard.colaborador')" :active="request()->routeIs('dashboard.colaborador')" icon="user-group">Colaborador</x-nav-link>
        @if(auth()->user()->colaborador?->estado_colaborador === 'activo')
            <x-nav-link :href="route('dashboard.aportes.nuevo')" :active="request()->routeIs('dashboard.aportes.nuevo')" icon="plus-circle">Aportar</x-nav-link>
        @endif
    @else
        <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Resumen</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.consultas')" :active="request()->routeIs('dashboard.consultas')" icon="clock">Historial</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.api-key')" :active="request()->routeIs('dashboard.api-key')" icon="key">API Key</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.recibos')" :active="request()->routeIs('dashboard.recibos')" icon="document-text">Recibos</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.colaborador')" :active="request()->routeIs('dashboard.colaborador')" icon="user-group">Colaborador</x-responsive-nav-link>
        @if(auth()->user()->colaborador?->estado_colaborador === 'activo')
            <x-responsive-nav-link :href="route('dashboard.aportes.nuevo')" :active="request()->routeIs('dashboard.aportes.nuevo')" icon="plus-circle">Aportar</x-responsive-nav-link>
        @endif
    @endif
@endif
