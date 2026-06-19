<?php

namespace App\Console\Commands;

use App\Services\DinardapService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class DinardapTest extends Command
{
    protected $signature = 'dinardap:test {cedula : Número de cédula a consultar}';
    protected $description = 'Prueba la conectividad con la API de Dinardap';

    public function handle(DinardapService $dinardapService): int
    {
        $cedula = $this->argument('cedula');

        if (config('dinardap.mock')) {
            $this->newLine();
            $this->components->warn('Modo MOCK activo — los datos son ficticios');
        }

        $this->newLine();
        $this->components->info("Consultando cédula: {$cedula}");

        // 1. Try cache first
        $this->newLine();
        $this->components->twoColumnDetail('<fg=yellow>Paso 1</>', 'Verificando cache en Firestore...');

        try {
            $cache = $dinardapService->obtenerCache($cedula);

            if ($cache !== null) {
                $this->components->twoColumnDetail('Cache', '<fg=green>HIT</>');
                $this->newLine();
                $this->table(
                    ['Campo', 'Valor'],
                    collect($cache)->map(fn($v, $k) => [$k, is_scalar($v) ? (string) $v : json_encode($v)])->toArray()
                );

                return 0;
            }

            $this->components->twoColumnDetail('Cache', '<fg=yellow>MISS</>');
        } catch (Throwable $e) {
            $this->components->twoColumnDetail('Cache', '<fg=red>ERROR</>');
            $this->components->error("Firestore: {$e->getMessage()}");
        }

        // 2. Call Dinardap
        $this->newLine();
        $this->components->twoColumnDetail('<fg=yellow>Paso 2</>', 'Consultando API Dinardap...');

        try {
            $resultado = $dinardapService->consultar($cedula);
        } catch (ConnectionException $e) {
            $this->newLine();
            $this->components->twoColumnDetail('Dinardap', '<fg=red>ERROR DE CONEXIÓN</>');
            $this->components->error($e->getMessage());

            return 2;
        }

        if ($resultado['status'] === 'not_found') {
            $this->newLine();
            $this->components->twoColumnDetail('Dinardap', '<fg=yellow>NO ENCONTRADO</>');
            $this->components->warn("La cédula {$cedula} no tiene registros en Dinardap.");

            return 1;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Dinardap', '<fg=green>OK</>');

        // 3. Save to cache
        $this->newLine();
        $this->components->twoColumnDetail('<fg=yellow>Paso 3</>', 'Guardando en cache Firestore...');

        $saved = $dinardapService->guardarCache($cedula, $resultado['data']);

        if ($saved) {
            $this->components->twoColumnDetail('Cache', '<fg=green>GUARDADO</>');
        } else {
            $this->components->twoColumnDetail('Cache', '<fg=yellow>NO GUARDADO (error no bloqueante)</>');
        }

        // 4. Show result
        $this->newLine();
        $this->components->info('Datos obtenidos:');
        $this->newLine();

        $this->table(
            ['Campo', 'Valor'],
            collect($resultado['data'])->map(fn($v, $k) => [$k, is_scalar($v) ? (string) $v : json_encode($v)])->toArray()
        );

        $this->newLine();
        $this->components->info('Prueba completada exitosamente.');

        return 0;
    }
}
