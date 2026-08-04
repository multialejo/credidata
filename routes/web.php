<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecargaPaypalReturnController;
use App\Http\Controllers\RecargaPayphoneReturnController;
use App\Livewire\GestionApiKey;
use App\Livewire\HistorialConsultas;
use App\Livewire\PanelSaldo;
use App\Livewire\Recargas;
use App\Livewire\Recibos;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', PanelSaldo::class)->name('dashboard');
    Route::get('/dashboard/recargas', Recargas::class)->name('dashboard.recargas');
    Route::get('/dashboard/consultas', HistorialConsultas::class)->name('dashboard.consultas');
    Route::get('/dashboard/api-key', GestionApiKey::class)->name('dashboard.api-key');
    Route::get('/dashboard/recibos', Recibos::class)->name('dashboard.recibos');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public PayPal return/cancel URLs (no auth middleware: PayPal redirects here from the buyer's browser).
Route::get('/dashboard/recargas/paypal/return', [RecargaPaypalReturnController::class, 'showReturn'])->name('recargas.paypal.return');
Route::get('/dashboard/recargas/paypal/cancel', [RecargaPaypalReturnController::class, 'showCancel'])->name('recargas.paypal.cancel');

// Public Payphone return/cancel URLs (no auth middleware: Payphone redirects here from the buyer's browser).
Route::get('/dashboard/recargas/payphone/return', [RecargaPayphoneReturnController::class, 'showReturn'])->name('recargas.payphone.return');
Route::get('/dashboard/recargas/payphone/cancel', [RecargaPayphoneReturnController::class, 'showCancel'])->name('recargas.payphone.cancel');

require __DIR__.'/auth.php';
