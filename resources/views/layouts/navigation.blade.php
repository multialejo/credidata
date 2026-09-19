@props(['variant' => 'desktop'])

<div x-data="{ sidebarOpen: false }">

    {{-- Desktop sidebar (lg+) --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:flex lg:w-64 lg:flex-col" aria-label="Menú de navegación">
        <div class="flex grow flex-col gap-y-6 overflow-y-auto bg-[#14213d] px-4 pb-6 pt-6 shadow-[4px_0_24px_-10px_rgba(20,33,61,0.35)]">
            {{-- Logo --}}
            <a data-testid="home-link" href="{{ auth()->user()->staff ? route('admin.clientes') : route('dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#14213d]">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#3155d9] text-sm font-black tracking-tight text-white">C</span>
                <span class="text-base font-bold tracking-tight text-white">Credidata</span>
            </a>

            {{-- Navigation --}}
            <nav class="flex flex-1 flex-col gap-y-1">
                @include('partials.sidebar-items', ['variant' => 'desktop'])
            </nav>

            {{-- User section --}}
            <div class="flex items-center gap-3 border-t border-white/10 px-3 pt-4">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">
                    {{ str(Auth::user()->name)->substr(0, 1)->upper() }}
                </div>
                <div class="flex flex-1 items-center justify-between min-w-0">
                    <span class="truncate text-sm font-medium text-slate-300">{{ Auth::user()->name }}</span>
                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button class="flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white" aria-label="Menú de usuario">
                                <x-icons.chevron-down class="h-4 w-4" />
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
            </div>
        </div>
    </aside>

    {{-- Mobile header (visible below lg) --}}
    <div class="sticky top-0 z-30 flex h-14 items-center gap-3 bg-[#14213d] px-4 shadow-md lg:hidden">
        <button @click="sidebarOpen = true" class="rounded-lg p-1.5 text-slate-300 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white" aria-label="Abrir menú">
            <x-icons.bars-3 class="h-6 w-6" />
        </button>
        <a data-testid="home-link" href="{{ auth()->user()->staff ? route('admin.clientes') : route('dashboard') }}" class="flex items-center gap-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-white">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#3155d9] text-xs font-black tracking-tight text-white">C</span>
            <span class="text-sm font-bold tracking-tight text-white">Credidata</span>
        </a>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 lg:hidden" style="display: none;">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50" @click="sidebarOpen = false"></div>

        {{-- Drawer panel --}}
        <aside x-show="sidebarOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="absolute inset-y-0 left-0 z-50 flex w-72 flex-col bg-[#14213d]">
            {{-- Close button --}}
            <div class="flex items-center justify-between px-4 pt-5 pb-2">
                <a data-testid="home-link" href="{{ auth()->user()->staff ? route('admin.clientes') : route('dashboard') }}" class="flex items-center gap-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-white">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#3155d9] text-xs font-black tracking-tight text-white">C</span>
                    <span class="text-sm font-bold tracking-tight text-white">Credidata</span>
                </a>
                <button @click="sidebarOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white" aria-label="Cerrar menú">
                    <x-icons.x-mark class="h-5 w-5" />
                </button>
            </div>

            {{-- Navigation --}}
            <nav class="flex flex-1 flex-col gap-y-1 overflow-y-auto px-3 pb-4 pt-2">
                @include('partials.sidebar-items', ['variant' => 'mobile'])
            </nav>

            {{-- User section --}}
            <div class="border-t border-white/10 px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">
                        {{ str(Auth::user()->name)->substr(0, 1)->upper() }}
                    </div>
                    <span class="truncate text-sm font-medium text-slate-300">{{ Auth::user()->name }}</span>
                </div>
                <div class="mt-3 flex flex-col gap-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">Perfil</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-responsive-nav-link>
                    </form>
                </div>
            </div>
        </aside>
    </div>
</div>
