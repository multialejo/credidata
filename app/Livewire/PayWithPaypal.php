<?php

namespace App\Livewire;

use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Models\Recarga;
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

    public function mount(float $monto = 0): void
    {
        $this->monto = $monto;
    }

    #[On('monto-updated')]
    public function syncMonto(float $monto): void
    {
        $this->monto = $monto;
    }

    protected function rules()
    {
        return [
            'monto' => 'required|numeric|min:'.$this->getRecargaMinimaUsd(),
        ];
    }

    public function pay(RecargaPaypalService $service)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate();

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

        $creditos = (int) floor($this->monto * $this->getTasaCambioUsdCreditos());

        Recarga::create([
            'cliente_id' => auth()->user()->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => $this->monto,
            'creditos_obtenidos' => $creditos,
            'estado' => 'pendiente',
            'referencia_externa' => $orderId,
            'fecha' => now(),
        ]);

        return redirect()->away($approvalUrl);
    }

    public function render()
    {
        return view('livewire.pay-with-paypal');
    }
}
