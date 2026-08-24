<?php

namespace Tests\Unit;

use App\Services\RecargaPaypalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecargaPaypalServiceTest extends TestCase
{
    private RecargaPaypalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config()->set('paypal.client_id', 'test-client-id');
        config()->set('paypal.client_secret', 'test-client-secret');
        config()->set('paypal.mode', 'sandbox');
        config()->set('paypal.mock', false);
        config()->set('paypal.timeout', 5);
        config()->set('paypal.return_url', 'https://app.credidata.test/dashboard/recargas/paypal/return');
        config()->set('paypal.cancel_url', 'https://app.credidata.test/dashboard/recargas/paypal/cancel');

        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'fake-token',
            ], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'ORDER-1',
                'status' => 'CREATED',
                'links' => [],
            ], 200),
        ]);

        $this->service = new RecargaPaypalService;
    }

    public function test_create_order_envia_return_y_cancel_url_del_override(): void
    {
        $this->service->createOrder(10.00);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/v2/checkout/orders')) {
                return true;
            }

            return $request['payment_source']['paypal']['experience_context']['return_url'] === 'https://app.credidata.test/dashboard/recargas/paypal/return'
                && $request['payment_source']['paypal']['experience_context']['cancel_url'] === 'https://app.credidata.test/dashboard/recargas/paypal/cancel';
        });
    }

    public function test_create_order_sin_override_construye_url_desde_el_host_del_request_actual(): void
    {
        config()->set('paypal.return_url', null);
        config()->set('paypal.cancel_url', null);

        $this->app->instance('request', Request::create('http://192.168.1.21/some/path'));

        $this->service->createOrder(10.00);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/v2/checkout/orders')) {
                return true;
            }

            return $request['payment_source']['paypal']['experience_context']['return_url'] === 'http://192.168.1.21/dashboard/recargas/paypal/return'
                && $request['payment_source']['paypal']['experience_context']['cancel_url'] === 'http://192.168.1.21/dashboard/recargas/paypal/cancel';
        });
    }

    public function test_create_order_con_override_explicito_ignora_el_host_del_request(): void
    {
        $this->app->instance('request', Request::create('http://192.168.1.21/some/path'));

        $this->service->createOrder(10.00);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/v2/checkout/orders')) {
                return true;
            }

            return $request['payment_source']['paypal']['experience_context']['return_url'] === 'https://app.credidata.test/dashboard/recargas/paypal/return'
                && $request['payment_source']['paypal']['experience_context']['cancel_url'] === 'https://app.credidata.test/dashboard/recargas/paypal/cancel';
        });
    }
}
