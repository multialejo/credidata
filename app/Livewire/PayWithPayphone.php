<?php

namespace App\Livewire;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Models\Recarga;
use App\Services\RecargaPayphoneService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class PayWithPayphone extends Component
{
    use InteractsWithFinancieroConfig;

    public float $monto = 0;

    public ?string $errorMessage = null;

    public ?string $payWithPayphone = null;

    public ?string $payWithCard = null;

    public ?string $clientTransactionId = null;

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

    public function pay(RecargaPayphoneService $service)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate();

        $this->resetPayphoneState();

        $ctid = $service->generateClientTransactionId();

        try {
            $prepared = $service->prepare($this->monto, $ctid);
        } catch (ConnectionException $e) {
            Log::error('PayWithPayphone prepare failed', ['error' => $e->getMessage()]);
            $this->errorMessage = 'Payphone no disponible, intenta nuevamente.';

            return;
        }

        if (empty($prepared['paymentId']) || empty($prepared['payWithPayPhone']) || empty($prepared['payWithCard'])) {
            $this->errorMessage = 'No se pudo preparar la transacción con Payphone.';

            return;
        }

        $creditos = (int) round($this->monto * $this->getTasaCambioUsdCreditos());

        Recarga::create([
            'cliente_id' => auth()->user()->cliente->id,
            'metodo' => 'payphone',
            'monto_usd' => $this->monto,
            'creditos_obtenidos' => $creditos,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => $ctid,
            'fecha' => now(),
        ]);

        $this->clientTransactionId = $ctid;
        $this->payWithPayphone = $prepared['payWithPayPhone'];
        $this->payWithCard = $prepared['payWithCard'];
    }

    private function resetPayphoneState(): void
    {
        $this->errorMessage = null;
        $this->payWithPayphone = null;
        $this->payWithCard = null;
        $this->clientTransactionId = null;
    }

    public function render()
    {
        return view('livewire.pay-with-payphone');
    }
}
