<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recarga;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminRecargaEvidenceController extends Controller
{
    public function __invoke(Recarga $recarga): BinaryFileResponse
    {
        abort_unless(
            $recarga->metodo === 'transferencia' && $recarga->comprobante_url,
            404,
        );

        abort_unless(Storage::disk('local')->exists($recarga->comprobante_url), 404);

        return response()->download(
            Storage::disk('local')->path($recarga->comprobante_url),
            basename($recarga->comprobante_url),
        );
    }
}
