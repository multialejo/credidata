<?php

namespace App\Livewire;

use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use Livewire\Attributes\On;
use Livewire\Component;

class PayWithTransferencia extends Component
{
    use InteractsWithFinancieroConfig;

    public float $monto = 0;

    public ?string $referencia = null;

    public function mount(float|int|null $monto = 0): void
    {
        $this->monto = (float) ($monto ?? 0);
        $this->referencia = 'CD-'.strtoupper(bin2hex(random_bytes(3)));
    }

    #[On('monto-updated')]
    public function syncMonto(float $monto): void
    {
        $this->monto = $monto;
    }

    public function getMontoValidoProperty(): bool
    {
        return $this->isMontoValido($this->monto);
    }

    public function getCreditosEstimadosProperty(): int
    {
        return $this->monto > 0
            ? (int) floor($this->monto * $this->getTasaCambioUsdCreditos())
            : 0;
    }

    public function getDatosTransferenciaProperty(): ?array
    {
        return $this->getDatosTransferencia();
    }

    public function getWhatsappLinkProperty(): ?string
    {
        $datos = $this->datosTransferencia;

        if (! $datos || empty($datos['whatsapp']) || ! $this->montoValido) {
            return null;
        }

        $mensaje = 'Hola, quiero confirmar una recarga de $'.number_format($this->monto, 2)
            ." ({$this->creditosEstimados} créditos)."
            ."\nReferencia: {$this->referencia}"
            ."\n\nAdjunto el comprobante de pago.";

        $encoded = rawurlencode($mensaje);

        return "https://wa.me/{$datos['whatsapp']}?text={$encoded}";
    }

    public function render()
    {
        return view('livewire.pay-with-transferencia');
    }
}
