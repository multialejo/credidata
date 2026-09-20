<?php

namespace App\Livewire;

use App\Enums\EstadoIntencionPayphone;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Models\IntencionPayphone;
use App\Services\RecargaPayphoneService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class PayWithPayphone extends Component
{
    use InteractsWithFinancieroConfig;

    public float $monto = 0;

    public bool $soloTarjeta = false;

    public ?string $errorMessage = null;

    public function mount(float|int|null $monto = 0, bool $soloTarjeta = false): void
    {
        $this->monto = (float) ($monto ?? 0);
        $this->soloTarjeta = $soloTarjeta;
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
        $this->errorMessage = null;

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

        $creditos = (int) floor($this->monto * $this->getTasaCambioUsdCreditos());

        IntencionPayphone::create([
            'cliente_id' => auth()->user()->cliente->id,
            'ctid' => $ctid,
            'payment_id' => $prepared['paymentId'],
            'monto_usd' => $this->monto,
            'creditos_estimados' => $creditos,
            'moneda' => config('payphone.currency'),
            'estado' => EstadoIntencionPayphone::Pendiente,
            'expira_en' => now()->addMinutes((int) config('payphone.intencion_ttl_minutes', 15)),
            'fecha' => now(),
        ]);

        return redirect()->away($this->soloTarjeta ? $prepared['payWithCard'] : $prepared['payWithPayPhone']);
    }

    public function render()
    {
        return view('livewire.pay-with-payphone');
    }
}
