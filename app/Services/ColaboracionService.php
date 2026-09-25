<?php

namespace App\Services;

use App\Models\Aporte;
use App\Models\Colaborador;
use App\Models\ConfigParametro;
use App\Models\LogActividad;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Kreait\Laravel\Firebase\Facades\Firebase;

class ColaboracionService
{
    public const TERMINOS_VERSION = 'provisional-2026-09-11';

    public const TERMINOS = 'Al aportar información confirmas que es veraz, que puedes compartirla y autorizas a CrediData a validarla y publicarla en sus fuentes de consulta.';

    public function activar(Usuario $usuario, string $ip): Colaborador
    {
        return DB::transaction(function () use ($usuario, $ip) {
            $usuario = Usuario::lockForUpdate()->findOrFail($usuario->id);
            if (! $usuario->cliente || $usuario->staff) {
                throw ValidationException::withMessages(['terminos' => 'Solo usuarios con perfil cliente pueden ser colaboradores.']);
            }

            $roles = $usuario->rolesNormalizados();
            if (! in_array('colaborador', $roles, true)) {
                $roles[] = 'colaborador';
                $usuario->update(['roles' => $roles]);
            }

            $colaborador = Colaborador::firstOrCreate(
                ['usuario_id' => $usuario->id],
                ['terminos_version' => self::TERMINOS_VERSION, 'terminos_aceptados_en' => now()],
            );
            if (! $colaborador->terminos_aceptados_en) {
                $colaborador->update(['terminos_version' => self::TERMINOS_VERSION, 'terminos_aceptados_en' => now()]);
            }

            if ($colaborador->wasRecentlyCreated) {
                LogActividad::create(['accion' => 'colaborador.activado', 'actor_id' => $usuario->id, 'detalle' => ['terminos_version' => self::TERMINOS_VERSION, 'terminos_aceptados_en' => $colaborador->terminos_aceptados_en?->toIso8601String()], 'ip_origen' => $ip]);
            }

            return $colaborador->fresh();
        });
    }

    public function registrarAporte(Colaborador $colaborador, string $identificador, string $tipo, string $valor, string $ip): Aporte
    {
        if ($colaborador->estado_colaborador !== 'activo') {
            throw ValidationException::withMessages(['colaborador' => 'El colaborador no está activo.']);
        }

        $recoveryQuery = Aporte::query()
            ->where('colaborador_id', $colaborador->id)
            ->where('identificador_relacionado', $identificador)
            ->where('tipo_dato', $tipo)
            ->where('valor', $valor)
            ->where('aplicacion_pendiente', true);
        $recuperable = (clone $recoveryQuery)->latest('id')->first();
        if ($recuperable) {
            return $this->aplicarPendiente($recuperable, $this->destination($identificador, $tipo), $ip);
        }

        $pendingQuery = Aporte::query()
            ->where('colaborador_id', $colaborador->id)
            ->where('identificador_relacionado', $identificador)
            ->where('tipo_dato', $tipo)
            ->where('valor', $valor)
            ->where('estado', 'pendiente')
            ->where('aplicacion_pendiente', false);
        $pendiente = (clone $pendingQuery)->latest('id')->first();
        if ($pendiente) {
            return $pendiente;
        }

        $destination = $this->destination($identificador, $tipo);
        if ($this->containsValue($destination['actual'], $valor)) {
            throw ValidationException::withMessages(['valor' => 'Este dato ya está registrado para el identificador indicado.']);
        }

        $aporte = DB::transaction(function () use ($colaborador, $identificador, $tipo, $valor, $destination, $recoveryQuery, $pendingQuery) {
            $colaborador = Colaborador::query()->lockForUpdate()->findOrFail($colaborador->id);
            if ($colaborador->estado_colaborador !== 'activo') {
                throw ValidationException::withMessages(['colaborador' => 'El colaborador no está activo.']);
            }

            $recuperable = (clone $recoveryQuery)->latest('id')->first();
            if ($recuperable) {
                return $recuperable;
            }
            $pendiente = (clone $pendingQuery)->latest('id')->first();
            if ($pendiente) {
                return $pendiente;
            }

            $inicioDia = now()->startOfDay();
            $solicitudesHoy = Aporte::query()
                ->where('colaborador_id', $colaborador->id)
                ->whereBetween('fecha', [$inicioDia, now()]);
            $porIdentificador = (clone $solicitudesHoy)
                ->where('identificador_relacionado', $identificador)
                ->count();
            $total = $solicitudesHoy->count();
            if ($porIdentificador >= $this->limiteDiario('limiteDiarioPorIdentificador', 3)) {
                throw ValidationException::withMessages(['identificador' => 'Alcanzaste el límite diario de aportes para este identificador.']);
            }
            if ($total >= $this->limiteDiario('limiteDiarioPorColaborador', 10)) {
                throw ValidationException::withMessages(['colaborador' => 'Alcanzaste el límite diario de aportes.']);
            }

            return Aporte::create([
                'colaborador_id' => $colaborador->id,
                'identificador_relacionado' => $identificador,
                'tipo_dato' => $tipo,
                'valor' => $valor,
                'estado' => 'pendiente',
                'fecha' => now(),
                'aplicacion_pendiente' => ! $this->hasValue($destination['actual']),
            ]);
        });

        if (! $aporte->aplicacion_pendiente) {
            return $aporte;
        }

        return $this->aplicarPendiente($aporte, $destination, $ip);
    }

