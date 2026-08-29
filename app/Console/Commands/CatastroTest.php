<?php

namespace App\Console\Commands;

use App\Rules\EcuadorianIdentificador;
use App\Services\CatastroService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CatastroTest extends Command
{
    protected $signature = 'catastro:test {ruc : RUC ecuatoriano a consultar}';

    protected $description = 'Diagnostica la consulta del catastro SRI en Firestore';

    public function handle(CatastroService $catastroService): int
    {
        $ruc = $this->argument('ruc');
        $validation = Validator::make(['ruc' => $ruc], ['ruc' => ['required', 'string', 'size:13', 'regex:/^[0-9]+$/', new EcuadorianIdentificador]]);
        if ($validation->fails()) {
            $this->error('RUC inválido: '.$validation->errors()->first('ruc'));

            return 1;
        }

        $this->line('Colección: catastro_sri');
        $this->line("Prefijo consultado: {$ruc}_");
        try {
            $establecimientos = $catastroService->buscarEstablecimientos($ruc);
        } catch (Throwable $e) {
            $this->error('Error Firestore: '.$e->getMessage());

            return 2;
        }

        $count = count($establecimientos);
        $this->line("Establecimientos: {$count}");
        if ($count === 0) {
            $this->warn('RUC válido sin establecimientos');

            return 0;
        }

        $this->line(json_encode($establecimientos[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $required = ['numero', 'razonSocial', 'estadoContribuyente', 'estadoEstablecimiento', 'actividadEconomica', 'ubicacion', 'fechas', 'contacto'];
        $missing = array_diff($required, array_keys($establecimientos[0]));
        if ($missing !== []) {
            $this->error('Campos obligatorios ausentes: '.implode(', ', $missing));

            return 3;
        }
        $enriched = ['regimenRimpe', 'agenteRetencion', 'contribuyenteEspecial', 'artesanoCalificado'];
        $missingEnriched = array_diff($enriched, array_keys($establecimientos[0]));
        if ($missingEnriched !== []) {
            $this->error('Campos enriquecidos ausentes: '.implode(', ', $missingEnriched));

            return 3;
        }
        $this->info('Campos enriquecidos: OK');

        return 0;
    }
}
