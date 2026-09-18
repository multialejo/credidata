<nav x-data="{ open: false }" class="bg-[#14213d] text-white shadow-[0_10px_30px_-20px_rgba(20,33,61,0.8)]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-[4.5rem] items-center justify-between gap-6">
            <div class="flex min-w-0 items-center gap-8">
                <a data-testid="home-link" href="{{ auth()->user()->staff ? route('admin.clientes') : route('dashboard') }}" class="flex shrink-0 items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#14213d]">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#3155d9] text-sm font-black tracking-tight text-white">C</span>
                    <span class="hidden text-base font-bold tracking-tight sm:block">Credidata</span>
                </a>

                <div class="hidden items-center gap-1 lg:flex">
                    @if(auth()->user()->staff)
                        <x-nav-link :href="route('admin.clientes')" :active="request()->routeIs('admin.clientes')">Clientes</x-nav-link>
                        <x-nav-link :href="route('admin.logs')" :active="request()->routeIs('admin.logs')">Logs</x-nav-link>
                        <x-nav-link :href="route('admin.config')" :active="request()->routeIs('admin.config')">Configuración</x-nav-link>
                        <x-nav-link :href="route('admin.registros')" :active="request()->routeIs('admin.registros')">Registros</x-nav-link>
                        <x-nav-link :href="route('admin.recargas')" :active="request()->routeIs('admin.recargas')">Recargas</x-nav-link>
                        <x-nav-link :href="route('admin.aportes')" :active="request()->routeIs('admin.aportes')">Aportes</x-nav-link>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Resumen</x-nav-link>
                        <x-nav-link :href="route('dashboard.consultas')" :active="request()->routeIs('dashboard.consultas')">Historial</x-nav-link>
                        <x-nav-link :href="route('dashboard.api-key')" :active="request()->routeIs('dashboard.api-key')">API Key</x-nav-link>
                        <x-nav-link :href="route('dashboard.recibos')" :active="request()->routeIs('dashboard.recibos')">Recibos</x-nav-link>
                        <x-nav-link :href="route('dashboard.colaborador')" :active="request()->routeIs('dashboard.colaborador')">Colaborador</x-nav-link>
                        @if(auth()->user()->colaborador?->estado_colaborador === 'activo')
                            <x-nav-link :href="route('dashboard.aportes.nuevo')" :active="request()->routeIs('dashboard.aportes.nuevo')">Aportar</x-nav-link>
                        @endif
                    @endif
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                <span class="max-w-40 truncate text-sm text-slate-300">{{ Auth::user()->name }}</span>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#14213d]" aria-label="Abrir menú de usuario">
                            {{ str(Auth::user()->name)->substr(0, 1)->upper() }}
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <button @click="open = ! open" class="rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white sm:hidden" aria-label="Abrir navegación" :aria-expanded="open.toString()">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path :class="{ 'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /><path :class="{ 'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div x-show="open" x-transition class="border-t border-white/10 py-3 sm:hidden">
            <div class="space-y-1">
                @if(auth()->user()->staff)
                    <x-responsive-nav-link :href="route('admin.clientes')" :active="request()->routeIs('admin.clientes')">Clientes</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.logs')" :active="request()->routeIs('admin.logs')">Logs</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.config')" :active="request()->routeIs('admin.config')">Configuración</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.registros')" :active="request()->routeIs('admin.registros')">Registros</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.recargas')" :active="request()->routeIs('admin.recargas')">Recargas</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.aportes')" :active="request()->routeIs('admin.aportes')">Aportes</x-responsive-nav-link>
                @else
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Resumen</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('dashboard.consultas')" :active="request()->routeIs('dashboard.consultas')">Historial</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('dashboard.api-key')" :active="request()->routeIs('dashboard.api-key')">API Key</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('dashboard.recibos')" :active="request()->routeIs('dashboard.recibos')">Recibos</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('dashboard.colaborador')" :active="request()->routeIs('dashboard.colaborador')">Colaborador</x-responsive-nav-link>
                    @if(auth()->user()->colaborador?->estado_colaborador === 'activo')
                        <x-responsive-nav-link :href="route('dashboard.aportes.nuevo')" :active="request()->routeIs('dashboard.aportes.nuevo')">Aportar</x-responsive-nav-link>
                    @endif
                @endif
            </div>
            <div class="mt-3 border-t border-white/10 pt-3">
                <div class="px-3 text-sm text-slate-300">{{ Auth::user()->name }}</div>
                <x-responsive-nav-link :href="route('profile.edit')">Perfil</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
