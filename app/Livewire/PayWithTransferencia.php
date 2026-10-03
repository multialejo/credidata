<?php

namespace App\Livewire;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Jobs\NotifyStaffTransferSubmitted;
use App\Models\Recarga;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PayWithTransferencia extends Component
{
    use InteractsWithFinancieroConfig;
    use WithFileUploads;

    public float $monto = 0;

    public string $referenciaBancaria = '';

    public $comprobante;

    public bool $solicitudEnviada = false;

    public function mount(float|int|null $monto = 0): void
    {
        $this->monto = (float) ($monto ?? 0);
    }

    #[On('monto-updated')]
    public function syncMonto(float $monto): void
    {
        $this->monto = $monto;
    }

    public function getMontoValidoProperty(): bool
    {
        if (! isset($this->monto)) {
            return false;
        }

        return $this->isMontoValido($this->monto) && $this->montoEsExacto($this->monto);
    }

    public function getCreditosEstimadosProperty(): int
    {
        if (! isset($this->monto) || $this->monto <= 0) {
            return 0;
        }

        return $this->creditosParaMonto($this->monto) ?? 0;
    }

    public function getDatosTransferenciaProperty(): ?array
    {
        return $this->getDatosTransferencia();
    }

    public function enviarComprobante(): void
    {
        abort_unless($this->isMetodoPagoHabilitado('transferencia') && $this->getDatosTransferencia(), 403);

        $this->referenciaBancaria = trim($this->referenciaBancaria);

        $this->validate([
            'monto' => ['required', 'numeric', 'decimal:0,2', 'min:'.$this->getRecargaMinimaUsd(), 'max:99999999.99'],
            'referenciaBancaria' => ['required', 'string', 'max:100', Rule::unique('recargas', 'referencia_externa')],
            'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $creditos = $this->creditosParaMonto($this->monto);
        if ($creditos === null) {
            $this->addError('monto', 'El monto debe corresponder a una cantidad entera de créditos.');

            return;
        }

        $cliente = auth()->user()?->cliente;
        abort_unless($cliente, 403);

        $path = $this->comprobante->store('recargas/comprobantes', 'local');

        try {
            $recarga = DB::transaction(fn () => Recarga::create([
                'cliente_id' => $cliente->id,
                'metodo' => 'transferencia',
                'monto_usd' => $this->monto,
                'creditos_obtenidos' => $creditos,
                'estado' => EstadoRecarga::Pendiente,
                'referencia_externa' => $this->referenciaBancaria,
                'comprobante_url' => $path,
                'fecha' => now(),
            ]));
        } catch (UniqueConstraintViolationException) {
            Storage::disk('local')->delete($path);
            $this->addError('referenciaBancaria', 'Esta referencia bancaria ya fue registrada. Verifica el número ingresado.');

            return;
        }

        NotifyStaffTransferSubmitted::dispatch($recarga);
        $this->reset(['referenciaBancaria', 'comprobante']);
        $this->solicitudEnviada = true;
    }

    public function render()
    {
        return view('livewire.pay-with-transferencia');
    }
}
