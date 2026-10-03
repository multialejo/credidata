<?php

namespace App\Livewire;

use App\Enums\EstadoIntencionPaypal;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Models\IntencionPaypal;
use App\Services\RecargaPaypalService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class PayWithPaypal extends Component
{
    use InteractsWithFinancieroConfig;

    public float $monto = 0;

    public ?string $errorMessage = null;

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
        return $this->isMontoValido($this->monto) && $this->montoEsExacto($this->monto);
    }

    protected function rules()
    {
        return [
            'monto' => 'required|numeric|decimal:0,2|min:'.$this->getRecargaMinimaUsd(),
        ];
    }

    public function pay(RecargaPaypalService $service)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate();

        $creditos = $this->creditosParaMonto($this->monto);
        if ($creditos === null) {
            $this->addError('monto', 'El monto debe corresponder a una cantidad entera de créditos.');

            return;
        }

        try {
            $order = $service->createOrder($this->monto);
        } catch (ConnectionException $e) {
            Log::error('PayWithPaypal createOrder failed', ['error' => $e->getMessage()]);
            $this->errorMessage = 'PayPal no disponible, intenta nuevamente.';

            return;
        }

        $orderId = $order['id'] ?? null;
        if (! $orderId) {
            $this->errorMessage = 'Error al crear la orden en PayPal.';

            return;
        }

        $approvalUrl = $service->obtenerApprovalUrl($order);
        if (! $approvalUrl) {
            $this->errorMessage = 'No se pudo obtener la URL de aprobación de PayPal.';

            return;
        }

        IntencionPaypal::create([
            'cliente_id' => auth()->user()->cliente->id,
            'order_id' => $orderId,
            'monto_usd' => $this->monto,
            'creditos_estimados' => $creditos,
            'moneda' => 'USD',
            'estado' => EstadoIntencionPaypal::Pendiente,
            'expira_en' => now()->addMinutes((int) config('paypal.intencion_ttl_minutes', 15)),
            'fecha' => now(),
        ]);

        return redirect()->away($approvalUrl);
    }

    public function render()
    {
        return view('livewire.pay-with-paypal');
    }
}
