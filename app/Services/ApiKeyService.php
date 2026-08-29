<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\LogActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApiKeyService
{
    public const SCOPES = ['consulta:cedula', 'consulta:ruc', 'consulta:*'];

    public function issue(Cliente $cliente, array $options = [], ?int $actorId = null, string $ip = 'sistema', string $action = 'API_KEY_GENERADA'): string
    {
        do {
            $secret = bin2hex(random_bytes(32));
            $prefix = substr($secret, 0, 8);
            $publicPrefix = 'cd_sk_'.$prefix;
        } while (Cliente::where('api_key_prefijo', $prefix)->exists());

        $now = now();
        $cliente->update([
            'api_key_hash' => Hash::make($secret),
            'api_key_prefijo' => $prefix,
            'api_key_alias' => $options['alias'] ?? $cliente->api_key_alias,
            'api_key_creada' => $now,
            'api_key_revocada' => false,
            'api_key_revocada_en' => null,
            'api_key_ips_permitidas' => $options['ips'] ?? $cliente->api_key_ips_permitidas,
            'api_key_alcance' => $options['scopes'] ?? ($cliente->api_key_alcance ?: ['consulta:cedula', 'consulta:ruc']),
            'api_key_rotacion_sugerida_en' => $now->copy()->addDays($this->parameter('diasSugerenciaRotacion', 90)),
            'api_key_notificacion_rotacion_enviada' => null,
            'api_key_formato' => 'v2',
        ]);

        LogActividad::create([
            'accion' => $action,
            'actor_id' => $actorId ?? $cliente->usuario_id,
            'actor_sistema' => $actorId === null,
            'detalle' => ['prefijo' => $publicPrefix, 'origen' => $ip],
            'ip_origen' => $ip,
        ]);

        return 'cd_sk_'.$prefix.'_'.$secret;
    }

    public function rotate(Cliente $cliente, array $options, int $actorId, string $ip): string
    {
        return DB::transaction(function () use ($cliente, $options, $actorId, $ip): string {
            $cliente->update(['api_key_revocada' => true, 'api_key_revocada_en' => now()]);
            $this->logRevocation($cliente, $actorId, $ip, 'API_KEY_ROTADA_ANTERIOR');

            return $this->issue($cliente, $options, $actorId, $ip, 'API_KEY_ROTADA');
        });
    }

    public function logRevocation(Cliente $cliente, int $actorId, string $ip, string $action = 'API_KEY_REVOCADA'): void
    {
        LogActividad::create(['accion' => $action, 'actor_id' => $actorId, 'actor_sistema' => false,
            'detalle' => ['prefijo' => $cliente->api_key_prefijo], 'ip_origen' => $ip]);
    }

    public function authenticate(Request $request): Cliente|string|null
    {
        $header = $request->header('Authorization');
        if (! $header || ! preg_match('/^Bearer\s+(.+)$/', $header, $matches)) {
            return null;
        }

        $key = $matches[1];
        if (! preg_match('/^cd_sk_([A-Za-z0-9]{8})_([a-f0-9]{64})$/', $key, $parts)) {
            return 'invalid';
        }

        $cliente = Cliente::where('api_key_prefijo', $parts[1])->first();
        if (! $cliente || ! Hash::check($parts[2], (string) $cliente->api_key_hash)) {
            return 'invalid';
        }
        if ($cliente->api_key_revocada) {
            return 'revoked';
        }
        if ($cliente->usuario->estado !== 'activo') {
            return 'inactive';
        }

        return $cliente;
    }

    public function validateOptions(array $data): array
    {
        $scopes = array_values(array_unique($data['scopes'] ?? ['consulta:cedula', 'consulta:ruc']));
        foreach ($scopes as $scope) {
            if (! in_array($scope, self::SCOPES, true)) {
                throw ValidationException::withMessages(['scopes' => 'El alcance seleccionado no es válido.']);
            }
        }

        $ips = array_values(array_filter(array_map('trim', $data['ips'] ?? [])));
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                throw ValidationException::withMessages(['ips' => "La IP {$ip} no es válida."]);
            }
        }

        return ['alias' => trim((string) ($data['alias'] ?? '')) ?: null, 'ips' => $ips, 'scopes' => $scopes];
    }

    public function parameter(string $key, int $fallback): int
    {
        $value = DB::table('config_parametros')->where('modulo', 'apiKeys')->where('clave', $key)->value('valor');

        return $value === null ? $fallback : (int) json_decode($value, true);
    }
}
