<?php

namespace App\Services;

use App\Models\ConfigParametro;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class DinardapService
{
    public function consultar(string $cedula): array
    {
        if (config('dinardap.mock')) {
            Log::info('Dinardap mock activado, devolviendo datos ficticios', ['cedula' => $cedula]);

            return [
                'status' => 'success',
                'data' => [
                    'nombres' => 'Juan Carlos Pérez García',
                    'fechaNacimiento' => '1985-06-15',
                    'lugarNacimiento' => 'Quito',
                    'estadoCivilCodigo' => 1,
                    'conyuge' => 'María Fernanda López',
                    'ubicacion' => [
                        'provincia' => 'Pichincha',
                        'canton' => 'Quito',
                        'parroquia' => 'La Carolina',
                    ],
                ],
            ];
        }

        $response = Http::withToken(config('dinardap.api_token'))
            ->timeout(config('dinardap.timeout'))
            ->post(config('dinardap.api_url'), [
                'cedula' => $cedula,
            ]);

        if ($response->unauthorized()) {
            Log::error('Dinardap API: token inválido o expirado', [
                'status' => $response->status(),
                'cedula' => $cedula,
            ]);

            throw new ConnectionException('Dinardap API: token inválido o expirado');
        }

        $body = $response->json();

        if ($response->notFound() || ($body && !empty($body['error']) && $body['error'] === 'no_encontrado')) {
            return ['status' => 'not_found'];
        }

        if ($response->failed()) {
            Log::error('Dinardap API: error inesperado', [
                'status' => $response->status(),
                'body' => $body,
                'cedula' => $cedula,
            ]);

            throw new ConnectionException('Dinardap API: error inesperado');
        }

        return [
            'status' => 'success',
            'data' => $this->normalizar($body),
        ];
    }

    public function obtenerCache(string $cedula): ?array
    {
        try {
            $doc = Firebase::firestore()
                ->database()
                ->document("sujetos/{$cedula}")
                ->snapshot();

            if (!$doc->exists()) {
                return null;
            }

            $data = $doc->data();

            if (!$this->cacheEsValido($data)) {
                return null;
            }

            $data['ultimaConsulta'] = now()->toIso8601String();

            Firebase::firestore()
                ->database()
                ->document("sujetos/{$cedula}")
                ->set($data);

            return $data;
        } catch (Throwable $e) {
            Log::warning('Firestore: error al leer cache', [
                'cedula' => $cedula,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function guardarCache(string $cedula, array $data): bool
    {
        try {
            $document = [
                'tipoIdentificador' => 'cedula',
                'nombres' => $data['nombres'] ?? '',
                'fechaNacimiento' => $data['fechaNacimiento'] ?? null,
                'lugarNacimiento' => $data['lugarNacimiento'] ?? null,
                'estadoCivilCodigo' => $data['estadoCivilCodigo'] ?? null,
                'conyuge' => $data['conyuge'] ?? null,
                'ubicacion' => [
                    'provincia' => $data['ubicacion']['provincia'] ?? '',
                    'canton' => $data['ubicacion']['canton'] ?? '',
                    'parroquia' => $data['ubicacion']['parroquia'] ?? '',
                ],
                'fuentesUtilizadas' => ['dinardap'],
                'ultimaActualizacion' => now()->toIso8601String(),
                'ultimaConsulta' => now()->toIso8601String(),
            ];

            Firebase::firestore()
                ->database()
                ->document("sujetos/{$cedula}")
                ->set($document);

            return true;
        } catch (Throwable $e) {
            Log::warning('Firestore: error al guardar cache', [
                'cedula' => $cedula,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function normalizar(?array $raw): array
    {
        if ($raw === null) {
            return [
                'nombres' => '',
                'fechaNacimiento' => null,
                'lugarNacimiento' => null,
                'estadoCivilCodigo' => null,
                'conyuge' => null,
                'ubicacion' => [
                    'provincia' => '',
                    'canton' => '',
                    'parroquia' => '',
                ],
            ];
        }

        return [
            'nombres' => $raw['nombres'] ?? $raw['nombre'] ?? $raw['nombreCompleto'] ?? '',
            'fechaNacimiento' => $raw['fechaNacimiento'] ?? $raw['fecha_nacimiento'] ?? null,
            'lugarNacimiento' => $raw['lugarNacimiento'] ?? $raw['lugar_nacimiento'] ?? null,
            'estadoCivilCodigo' => $raw['estadoCivilCodigo'] ?? $raw['estado_civil_codigo'] ?? $raw['estadoCivil'] ?? null,
            'conyuge' => $raw['conyuge'] ?? $raw['nombreConyuge'] ?? null,
            'ubicacion' => [
                'provincia' => $raw['ubicacion']['provincia'] ?? $raw['provincia'] ?? '',
                'canton' => $raw['ubicacion']['canton'] ?? $raw['canton'] ?? '',
                'parroquia' => $raw['ubicacion']['parroquia'] ?? $raw['parroquia'] ?? '',
            ],
        ];
    }

    protected function cacheEsValido(?array $cache): bool
    {
        if ($cache === null || empty($cache['ultimaActualizacion'])) {
            return false;
        }

        $param = ConfigParametro::where('modulo', 'dinardap')
            ->where('clave', 'cacheTTLMinutos')
            ->first();

        $ttlMinutos = $param ? (int) json_decode($param->valor) : 1440;

        try {
            $ultimaActualizacion = now()->createFromFormat(\DateTimeInterface::ISO8601_EXPANDED, $cache['ultimaActualizacion']);
        } catch (Throwable) {
            $ultimaActualizacion = now()->parse($cache['ultimaActualizacion']);
        }

        if (!$ultimaActualizacion) {
            return false;
        }

        $vencimiento = $ultimaActualizacion->copy()->addMinutes($ttlMinutos);

        return $vencimiento->isFuture();
    }
}
