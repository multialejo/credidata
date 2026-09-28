<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Cliente;
use App\Models\LogActividad;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiKeyService
{
    public const SCOPES = ['consulta:cedula', 'consulta:ruc', 'consulta:*', 'colaboradores:registro', 'colaboradores:aportes', 'colaboradores:*'];

    /** Un permiso está cubierto por una concesión exacta o por el comodín de su familia. */
    public static function cubre(array $otorgados, string $pedido): bool
    {
        return in_array($pedido, $otorgados, true) || in_array(explode(':', $pedido)[0].':*', $otorgados, true);
    }

    public function issue(Cliente $cliente, array $options = [], ?int $actorId = null, string $ip = 'sistema', string $action = 'API_KEY_GENERADA'): string
    {
        $options = $this->validateOptions($options);
        if ($cliente->apiKeys()->where('nombre', $options['name'])->exists()) {
            throw ValidationException::withMessages(['name' => 'Ya existe una API Key con este nombre para el cliente.']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $secret = bin2hex(random_bytes(32));
                $prefix = substr($secret, 0, 8);
                $publicPrefix = 'cd_sk_'.$prefix;
                $now = now();

                $apiKey = $cliente->apiKeys()->create([
                    'nombre' => $options['name'],
                    'hash' => Hash::make($secret),
                    'prefijo' => $prefix,
                    'creada_en' => $now,
                    'revocada' => false,
                    'revocada_en' => null,
                    'ips_permitidas' => $options['ips'],
                    'alcance' => $options['scopes'],
                    'rotacion_sugerida_en' => $now->copy()->addDays($this->parameter('diasSugerenciaRotacion', 90)),
                    'notificacion_rotacion_enviada_en' => null,
                    'formato' => 'v2',
                ]);
                break;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        LogActividad::create([
            'accion' => $action,
            'actor_id' => $actorId ?? $cliente->usuario_id,
            'actor_sistema' => $actorId === null,
            'detalle' => ['api_key_id' => $apiKey->id, 'nombre' => $apiKey->nombre, 'prefijo' => $publicPrefix, 'origen' => $ip],
            'ip_origen' => $ip,
        ]);

        return 'cd_sk_'.$prefix.'_'.$secret;
    }

    public function rotate(ApiKey $apiKey, array $options, int $actorId, string $ip): string
    {
        return DB::transaction(function () use ($apiKey, $options, $actorId, $ip): string {
            $options = $this->validateOptions([
                'name' => $apiKey->nombre,
                'ips' => $options['ips'] ?? $apiKey->ips_permitidas ?? [],
                'scopes' => $options['scopes'] ?? $apiKey->alcance ?? [],
            ]);
            $secret = bin2hex(random_bytes(32));
            $prefix = substr($secret, 0, 8);
            $apiKey->update([
                'prefijo' => $prefix,
                'hash' => Hash::make($secret),
                'creada_en' => now(),
                'revocada' => false,
                'revocada_en' => null,
                'ultimo_uso_en' => null,
                'ips_permitidas' => $options['ips'],
                'alcance' => $options['scopes'],
                'rotacion_sugerida_en' => now()->addDays($this->parameter('diasSugerenciaRotacion', 90)),
                'notificacion_rotacion_enviada_en' => null,
            ]);

            LogActividad::create([
                'accion' => 'API_KEY_ROTADA',
                'actor_id' => $actorId,
                'actor_sistema' => false,
                'detalle' => ['api_key_id' => $apiKey->id, 'nombre' => $apiKey->nombre, 'prefijo' => 'cd_sk_'.$prefix],
                'ip_origen' => $ip,
            ]);

            return 'cd_sk_'.$prefix.'_'.$secret;
        });
    }

    public function revoke(ApiKey $apiKey, int $actorId, string $ip, string $action = 'API_KEY_REVOCADA'): void
    {
        if ($apiKey->revocada) {
            return;
        }

        $apiKey->update(['revocada' => true, 'revocada_en' => now()]);
        LogActividad::create([
            'accion' => $action,
            'actor_id' => $actorId,
            'actor_sistema' => false,
            'detalle' => ['api_key_id' => $apiKey->id, 'nombre' => $apiKey->nombre, 'prefijo' => $apiKey->prefijo],
            'ip_origen' => $ip,
        ]);
    }

    public function authenticate(Request $request): ApiKey|string|null
    {
        $header = $request->header('Authorization');
        if (! $header || ! preg_match('/^Bearer\s+(.+)$/', $header, $matches)) {
            return null;
        }

        $key = $matches[1];
        if (! preg_match('/^cd_sk_([A-Za-z0-9]{8})_([a-f0-9]{64})$/', $key, $parts)) {
            return 'invalid';
        }

        $apiKey = ApiKey::with('cliente.usuario')->where('prefijo', $parts[1])->first();
        if (! $apiKey || ! Hash::check($parts[2], (string) $apiKey->hash)) {
            return 'invalid';
        }
        if ($apiKey->revocada) {
            return 'revoked';
        }
        if ($apiKey->cliente->usuario->estado !== 'activo') {
            return 'inactive';
        }

        return $apiKey;
    }

    public function validateOptions(array $data): array
    {
        if (isset($data['scopes']) && ! is_array($data['scopes'])) {
            throw ValidationException::withMessages(['scopes' => 'Los scopes deben ser un arreglo.']);
        }
        if (isset($data['ips']) && ! is_array($data['ips'])) {
            throw ValidationException::withMessages(['ips' => 'Las IPs deben ser un arreglo.']);
        }

        $scopes = $data['scopes'] ?? ['consulta:cedula', 'consulta:ruc'];
        if ($scopes === []) {
            throw ValidationException::withMessages(['scopes' => 'Selecciona al menos un permiso.']);
        }
        foreach ($scopes as $scope) {
            if (! is_string($scope) || ! in_array($scope, self::SCOPES, true)) {
                throw ValidationException::withMessages(['scopes' => 'El alcance seleccionado no es válido.']);
            }
        }
        $scopes = array_values(array_unique($scopes));

        $ips = [];
        foreach ($data['ips'] ?? [] as $ip) {
            if (! is_string($ip)) {
                throw ValidationException::withMessages(['ips' => 'Cada IP debe ser texto.']);
            }

            $ip = trim($ip);
            if ($ip === '') {
                continue;
            }
            $ips[] = $ip;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                throw ValidationException::withMessages(['ips' => "La IP {$ip} no es válida."]);
            }
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'El nombre del aplicativo es obligatorio.']);
        }
        if (mb_strlen($name) > 100) {
            throw ValidationException::withMessages(['name' => 'El nombre del aplicativo no puede superar 100 caracteres.']);
        }

        return ['name' => $name, 'ips' => array_values(array_unique($ips)), 'scopes' => $scopes];
    }

    public function parameter(string $key, int $fallback): int
    {
        $value = DB::table('config_parametros')->where('modulo', 'apiKeys')->where('clave', $key)->value('valor');

        return $value === null ? $fallback : (int) json_decode($value, true);
    }
}
