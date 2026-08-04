<?php

namespace Tests\Unit;

use App\Services\RecargaPayphoneService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecargaPayphoneServiceTest extends TestCase
{
    private RecargaPayphoneService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payphone.api_token', 'test-token-abc');
        config()->set('payphone.api_url', 'https://pay.payphonetodoesposible.com/api');
        config()->set('payphone.store_id', 'store-uid-123');
        config()->set('payphone.mock', false);
        config()->set('payphone.timeout', 5);
        config()->set('payphone.reference', 'CrediData recarga');
        config()->set('payphone.currency', 'USD');
        config()->set('payphone.response_url', 'https://app.credidata.test/dashboard/recargas/payphone/return');
        config()->set('payphone.cancellation_url', 'https://app.credidata.test/dashboard/recargas/payphone/cancel');

        $this->service = new RecargaPayphoneService;
    }

    public function test_generate_client_transaction_id_has_bs_prefix_and_unique_suffix(): void
    {
        $id1 = $this->service->generateClientTransactionId();
        $id2 = $this->service->generateClientTransactionId();

        $this->assertStringStartsWith('bs-', $id1);
        $this->assertMatchesRegularExpression('/^bs-\d{10,}-[A-Za-z0-9]{6}$/', $id1);
        $this->assertNotSame($id1, $id2);
    }

    public function test_prepare_con_mock_true_retorna_payment_id_y_urls_sin_http(): void
    {
        config()->set('payphone.mock', true);
        Http::fake();

        $result = $this->service->prepare(10.00, 'bs-mock-001');

        $this->assertNotEmpty($result['paymentId']);
        $this->assertStringStartsWith('MOCK-PAY-', $result['paymentId']);
        $this->assertStringContainsString($result['paymentId'], $result['payWithPayPhone']);
        $this->assertStringContainsString($result['paymentId'], $result['payWithCard']);
        Http::assertNothingSent();
    }

    public function test_prepare_con_mock_false_llama_button_prepare_con_bearer_y_body_correcto(): void
    {
        Http::fake([
            'pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId' => 'GSizecyUkIAkxTj3SQ',
                'payWithPayPhone' => 'https://pay.payphonetodoesposible.com/PayPhone/Index?paymentId=GSizecyUkIAkxTj3SQ',
                'payWithCard' => 'https://pay.payphonetodoesposible.com/Anonymous/Index?paymentId=GSizecyUkIAkxTj3SQ',
            ], 200),
        ]);

        $result = $this->service->prepare(10.00, 'bs-real-001');

        $this->assertSame('GSizecyUkIAkxTj3SQ', $result['paymentId']);
        $this->assertStringContainsString('GSizecyUkIAkxTj3SQ', $result['payWithPayPhone']);
        $this->assertStringContainsString('GSizecyUkIAkxTj3SQ', $result['payWithCard']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://pay.payphonetodoesposible.com/api/button/Prepare'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-token-abc')
                && $request['amount'] === 1000
                && $request['amountWithoutTax'] === 1000
                && $request['clientTransactionId'] === 'bs-real-001'
                && $request['storeId'] === 'store-uid-123'
                && $request['currency'] === 'USD'
                && $request['responseUrl'] === 'https://app.credidata.test/dashboard/recargas/payphone/return'
                && $request['cancellationUrl'] === 'https://app.credidata.test/dashboard/recargas/payphone/cancel';
        });
    }

    public function test_prepare_convierte_monto_a_centavos(): void
    {
        Http::fake([
            '*button/Prepare*' => Http::response([
                'paymentId' => 'PID',
                'payWithPayPhone' => 'x',
                'payWithCard' => 'y',
            ], 200),
        ]);

        $this->service->prepare(12.68, 'bs-1');

        Http::assertSent(fn ($r) => $r['amount'] === 1268 && $r['amountWithoutTax'] === 1268);
    }

    public function test_prepare_sin_override_construye_url_desde_el_host_del_request_actual(): void
    {
        config()->set('payphone.response_url', null);
        config()->set('payphone.cancellation_url', null);

        $this->app->instance('request', Request::create('http://192.168.1.21/some/path'));

        Http::fake([
            '*button/Prepare*' => Http::response(['paymentId' => 'P'], 200),
        ]);

        $this->service->prepare(10.00, 'bs-lan-001');

        Http::assertSent(function ($request) {
            return $request['responseUrl'] === 'http://192.168.1.21/dashboard/recargas/payphone/return'
                && $request['cancellationUrl'] === 'http://192.168.1.21/dashboard/recargas/payphone/cancel';
        });
    }

    public function test_prepare_con_override_explicito_ignora_el_host_del_request(): void
    {
        $this->app->instance('request', Request::create('http://192.168.1.21/some/path'));

        Http::fake([
            '*button/Prepare*' => Http::response(['paymentId' => 'P'], 200),
        ]);

        $this->service->prepare(10.00, 'bs-override-001');

        Http::assertSent(function ($request) {
            return $request['responseUrl'] === 'https://app.credidata.test/dashboard/recargas/payphone/return'
                && $request['cancellationUrl'] === 'https://app.credidata.test/dashboard/recargas/payphone/cancel';
        });
    }

    public function test_prepare_con_respuesta_http_error_lanza_connection_exception(): void
    {
        Http::fake([
            '*button/Prepare*' => Http::response('error', 500),
        ]);

        $this->expectException(ConnectionException::class);

        $this->service->prepare(10.00, 'bs-error-001');
    }

    public function test_prepare_con_timeout_lanza_connection_exception(): void
    {
        Http::fake([
            '*button/Prepare*' => function () {
                throw new ConnectionException('timeout');
            },
        ]);

        $this->expectException(ConnectionException::class);

        $this->service->prepare(10.00, 'bs-timeout-001');
    }

    public function test_confirm_con_mock_true_retorna_approved_sin_http(): void
    {
        config()->set('payphone.mock', true);
        Http::fake();

        $result = $this->service->confirm(12345, 'bs-mock-001');

        $this->assertSame('Approved', $result['transactionStatus']);
        $this->assertNotNull($result['transactionId']);
        $this->assertNotNull($result['authorizationCode']);
        Http::assertNothingSent();
    }

    public function test_confirm_con_mock_false_envia_client_tx_id_y_no_client_transaction_id(): void
    {
        Http::fake([
            'pay.payphonetodoesposible.com/api/button/V2/Confirm' => Http::response([
                'statusCode' => 3,
                'transactionStatus' => 'Approved',
                'transactionId' => 23178284,
                'authorizationCode' => 'W23178284',
                'message' => null,
            ], 200),
        ]);

        $result = $this->service->confirm(12345, 'bs-real-001');

        $this->assertSame('Approved', $result['transactionStatus']);
        $this->assertSame('23178284', $result['transactionId']);
        $this->assertSame('W23178284', $result['authorizationCode']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://pay.payphonetodoesposible.com/api/button/V2/Confirm'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-token-abc')
                && $request['id'] === 12345
                && $request['clientTxId'] === 'bs-real-001'
                && ! isset($request['clientTransactionId']);
        });
    }

    public function test_confirm_normaliza_status_code_2_a_canceled(): void
    {
        Http::fake([
            '*button/V2/Confirm*' => Http::response([
                'statusCode' => 2,
                'transactionStatus' => 'CANCELED',
            ], 200),
        ]);

        $result = $this->service->confirm(99, 'bs-canceled-001');

        $this->assertSame('Canceled', $result['transactionStatus']);
    }

    public function test_confirm_normaliza_status_code_3_a_approved(): void
    {
        Http::fake([
            '*button/V2/Confirm*' => Http::response([
                'statusCode' => 3,
                'transactionStatus' => 'APPROVED',
            ], 200),
        ]);

        $result = $this->service->confirm(99, 'bs-approved-001');

        $this->assertSame('Approved', $result['transactionStatus']);
    }

    public function test_confirm_con_status_code_no_reconocido_retorna_unknown(): void
    {
        Http::fake([
            '*button/V2/Confirm*' => Http::response([
                'statusCode' => 99,
                'transactionStatus' => 'WHATEVER',
            ], 200),
        ]);

        $result = $this->service->confirm(99, 'bs-unknown-001');

        $this->assertSame('Unknown', $result['transactionStatus']);
    }

    public function test_confirm_con_respuesta_http_error_lanza_connection_exception(): void
    {
        Http::fake([
            '*button/V2/Confirm*' => Http::response('error', 500),
        ]);

        $this->expectException(ConnectionException::class);

        $this->service->confirm(99, 'bs-error-001');
    }

    public function test_confirm_con_timeout_lanza_connection_exception(): void
    {
        Http::fake([
            '*button/V2/Confirm*' => function () {
                throw new ConnectionException('timeout');
            },
        ]);

        $this->expectException(ConnectionException::class);

        $this->service->confirm(99, 'bs-timeout-001');
    }
}