    public function decidir(Aporte $aporte, Usuario $staff, bool $aprobar, ?string $comentario, string $ip): Aporte
    {
        return DB::transaction(function () use ($aporte, $staff, $aprobar, $comentario, $ip) {
            $aporte = Aporte::lockForUpdate()->findOrFail($aporte->id);
            if ($aporte->estado !== 'pendiente') {
                return $aporte;
            }
            if (! $aprobar) {
                $aporte->update(['estado' => 'rechazado', 'revisado_por' => $staff->id, 'comentario_revision' => $comentario, 'revisado_en' => now()]);
                $this->updateCounters($aporte->colaborador, false);
                $this->logDecision($aporte, $staff, $ip);

                return $aporte->fresh();
            }

            $destination = $this->destination($aporte->identificador_relacionado, $aporte->tipo_dato);
            if ($this->containsValue($destination['actual'], $aporte->valor)) {
                throw ValidationException::withMessages(['aporte' => 'Este dato ya está registrado para el identificador indicado.']);
            }
            $this->writeDestination($destination, $aporte->valor);
            $aporte->update(['estado' => 'aprobado', 'revisado_por' => $staff->id, 'comentario_revision' => $comentario, 'revisado_en' => now()]);
            $this->acreditar($aporte, $this->recompensa('financiero', 'recompensaAporteCreditos'), $ip);
            $this->logDecision($aporte, $staff, $ip);

            return $aporte->fresh();
        });
    }

    public function valorActual(Aporte $aporte): mixed
    {
        return $this->destination($aporte->identificador_relacionado, $aporte->tipo_dato)['actual'];
    }

    private function aplicarPendiente(Aporte $aporte, array $destination, string $ip): Aporte
    {
        $this->writeDestination($destination, $aporte->valor);

        return DB::transaction(function () use ($aporte, $ip) {
            $aporte = Aporte::lockForUpdate()->findOrFail($aporte->id);
            if (! $aporte->aplicacion_pendiente) {
                return $aporte;
            }
            $aporte->update(['estado' => 'aprobado', 'aplicacion_pendiente' => false]);
            $this->acreditar($aporte, $this->recompensa('colaboracion', 'recompensaNuevoRegistroCreditos'), $ip);

            return $aporte->fresh();
        });
    }

