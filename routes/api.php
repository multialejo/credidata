<?php

use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\ConsultaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/consulta/cedula', [ConsultaController::class, 'consultaCedula'])
    ->middleware('api.key:consulta:cedula');

Route::post('/v1/api-key/revocar', [ApiKeyController::class, 'revocar']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
