@props(['variant' => 'desktop'])

@php
$isDesktop = $variant === 'desktop';
@endphp

@if(auth()->user()->staff)
    @php($esAdmin = auth()->user()->staff->rol_staff === 'admin')
    @if($isDesktop)
        <x-nav-link :href="route('admin.clientes')" :active="request()->routeIs('admin.clientes')" icon="users">Clientes</x-nav-link>
        @if($esAdmin)
            <x-nav-link :href="route('admin.logs')" :active="request()->routeIs('admin.logs')" icon="document-text">Logs</x-nav-link>
            <x-nav-link :href="route('admin.config')" :active="request()->routeIs('admin.config')" icon="cog-6-tooth">Configuración</x-nav-link>
        @endif
        <x-nav-link :href="route('admin.registros')" :active="request()->routeIs('admin.registros')" icon="clipboard-document-list">Edición de Registros</x-nav-link>
        <x-nav-link :href="route('admin.recargas')" :active="request()->routeIs('admin.recargas')" icon="arrow-path">Recargas</x-nav-link>
        <x-nav-link :href="route('admin.aportes')" :active="request()->routeIs('admin.aportes')" icon="sparkles">Aportes</x-nav-link>
    @else
        <x-responsive-nav-link :href="route('admin.clientes')" :active="request()->routeIs('admin.clientes')" icon="users">Clientes</x-responsive-nav-link>
        @if($esAdmin)
            <x-responsive-nav-link :href="route('admin.logs')" :active="request()->routeIs('admin.logs')" icon="document-text">Logs</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.config')" :active="request()->routeIs('admin.config')" icon="cog-6-tooth">Configuración</x-responsive-nav-link>
        @endif
        <x-responsive-nav-link :href="route('admin.registros')" :active="request()->routeIs('admin.registros')" icon="clipboard-document-list">Edición de Registros</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.recargas')" :active="request()->routeIs('admin.recargas')" icon="arrow-path">Recargas</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.aportes')" :active="request()->routeIs('admin.aportes')" icon="sparkles">Aportes</x-responsive-nav-link>
    @endif
@else
    <br>
    <p class="px-3 pt-2 pb-1 text-[0.7rem] font-bold uppercase tracking-[0.18em] text-slate-500">Mi cuenta</p>
    @if($isDesktop)
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Inicio</x-nav-link>
        <x-nav-link :href="route('dashboard.consultas')" :active="request()->routeIs('dashboard.consultas')" icon="clock">Historial</x-nav-link>
        <x-nav-link :href="route('dashboard.api-key')" :active="request()->routeIs('dashboard.api-key')" icon="key">API Key</x-nav-link>
        <x-nav-link :href="route('dashboard.recibos')" :active="request()->routeIs('dashboard.recibos')" icon="document-text">Recibos</x-nav-link>
        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')" icon="user-circle">Perfil</x-nav-link>
    @else
        <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Inicio</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.consultas')" :active="request()->routeIs('dashboard.consultas')" icon="clock">Historial</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.api-key')" :active="request()->routeIs('dashboard.api-key')" icon="key">API Key</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('dashboard.recibos')" :active="request()->routeIs('dashboard.recibos')" icon="document-text">Recibos</x-responsive-nav-link>
        <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')" icon="user-circle">Perfil</x-responsive-nav-link>
    @endif

    <p class="px-3 pt-4 pb-1 text-[0.7rem] font-bold uppercase tracking-[0.18em] text-slate-500">Desarrolladores</p>
    @if($isDesktop)
        <x-nav-link :href="route('dashboard.documentacion')" :active="request()->routeIs('dashboard.documentacion')" icon="book-open">Documentación</x-nav-link>
        <x-nav-link :href="route('dashboard.colaborador')" :active="request()->routeIs('dashboard.colaborador')" icon="user-group">Colaborador</x-nav-link>
        @if(auth()->user()->colaborador?->estado_colaborador === 'activo')
            <x-nav-link :href="route('dashboard.aportes.nuevo')" :active="request()->routeIs('dashboard.aportes.nuevo')" icon="plus-circle">Aportar</x-nav-link>
        @endif
    @else
        <x-responsive-nav-link :href="route('dashboard.documentacion')" :active="request()->routeIs('dashboard.documentacion')" icon="book-open">Documentación</x-responsive-nav-link>
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

<footer class="mt-6 border-t border-white/10 px-3 pt-4 pb-2 text-xs leading-5 text-slate-400">
    <p>© 2026 CrediData</p>
    <p>Desarrolado por <a href="https://softecsa.com" class="font-bold text-blue-600 hover:underline">Softecapps S.A.S.</a> </p>
</footer>
