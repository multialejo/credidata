<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RecargaPayphoneService
{
    public function generateClientTransactionId(): string
    {
        return 'bs-'.now()->timestamp.'-'.Str::random(6);
    }

    public function prepare(float $montoUsd, string $clientTransactionId): array
    {
        if (config('payphone.mock')) {
            return $this->prepareMock();
        }

        $cents = (int) round($montoUsd * 100);

        try {
            $response = Http::withToken(config('payphone.api_token'))
                ->timeout(config('payphone.timeout', 10))
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl().'/button/Prepare', [
                    'amount' => $cents,
                    'amountWithoutTax' => $cents,
                    'amountWithTax' => 0,
                    'tax' => 0,
                    'service' => 0,
                    'tip' => 0,
                    'clientTransactionId' => $clientTransactionId,
                    'reference' => config('payphone.reference', 'CrediData recarga'),
                    'storeId' => config('payphone.store_id'),
                    'currency' => config('payphone.currency'),
                    'responseUrl' => $this->callbackUrl('recargas.payphone.return', config('payphone.response_url')),
                    'cancellationUrl' => $this->callbackUrl('recargas.payphone.cancel', config('payphone.cancellation_url')),
                ]);

            if ($response->failed()) {
                Log::error('Payphone prepare: error', [
                    'status' => $response->status(),
                    'clientTransactionId' => $clientTransactionId,
                    'body' => $response->json(),
                ]);

                throw new ConnectionException('Payphone prepare: error inesperado');
            }

            return [
                'paymentId' => (string) ($response->json('paymentId') ?? ''),
                'payWithPayPhone' => $response->json('payWithPayPhone'),
                'payWithCard' => $response->json('payWithCard'),
            ];
        } catch (ConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Payphone prepare: excepción', [
                'clientTransactionId' => $clientTransactionId,
                'error' => $e->getMessage(),
            ]);
            throw new ConnectionException('Payphone prepare: '.$e->getMessage());
        }
    }

    public function confirm(int $id, string $clientTransactionId): array
    {
        if (config('payphone.mock')) {
            return $this->confirmMock();
        }

        try {
            $response = Http::withToken(config('payphone.api_token'))
                ->timeout(config('payphone.timeout', 10))
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl().'/button/V2/Confirm', [
                    'id' => $id,
                    'clientTxId' => $clientTransactionId,
                ]);

            if ($response->failed()) {
                Log::error('Payphone confirm: error', [
                    'status' => $response->status(),
                    'id' => $id,
                    'clientTransactionId' => $clientTransactionId,
                    'body' => $response->json(),
                ]);

                throw new ConnectionException('Payphone confirm: error inesperado');
            }

            return $this->normalizeConfirmResponse($response->json());
        } catch (ConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Payphone confirm: excepción', [
                'id' => $id,
                'clientTransactionId' => $clientTransactionId,
                'error' => $e->getMessage(),
            ]);
            throw new ConnectionException('Payphone confirm: '.$e->getMessage());
        }
    }

    private function baseUrl(): string
    {
        return rtrim(config('payphone.api_url'), '/');
    }

    private function callbackUrl(string $routeName, ?string $override): string
    {
        if (! empty($override)) {
            return $override;
        }

        return request()->getSchemeAndHttpHost().route($routeName, [], false);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{transactionStatus: string, transactionId: ?string, authorizationCode: ?string, message: ?string}
     */
    private function normalizeConfirmResponse(array $payload): array
    {
        $statusCode = (int) ($payload['statusCode'] ?? 0);
        $rawStatus = strtoupper((string) ($payload['transactionStatus'] ?? ''));

        $transactionStatus = match (true) {
            $statusCode === 3, str_contains($rawStatus, 'APPROV') => 'Approved',
            $statusCode === 2, str_contains($rawStatus, 'CANCEL') => 'Canceled',
            default => 'Unknown',
        };

        return [
            'transactionStatus' => $transactionStatus,
            'transactionId' => isset($payload['transactionId']) ? (string) $payload['transactionId'] : null,
            'authorizationCode' => $payload['authorizationCode'] ?? null,
            'message' => $payload['message'] ?? null,
        ];
    }

    private function prepareMock(): array
    {
        $paymentId = 'MOCK-PAY-'.strtoupper(uniqid());

        return [
            'paymentId' => $paymentId,
            'payWithPayPhone' => "https://pay.payphonetodoesposible.com/PayPhone/Index?paymentId={$paymentId}",
            'payWithCard' => "https://pay.payphonetodoesposible.com/Anonymous/Index?paymentId={$paymentId}",
        ];
    }

    private function confirmMock(): array
    {
        return [
            'transactionStatus' => 'Approved',
            'transactionId' => 'MOCK-PP-'.strtoupper(uniqid()),
            'authorizationCode' => 'MOCK-AUTH-'.strtoupper(uniqid()),
            'message' => null,
        ];
    }
}
