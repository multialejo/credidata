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
    <p class="px-3 pt-2 pb-1 text-[0.7rem] font-bold uppercase tracking-[0.18em] text-slate-500">Mi cuenta</p>
    @if($isDesktop)
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Saldo</x-nav-link>
        <x-nav-link :href="route('dashboard.consultas')" :active="request()->routeIs('dashboard.consultas')" icon="clock">Historial</x-nav-link>
        <x-nav-link :href="route('dashboard.api-key')" :active="request()->routeIs('dashboard.api-key')" icon="key">API Keys</x-nav-link>
        <x-nav-link :href="route('dashboard.recibos')" :active="request()->routeIs('dashboard.recibos')" icon="document-text">Recibos</x-nav-link>
        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')" icon="cog-6-tooth">Configuración</x-nav-link>
    @else
        <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Saldo</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.consultas')" :active="request()->routeIs('dashboard.consultas')" icon="clock">Historial</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.api-key')" :active="request()->routeIs('dashboard.api-key')" icon="key">API Keys</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.recibos')" :active="request()->routeIs('dashboard.recibos')" icon="document-text">Recibos</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')" icon="cog-6-tooth">Configuración</x-responsive-nav-link>
    @endif

    <p class="px-3 pt-4 pb-1 text-[0.7rem] font-bold uppercase tracking-[0.18em] text-slate-500">Desarrolladores</p>
    @if($isDesktop)
        <span class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-base font-medium text-slate-500 opacity-50 cursor-not-allowed select-none" aria-disabled="true">
            <x-icons.book-open class="h-5 w-5 shrink-0" />
            Documentación
        </span>
        <span class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-base font-medium text-slate-500 opacity-50 cursor-not-allowed select-none" aria-disabled="true">
            <x-icons.currency-dollar class="h-5 w-5 shrink-0" />
            Precios
        </span>
        <x-nav-link :href="route('dashboard.colaborador')" :active="request()->routeIs('dashboard.colaborador')" icon="user-group">Colaborador</x-nav-link>
        @if(auth()->user()->colaborador?->estado_colaborador === 'activo')
            <x-nav-link :href="route('dashboard.aportes.nuevo')" :active="request()->routeIs('dashboard.aportes.nuevo')" icon="plus-circle">Aportar</x-nav-link>
        @endif
    @else
        <span class="flex items-center gap-3 w-full rounded-lg px-3 py-2.5 text-start text-base font-medium text-slate-500 opacity-50 cursor-not-allowed select-none" aria-disabled="true">
            <x-icons.book-open class="h-5 w-5 shrink-0" />
            Documentación
        </span>
        <span class="flex items-center gap-3 w-full rounded-lg px-3 py-2.5 text-start text-base font-medium text-slate-500 opacity-50 cursor-not-allowed select-none" aria-disabled="true">
            <x-icons.currency-dollar class="h-5 w-5 shrink-0" />
            Precios
        </span>
        <x-responsive-nav-link :href="route('dashboard.colaborador')" :active="request()->routeIs('dashboard.colaborador')" icon="user-group">Colaborador</x-responsive-nav-link>
        @if(auth()->user()->colaborador?->estado_colaborador === 'activo')
            <x-responsive-nav-link :href="route('dashboard.aportes.nuevo')" :active="request()->routeIs('dashboard.aportes.nuevo')" icon="plus-circle">Aportar</x-responsive-nav-link>
        @endif
    @endif
@endif
