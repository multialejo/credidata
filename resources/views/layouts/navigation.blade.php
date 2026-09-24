@props(['variant' => 'desktop'])

<div
    x-data="{
        sidebarOpen: false,
        openSidebar() {
            this.sidebarOpen = true;
            document.body.classList.add('overflow-hidden');
        },
        closeSidebar() {
            this.sidebarOpen = false;
            document.body.classList.remove('overflow-hidden');
            this.$nextTick(() => this.$refs.openSidebar.focus());
        },
        trapFocus(event) {
            if (event.key !== 'Tab') return;

            const focusable = [...this.$refs.mobileDrawer.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex=-1])')];
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }"
    x-init="$watch('sidebarOpen', value => { if (value) $nextTick(() => $refs.closeSidebar.focus()) })"
    x-on:keydown.escape.window="if (sidebarOpen) closeSidebar()"
>

    {{-- Desktop sidebar (lg+) --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:flex lg:h-full lg:w-64 lg:flex-col" aria-label="Menú de navegación">
        <div class="flex h-full flex-col overflow-hidden bg-[#14213d] shadow-[4px_0_24px_-10px_rgba(20,33,61,0.35)]">
            {{-- Logo --}}
            <a data-testid="home-link" href="{{ auth()->user()->staff ? route('admin.clientes') : route('dashboard') }}" class="flex items-center gap-3 px-6 py-6 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#14213d]">
                <x-application-logo class="h-9 w-9 shrink-0 text-white" />
                <span class="text-xl font-extrabold tracking-tight text-white">CrediData</span>
            </a>

            @if(!auth()->user()->staff)
                {{-- Balance card (clients only) --}}
                <a href="{{ route('dashboard.recargas') }}" class="group mx-4 mb-4 block rounded-xl border border-slate-200 bg-white p-4 shadow-[0_12px_35px_-24px_rgba(20,33,61,0.45)] transition hover:border-[#3155d9]/50 hover:shadow-[0_18px_50px_-24px_rgba(49,85,217,0.5)] focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2 focus:ring-offset-[#14213d]" aria-label="Recargar saldo">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Saldo disponible</p>
                    <div class="mt-2 flex items-end justify-between">
                        <p class="text-2xl font-bold tracking-tight text-[#14213d]">{{ number_format(auth()->user()->cliente?->saldo_creditos ?? 0, 0) }} <span class="ml-1 text-base font-medium text-slate-500">créditos</span></p>
                        <x-icons.credit-card class="h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-[#3155d9]" />
                    </div>
                    <p class="mt-3 inline-flex items-center text-sm font-semibold text-[#3155d9]">
                        Recargar saldo
                        <span class="ml-1 transition group-hover:translate-x-0.5" aria-hidden="true">&rarr;</span>
                    </p>
                </a>
            @endif

            {{-- User card --}}
            @if(!auth()->user()->staff)
                <x-dropdown align="top-right" width="48" class="mx-4">
                    <x-slot name="trigger">
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">
                                    {{ str(Auth::user()->name)->substr(0, 1)->upper() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="truncate text-base font-medium text-slate-300">{{ Auth::user()->name }}</p>
                                    <p class="truncate text-sm text-slate-400">{{ Auth::user()->email }}</p>
                                </div>
                                <x-icons.ellipsis-vertical class="h-4 w-4 shrink-0 text-slate-400" />
                            </div>
                        </div>
                    </x-slot>
                    <x-slot name="content">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            @else
                {{-- Staff user section --}}
                <x-dropdown align="top-right" width="48">
                    <x-slot name="trigger">
                        <div class="flex items-center gap-3 border-t border-white/10 px-5 py-4 transition hover:bg-white/10">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">
                                {{ str(Auth::user()->name)->substr(0, 1)->upper() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="truncate text-base font-medium text-slate-300">{{ Auth::user()->name }}</p>
                                <p class="truncate text-sm text-slate-400">{{ Auth::user()->email }}</p>
                            </div>
                            <x-icons.chevron-down class="h-4 w-4 shrink-0 text-slate-400" />
                        </div>
                    </x-slot>
                    <x-slot name="content">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            @endif

            {{-- Scrollable sidebar body --}}
            <nav class="flex-1 overflow-y-auto scrollbar-none px-4 pb-4">
                <div class="flex flex-col gap-y-1">
                    @include('partials.sidebar-items', ['variant' => 'desktop'])
                </div>
            </nav>
        </div>
    </aside>

    {{-- Mobile header (visible below lg) --}}
    <div class="sticky top-0 z-30 flex h-14 items-center gap-3 bg-[#14213d] px-4 shadow-md lg:hidden">
        <button x-ref="openSidebar" @click="openSidebar()" aria-controls="mobile-navigation-drawer" :aria-expanded="sidebarOpen.toString()" class="rounded-lg p-1.5 text-slate-300 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white" aria-label="Abrir menú">
            <x-icons.bars-3 class="h-6 w-6" />
        </button>
        <a data-testid="home-link" href="{{ auth()->user()->staff ? route('admin.clientes') : route('dashboard') }}" class="flex items-center gap-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-white">
            <x-application-logo class="h-8 w-8 shrink-0 text-white" />
            <span class="text-xl font-extrabold tracking-tight text-white">CrediData</span>
        </a>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 lg:hidden" style="display: none;" aria-label="Navegación móvil">
        {{-- Backdrop --}}
        <button type="button" class="absolute inset-0 h-full w-full cursor-default bg-[#14213d]/60" @click="closeSidebar()" aria-label="Cerrar menú"></button>

        {{-- Drawer panel --}}
        <aside id="mobile-navigation-drawer" x-ref="mobileDrawer" x-show="sidebarOpen" x-on:keydown="trapFocus($event)" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="absolute inset-y-0 left-0 z-50 flex w-72 flex-col bg-[#14213d]" role="dialog" aria-modal="true" aria-label="Menú principal">
            {{-- Close button --}}
            <div class="flex items-center justify-between px-4 pt-5 pb-2">
                <a data-testid="home-link" href="{{ auth()->user()->staff ? route('admin.clientes') : route('dashboard') }}" class="flex items-center gap-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-white">
                    <x-application-logo class="h-8 w-8 shrink-0 text-white" />
                    <span class="text-xl font-extrabold tracking-tight text-white">CrediData</span>
                </a>
                <button x-ref="closeSidebar" type="button" @click="closeSidebar()" class="rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white" aria-label="Cerrar menú">
                    <x-icons.x-mark class="h-5 w-5" />
                </button>
            </div>

            @if(!auth()->user()->staff)
                {{-- Balance card (mobile, clients only) --}}
                <a href="{{ route('dashboard.recargas') }}" class="group mx-3 mb-2 block rounded-xl border border-slate-200 bg-white p-3 shadow-[0_12px_35px_-24px_rgba(20,33,61,0.45)] transition hover:border-[#3155d9]/50 hover:shadow-[0_18px_50px_-24px_rgba(49,85,217,0.5)] focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2 focus:ring-offset-[#14213d]" aria-label="Recargar saldo">
                    <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-slate-500">Saldo disponible</p>
                    <div class="mt-1.5 flex items-end justify-between">
                        <p class="text-xl font-bold tracking-tight text-[#14213d]">{{ number_format(auth()->user()->cliente?->saldo_creditos ?? 0, 0) }} <span class="ml-1 text-sm font-medium text-slate-500">créditos</span></p>
                        <x-icons.credit-card class="h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-[#3155d9]" />
                    </div>
                    <p class="mt-2 inline-flex items-center text-sm font-semibold text-[#3155d9]">
                        Recargar saldo
                        <span class="ml-1 transition group-hover:translate-x-0.5" aria-hidden="true">&rarr;</span>
                    </p>
                </a>
            @endif

            {{-- User card --}}
            @if(!auth()->user()->staff)
                <x-dropdown align="top-right" width="48" class="mx-3">
                    <x-slot name="trigger">
                        <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 p-3 transition hover:bg-white/10">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">
                                {{ str(Auth::user()->name)->substr(0, 1)->upper() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="truncate text-base font-medium text-slate-300">{{ Auth::user()->name }}</p>
                                <p class="truncate text-sm text-slate-400">{{ Auth::user()->email }}</p>
                            </div>
                            <x-icons.ellipsis-vertical class="h-4 w-4 shrink-0 text-slate-400" />
                        </div>
                    </x-slot>
                    <x-slot name="content">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            @else
                {{-- Staff user section --}}
                <x-dropdown align="top-right" width="48">
                    <x-slot name="trigger">
                        <div class="flex items-center gap-3 px-3 py-2 transition hover:bg-white/10">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">
                                {{ str(Auth::user()->name)->substr(0, 1)->upper() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="truncate text-base font-medium text-slate-300">{{ Auth::user()->name }}</p>
                                <p class="truncate text-sm text-slate-400">{{ Auth::user()->email }}</p>
                            </div>
                            <x-icons.chevron-down class="h-4 w-4 shrink-0 text-slate-400" />
                        </div>
                    </x-slot>
                    <x-slot name="content">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            @endif

            {{-- Scrollable body --}}
            <nav class="flex flex-1 flex-col gap-y-1 overflow-y-auto scrollbar-none px-3 pb-4 pt-2">
                @include('partials.sidebar-items', ['variant' => 'mobile'])
            </nav>
        </aside>
    </div>
</div>
