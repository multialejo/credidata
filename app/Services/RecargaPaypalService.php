<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecargaPaypalService
{
    public function createOrder(float $montoUsd): array
    {
        if (config('paypal.mock')) {
            return $this->createOrderMock($montoUsd);
        }

        try {
            $response = Http::withToken($this->getAccessToken())
                ->timeout(config('paypal.timeout'))
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl().'/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($montoUsd, 2, '.', ''),
                        ],
                    ]],
                    'payment_source' => [
                        'paypal' => [
                            'experience_context' => [
                                // Phase 1 wiring: drives PayPal sandbox to redirect back to us.
                                // Phase 3 may extract to a private method or value object.
                                'user_action' => 'PAY_NOW',
                                'shipping_preference' => 'NO_SHIPPING',
                                'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                                'return_url' => config('paypal.return_url'),
                                'cancel_url' => config('paypal.cancel_url'),
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('PayPal createOrder: error', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                throw new ConnectionException('PayPal createOrder: error inesperado');
            }

            return $response->json();
        } catch (ConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('PayPal createOrder: excepción', [
                'error' => $e->getMessage(),
            ]);
            throw new ConnectionException('PayPal createOrder: '.$e->getMessage());
        }
    }

    public function captureOrder(string $orderId): array
    {
        if (config('paypal.mock')) {
            return $this->captureOrderMock($orderId);
        }

        try {
            $response = Http::withToken($this->getAccessToken())
                ->timeout(config('paypal.timeout'))
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl()."/v2/checkout/orders/{$orderId}/capture", (object) []);

            if ($response->status() === 404) {
                return ['status' => 'NOT_FOUND', 'order_id' => $orderId];
            }

            if ($response->failed()) {
                Log::error('PayPal captureOrder: error', [
                    'status' => $response->status(),
                    'order_id' => $orderId,
                    'body' => $response->json(),
                ]);
                throw new ConnectionException('PayPal captureOrder: error inesperado');
            }

            return $response->json();
        } catch (ConnectionException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('PayPal captureOrder: excepción', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw new ConnectionException('PayPal captureOrder: '.$e->getMessage());
        }
    }

    public function obtenerApprovalUrl(array $order): ?string
    {
        foreach ($order['links'] ?? [] as $link) {
            if (in_array($link['rel'] ?? '', ['approve', 'payer-action'], true)) {
                return $link['href'] ?? null;
            }
        }

        return null;
    }

    private function getAccessToken(): string
    {
        return Cache::remember(
            'paypal:oauth:access_token',
            now()->addMinutes(50),
            function () {
                $response = Http::withBasicAuth(
                    config('paypal.client_id'),
                    config('paypal.client_secret'),
                )
                    ->asForm()
                    ->timeout(config('paypal.timeout'))
                    ->post($this->baseUrl().'/v1/oauth2/token', [
                        'grant_type' => 'client_credentials',
                    ]);

                if ($response->failed()) {
                    Log::error('PayPal OAuth: error', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    throw new ConnectionException('PayPal OAuth: error al obtener token');
                }

                return $response->json('access_token');
            }
        );
    }

    private function baseUrl(): string
    {
        return config('paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function createOrderMock(float $montoUsd): array
    {
        $id = 'MOCK-ORDER-'.strtoupper(uniqid());

        return [
            'id' => $id,
            'status' => 'CREATED',
            'links' => [
                [
                    'href' => "https://www.sandbox.paypal.com/checkoutnow?token={$id}",
                    'rel' => 'approve',
                    'method' => 'GET',
                ],
            ],
        ];
    }

    private function captureOrderMock(string $orderId): array
    {
        return [
            'id' => $orderId,
            'status' => 'COMPLETED',
            'purchase_units' => [
                [
                    'reference_id' => 'default',
                    'payments' => [
                        'captures' => [
                            [
                                'id' => 'MOCK-CAPTURE-'.strtoupper(uniqid()),
                                'status' => 'COMPLETED',
                                'amount' => [
                                    'currency_code' => 'USD',
                                    'value' => '0.00',
                                ],
                                'final_capture' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
