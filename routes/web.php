<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\PanelSaldo;
use App\Livewire\HistorialConsultas;
use App\Livewire\GestionApiKey;
use App\Livewire\Recibos;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', PanelSaldo::class)->name('dashboard');
    Route::get('/dashboard/consultas', HistorialConsultas::class)->name('dashboard.consultas');
    Route::get('/dashboard/api-key', GestionApiKey::class)->name('dashboard.api-key');
    Route::get('/dashboard/recibos', Recibos::class)->name('dashboard.recibos');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
