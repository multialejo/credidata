<?php

namespace App\Http\Controllers;

use App\Enums\EstadoRecarga;
use App\Models\Recarga;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReciboPdfController extends Controller
{
    public function __invoke(Recarga $recarga): Response
    {
        abort_unless(
            $recarga->cliente_id === auth()->user()->cliente?->id
                && $recarga->estado === EstadoRecarga::Completada,
            404,
        );

        return Pdf::loadView('recibos.pdf', [
            'recarga' => $recarga,
            'cliente' => $recarga->cliente->usuario,
        ])
            ->setPaper('a4')
            ->download('recibo-recarga-'.$recarga->id.'.pdf');
    }
}
