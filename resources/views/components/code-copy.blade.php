@props([
    'title' => 'Terminal',
    'label' => 'Terminal',
])

<div
    x-data="{
        copied: false,
        failed: false,
        async copy() {
            try {
                await navigator.clipboard.writeText(this.$refs.command.innerText.trim());
                this.copied = true;
                this.failed = false;
                setTimeout(() => this.copied = false, 2000);
            } catch {
                this.failed = true;
                this.copied = false;
            }
        }
    }"
    {{ $attributes->class('overflow-hidden rounded-xl bg-[#0d1630] ring-1 ring-[#14213d]/10') }}
>
    <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-2.5">
        <span class="text-xs font-semibold text-slate-300">{{ $label }}</span>
        <div class="flex items-center gap-3">
            <span class="font-mono text-xs text-slate-400">{{ $title }}</span>
            <button
                type="button"
                x-on:click="copy()"
                x-bind:aria-label="copied ? 'Comando copiado' : 'Copiar comando'"
                class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-white/20 px-3 text-xs font-semibold text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#93c5fd]"
            >
                <span x-text="copied ? '¡Copiado!' : 'Copiar comando'"></span>
            </button>
        </div>
    </div>
    <pre class="overflow-x-auto p-4 font-mono text-sm leading-6 selection:bg-[#3155d9] selection:text-white" style="color: #f8fafc !important"><code x-ref="command" style="color: #f8fafc !important">{{ $slot }}</code></pre>
    <p class="sr-only" role="status" aria-live="polite" x-text="copied ? 'Comando copiado al portapapeles.' : failed ? 'No se pudo copiar. Selecciona el comando y cópialo manualmente.' : ''"></p>
</div>
