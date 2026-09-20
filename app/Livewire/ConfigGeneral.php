<?php

namespace App\Livewire;

use App\Models\ConfigParametro;
use App\Models\LogActividad;
use Closure;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ConfigGeneral extends Component
{
    public const METODOS_PAGO_CONFIG = [
        'paypal' => ['label' => 'PayPal', 'key' => 'metodoPaypalHabilitado'],
        'payphone' => ['label' => 'PayPhone', 'key' => 'metodoPayphoneHabilitado'],
        'tarjeta' => ['label' => 'Tarjeta (débito/crédito)', 'key' => 'metodoTarjetaHabilitado'],
        'transferencia' => ['label' => 'Transferencia bancaria', 'key' => 'metodoTransferenciaHabilitado'],
    ];

    public ?string $editando = null;

    public string $valorEditando = '';

    public string $transferenciaBanco = '';

    public string $transferenciaTipoCuenta = '';

    public string $transferenciaNumeroCuenta = '';

    public string $transferenciaTitular = '';

    public string $transferenciaCedulaTitular = '';

    public string $transferenciaWhatsapp = '';

    public function mount(): void
    {
        $this->cargarDatosTransferencia();
    }

    protected function cargarDatosTransferencia(): void
    {
        $param = ConfigParametro::where('modulo', 'recargas')
            ->where('clave', 'datosTransferencia')
            ->first();

        if (! $param) {
            return;
        }

        $datos = json_decode($param->valor, true);

        if (! is_array($datos)) {
            return;
        }

        $this->transferenciaBanco = $datos['banco'] ?? '';
        $this->transferenciaTipoCuenta = $datos['tipoCuenta'] ?? '';
        $this->transferenciaNumeroCuenta = $datos['numeroCuenta'] ?? '';
        $this->transferenciaTitular = $datos['titular'] ?? '';
        $this->transferenciaCedulaTitular = $datos['cedulaTitular'] ?? '';
        $this->transferenciaWhatsapp = $datos['whatsapp'] ?? '';
    }

    public function iniciarEdicion(string $modulo, string $clave): void
    {
        $param = ConfigParametro::where('modulo', $modulo)
            ->where('clave', $clave)
            ->firstOrFail();

        $this->editando = "{$modulo}.{$clave}";
        $this->valorEditando = $param->valor;
    }

    public function cancelarEdicion(): void
    {
        $this->editando = null;
        $this->valorEditando = '';
    }

    public function guardar(string $modulo, string $clave): void
    {
        $this->validate([
            'valorEditando' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                $decoded = json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail('El valor debe ser JSON válido.');

                    return;
                }

                if (! is_scalar($decoded)) {
                    $fail('El valor debe ser un escalar (string, int, float o bool).');
                }
            }],
        ]);

        $param = ConfigParametro::where('modulo', $modulo)
            ->where('clave', $clave)
            ->firstOrFail();

        $valorNuevo = json_encode(json_decode($this->valorEditando));

        $this->actualizarParametro($param, $valorNuevo);

        $this->cancelarEdicion();

        session()->flash('status', 'Parámetro actualizado correctamente.');
    }

    public function toggleMetodoPago(string $codigo): void
    {
        if (! isset(self::METODOS_PAGO_CONFIG[$codigo])) {
            return;
        }

        $config = self::METODOS_PAGO_CONFIG[$codigo];

        $param = ConfigParametro::firstOrCreate(
            ['modulo' => 'recargas', 'clave' => $config['key']],
            ['valor' => json_encode(true)],
        );

        $valorActual = json_decode($param->valor, true);
        $valorNuevo = ! $valorActual;

        $this->actualizarParametro($param, json_encode($valorNuevo));

        session()->flash('status', "Método {$config['label']} " . ($valorNuevo ? 'habilitado' : 'deshabilitado') . '.');
    }

    public function guardarDatosTransferencia(): void
    {
        $this->validate([
            'transferenciaBanco' => ['required', 'string', 'max:100'],
            'transferenciaTipoCuenta' => ['required', 'string', 'max:100'],
            'transferenciaNumeroCuenta' => ['required', 'string', 'max:20'],
            'transferenciaTitular' => ['required', 'string', 'max:150'],
            'transferenciaCedulaTitular' => ['required', 'string', 'digits:10'],
            'transferenciaWhatsapp' => ['required', 'string', 'digits_between:10,15'],
        ]);

        $param = ConfigParametro::firstOrCreate(
            ['modulo' => 'recargas', 'clave' => 'datosTransferencia'],
            ['valor' => json_encode([])],
        );

        $valorNuevo = json_encode([
            'banco' => $this->transferenciaBanco,
            'tipoCuenta' => $this->transferenciaTipoCuenta,
            'numeroCuenta' => $this->transferenciaNumeroCuenta,
            'titular' => $this->transferenciaTitular,
            'cedulaTitular' => $this->transferenciaCedulaTitular,
            'whatsapp' => $this->transferenciaWhatsapp,
        ]);

        $this->actualizarParametro($param, $valorNuevo);

        session()->flash('status', 'Datos de transferencia bancaria actualizados.');
    }

    protected function actualizarParametro(ConfigParametro $param, string $valorNuevo): void
    {
        $valorAnterior = $param->valor;

        DB::transaction(function () use ($param, $valorNuevo, $valorAnterior): void {
            ConfigParametro::where('modulo', $param->modulo)
                ->where('clave', $param->clave)
                ->update([
                    'valor' => $valorNuevo,
                    'actualizado_por' => auth()->user()->staff->id,
                    'actualizado_en' => now(),
                ]);

            LogActividad::create([
                'accion' => 'config.actualizada',
                'actor_id' => auth()->id(),
                'actor_sistema' => false,
                'detalle' => [
                    'modulo' => $param->modulo,
                    'clave' => $param->clave,
                    'valor_anterior' => json_decode($valorAnterior),
                    'valor_nuevo' => json_decode($valorNuevo),
                ],
                'ip_origen' => request()->ip(),
            ]);
        });
    }

    protected function getEstadosMetodosPago(): array
    {
        $estados = [];

        foreach (self::METODOS_PAGO_CONFIG as $codigo => $config) {
            $param = ConfigParametro::where('modulo', 'recargas')
                ->where('clave', $config['key'])
                ->first();

            $estados[$codigo] = [
                'label' => $config['label'],
                'habilitado' => $param ? (bool) json_decode($param->valor) : true,
            ];
        }

        return $estados;
    }

    public function render()
    {
        $parametros = ConfigParametro::query()
            ->where('modulo', '!=', 'recargas')
            ->orderBy('modulo')
            ->orderBy('clave')
            ->get()
            ->groupBy('modulo');

        return view('livewire.config-general', [
            'parametros' => $parametros,
            'estadosMetodosPago' => $this->getEstadosMetodosPago(),
        ]);
    }
}
