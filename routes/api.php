<?php

use App\Http\Controllers\Api\AdminRecargaController;
use App\Http\Controllers\Api\AdminRecargaEvidenceController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\ConsultaController;
use App\Http\Controllers\Api\RecargaPaypalController;
use App\Http\Controllers\Api\RecargaPayphoneController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/consulta/cedula', [ConsultaController::class, 'consultaCedula'])
    ->middleware('api.key:consulta:cedula');
Route::post('/v1/consulta/ruc', [ConsultaController::class, 'consultaRuc'])
    ->middleware('api.key:consulta:ruc');

Route::middleware('api.key')->group(function () {
    Route::post('/v1/api-key/revocar', [ApiKeyController::class, 'revocar']);
    Route::post('/v1/api-key/rotar', [ApiKeyController::class, 'rotar']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('v1/recargas/paypal')->group(function () {
        Route::post('/orden', [RecargaPaypalController::class, 'crearOrden']);
        Route::post('/{order_id}/capturar', [RecargaPaypalController::class, 'capturar']);
    });

    Route::prefix('v1/recargas/payphone')->group(function () {
        Route::post('/transaccion', [RecargaPayphoneController::class, 'crearTransaccion']);
        Route::post('/{id}/confirmar', [RecargaPayphoneController::class, 'confirmar']);
    });

    Route::middleware(['staff'])->prefix('v1/admin')->group(function () {
        Route::get('recargas/pendientes', [AdminRecargaController::class, 'pendientes']);
        Route::get('recargas/{recarga}/comprobante', AdminRecargaEvidenceController::class);
        Route::post('recargas/{recarga}/rechazar', [AdminRecargaController::class, 'rechazar']);
        Route::post('recargas/acreditar', [AdminRecargaController::class, 'acreditar'])
            ->middleware('staff.role:admin');
    });
});