    private function acreditar(Aporte $aporte, int $creditos, string $ip): void
    {
        if ($aporte->recompensado_en) {
            return;
        }
        $colaborador = Colaborador::lockForUpdate()->findOrFail($aporte->colaborador_id);
        $cliente = $colaborador->usuario->cliente;
        $saldoAnterior = $cliente->saldo_creditos;
        $cliente->increment('saldo_creditos', $creditos);
        $aporte->update(['recompensa_creditos' => $creditos, 'recompensado_en' => now()]);
        $this->updateCounters($colaborador, true, $creditos);
        LogActividad::create(['accion' => 'colaborador.recompensa_acreditada', 'actor_sistema' => true, 'detalle' => ['aporte_id' => $aporte->id, 'motivo' => 'aporte_aprobado', 'saldo_anterior' => $saldoAnterior, 'saldo_nuevo' => $saldoAnterior + $creditos, 'creditos' => $creditos], 'ip_origen' => $ip]);
    }

    private function updateCounters(Colaborador $colaborador, bool $aprobado, int $creditos = 0): void
    {
        $total = $colaborador->total_aportes + 1;
        $ok = $colaborador->aportes_aprobados + ($aprobado ? 1 : 0);
        $rejected = $colaborador->aportes_rechazados + ($aprobado ? 0 : 1);
        $colaborador->update(['total_aportes' => $total, 'aportes_aprobados' => $ok, 'aportes_rechazados' => $rejected, 'creditos_ganados' => $colaborador->creditos_ganados + $creditos, 'creditos_acreditados' => $colaborador->creditos_acreditados + $creditos, 'tasa_aprobacion' => $total ? ($ok * 100 / $total) : 0]);
    }

    private function logDecision(Aporte $aporte, Usuario $staff, string $ip): void
    {
        LogActividad::create(['accion' => 'aporte.decidido', 'actor_id' => $staff->id, 'detalle' => ['aporte_id' => $aporte->id, 'estado' => $aporte->estado, 'comentario' => $aporte->comentario_revision, 'revisado_en' => $aporte->revisado_en?->toIso8601String()], 'ip_origen' => $ip]);
    }

    private function recompensa(string $modulo, string $clave): int
    {
        $value = ConfigParametro::where('modulo', $modulo)->where('clave', $clave)->value('valor');

        return max(0, (int) json_decode($value ?? '1'));
    }

    /** @return array{reference: mixed, data: array, key: string, list: bool, actual: mixed} */
    private function destination(string $identificador, string $tipo): array
    {
        $ruc = strlen($identificador) === 13;
        $path = $ruc ? "catastro_sri/{$identificador}_001" : "sujetos/{$identificador}";
        $reference = Firebase::firestore()->database()->document($path);
        $snapshot = $reference->snapshot();
        if (! $snapshot->exists()) {
            throw ValidationException::withMessages(['identificador' => 'No existe un destino para el identificador indicado.']);
        }
        $data = $snapshot->data();
        $key = $ruc ? ['email' => 'correo_electronico', 'telefono' => 'telefono', 'direccion' => 'direccion_completa'][$tipo] : ['email' => 'emails', 'telefono' => 'telefonos', 'direccion' => 'direcciones'][$tipo];
        $actual = $ruc ? ($data[$key] ?? null) : ($data['contacto'][$key] ?? null);

        return compact('reference', 'data', 'key', 'actual') + ['list' => ! $ruc];
    }

    private function writeDestination(array $destination, string $valor): void
    {
        $data = $destination['data'];
        if ($destination['list']) {
            $contacto = $data['contacto'] ?? [];
            $values = is_array($contacto[$destination['key']] ?? null) ? $contacto[$destination['key']] : [];
            if (! in_array($valor, $values, true)) {
                $values[] = $valor;
            }
            $contacto[$destination['key']] = $values;
            $data['contacto'] = $contacto;
        } else {
            $data[$destination['key']] = $valor;
        }
        $destination['reference']->set($data);
    }

    private function hasValue(mixed $value): bool
    {
        return is_array($value) ? count($value) > 0 : filled($value);
    }

    private function containsValue(mixed $actual, string $value): bool
    {
        return is_array($actual) ? in_array($value, $actual, true) : (is_string($actual) && $actual === $value);
    }

    private function limiteDiario(string $clave, int $defecto): int
    {
        $value = ConfigParametro::where('modulo', 'colaboracion')->where('clave', $clave)->value('valor');
        $decoded = json_decode($value ?? 'null');

        return is_int($decoded) && $decoded >= 0 ? $decoded : $defecto;
    }
}
