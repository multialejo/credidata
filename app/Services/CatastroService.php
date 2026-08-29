<?php

namespace App\Services;

use DateTimeInterface;
use Google\Cloud\Firestore\FieldPath;
use Kreait\Laravel\Firebase\Facades\Firebase;

class CatastroService
{
    private const COLLECTION = 'catastro_sri';

    /** @return list<array<string, mixed>> */
    public function buscarEstablecimientos(string $ruc): array
    {
        $prefix = $ruc.'_';
        $documents = Firebase::firestore()->database()->collection(self::COLLECTION)
            ->where(FieldPath::documentId(), '>=', $prefix)
            ->where(FieldPath::documentId(), '<=', $prefix."\u{F8FF}")
            ->orderBy(FieldPath::documentId())
            ->documents();

        $result = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $result[] = $this->normalizar($document->data(), $document->id());
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $raw */
    private function normalizar(array $raw, string $documentId): array
    {
        $text = fn (mixed $value): mixed => $this->texto($value);
        $date = fn (mixed $value): ?string => $this->fecha($value);
        $bool = fn (mixed $value): bool => $this->booleano($value);

        return [
            'numero' => (string) ($raw['NUMERO_ESTABLECIMIENTO'] ?? $this->numeroDesdeId($documentId)),
            'razonSocial' => $text($raw['RAZON_SOCIAL'] ?? null),
            'nombreComercial' => $text($raw['NOMBRE_FANTASIA_COMERCIAL'] ?? null),
            'estadoContribuyente' => $text($raw['ESTADO_CONTRIBUYENTE'] ?? null),
            'estadoEstablecimiento' => $text($raw['ESTADO_ESTABLECIMIENTO'] ?? null),
            'tipoContribuyente' => $text($raw['TIPO_CONTRIBUYENTE'] ?? null),
            'claseContribuyente' => $text($raw['CLASE_CONTRIBUYENTE'] ?? null),
            'obligadoContabilidad' => $bool($raw['obligado'] ?? $raw['OBLIGADO'] ?? false),
            'actividadEconomica' => [
                'codigo' => $raw['CODIGO_CIIU'] ?? null,
                'descripcion' => $text($raw['ACTIVIDAD_ECONOMICA'] ?? null),
            ],
            'ubicacion' => [
                'jurisdiccion' => $text($raw['DESCRIPCION_JURISDICCION_EST'] ?? null),
                'provincia' => $text($raw['DESCRIPCION_PROVINCIA_EST'] ?? null),
                'canton' => $text($raw['DESCRIPCION_CANTON_EST'] ?? null),
                'parroquia' => $text($raw['DESCRIPCION_PARROQUIA_EST'] ?? null),
                'direccion' => $text($raw['direccion_completa'] ?? $raw['DIRECCION_COMPLETA'] ?? null),
            ],
            'fechas' => [
                'inicioActividades' => $date($raw['FECHA_INICIO_ACTIVIDADES'] ?? null),
                'reinicioActividades' => $date($raw['FECHA_REINICIO_ACTIVIDADES'] ?? null),
                'suspensionDefinitiva' => $date($raw['FECHA_SUSPENSION_DEFINITIVA'] ?? null),
                'actualizacion' => $date($raw['FECHA_ACTUALIZACION'] ?? null),
            ],
            'agenteRetencion' => $bool($raw['agente_retencion'] ?? $raw['AGENTE_RETENCION'] ?? false),
            'contribuyenteEspecial' => $bool($raw['esencial_especial'] ?? $raw['ESENCIAL_ESPECIAL'] ?? false),
            'artesanoCalificado' => $bool($raw['artesano_calificado'] ?? $raw['ARTESANO_CALIFICADO'] ?? false),
            'regimenRimpe' => $text($raw['regimen_rimpe'] ?? $raw['REGIMEN_RIMPE'] ?? null),
            'contacto' => [
                'email' => $text($raw['correo_electronico'] ?? $raw['CORREO_ELECTRONICO'] ?? null),
                'telefono' => $text($raw['telefono'] ?? $raw['TELEFONO'] ?? null),
            ],
        ];
    }

    private function numeroDesdeId(string $id): string
    {
        return str_contains($id, '_') ? substr((string) strrchr($id, '_'), 1) : '';
    }

    private function texto(mixed $value): mixed
    {
        if ($value === null || ! is_string($value) || ! preg_match('/[ÃÂâ]/u', $value)) {
            return $value;
        }

        $legacy = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $value);
        // The intermediate ISO bytes are the original UTF-8 bytes after removing
        // the accidental UTF-8 decoding performed by the ETL.
        $repaired = is_string($legacy) && mb_check_encoding($legacy, 'UTF-8') ? $legacy : false;

        return is_string($repaired) && mb_check_encoding($repaired, 'UTF-8')
            && substr_count($repaired, 'Ã') + substr_count($repaired, 'Â') + substr_count($repaired, 'â')
            < substr_count($value, 'Ã') + substr_count($value, 'Â') + substr_count($value, 'â')
            ? $repaired : $value;
    }

    private function fecha(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (method_exists($value, 'get')) {
            $value = $value->get();
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_string($value)) {
            return substr($value, 0, 10);
        }

        return null;
    }

    private function booleano(mixed $value): bool
    {
        return is_string($value) ? in_array(strtoupper($value), ['S', 'SI', 'TRUE', '1'], true) : (bool) $value;
    }
}
