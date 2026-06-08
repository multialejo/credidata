<?php

use App\Http\Controllers\Api\ConsultaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/consulta/cedula', [ConsultaController::class, 'consultaCedula']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
