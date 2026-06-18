<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class FirebaseUsers extends Command
{
    protected $signature = 'firebase:users
        {--limit=10 : Número máximo de documentos a recuperar}
        {--all : Recuperar todos los documentos}';

    protected $description = 'Recupera documentos de la colección "users" en Firestore';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $all = $this->option('all');

        try {
            $firestore = Firebase::firestore()->database();
            $collection = $firestore->collection('vacas');
            $snapshot = $collection->documents();
        } catch (Throwable $e) {
            $this->newLine();
            $this->components->error($e->getMessage());
            return 1;
        }

        $documents = $snapshot->isEmpty() ? [] : iterator_to_array($snapshot);

        if (empty($documents)) {
            $this->newLine();
            $this->components->warn('No se encontraron documentos en la colección "users".');
            return 0;
        }

        $total = count($documents);
        $display = $all ? $documents : array_slice($documents, 0, $limit);
        $hasMore = !$all && $total > $limit;

        $rows = [];

        foreach ($display as $doc) {
            if (!$doc->exists()) {
                continue;
            }

            $data = $doc->data() ?? [];
            $formatted = collect($data)
                ->map(fn ($v, $k) => is_scalar($v) ? "{$k}: {$v}" : "{$k}: " . json_encode($v))
                ->implode(', ');

            $rows[] = [$doc->id(), $formatted];
        }

        $this->newLine();

        if (empty($rows)) {
            $this->components->warn('No se encontraron documentos con datos en la colección "users".');
            return 0;
        }

        $this->table(['Document ID', 'Data'], $rows);

        $count = count($rows);
        $showing = $hasMore
            ? "Mostrando {$count} de {$total} documentos"
            : "{$count} documento(s) recuperado(s) de la colección \"users\".";

        $this->newLine();
        $this->components->info($showing);

        return 0;
    }
}
