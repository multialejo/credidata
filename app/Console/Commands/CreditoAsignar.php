<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\LogActividad;
use App\Models\Recarga;
use Illuminate\Console\Command;

class CreditoAsignar extends Command
{
    protected $signature = 'credito:asignar {uid} {cantidad}';
    protected $description = 'Asigna créditos manualmente a un cliente';

    public function handle()
    {
        $cliente = Cliente::where('uid', $this->argument('uid'))->first();
        if (!$cliente) {
            $this->error('Cliente no encontrado');
            return 1;
        }

        $cantidad = (int) $this->argument('cantidad');

        $cliente->increment('saldo_creditos', $cantidad);

        Recarga::create([
            'cliente_id' => $cliente->uid,
            'metodo' => 'bonificacion',
            'monto_usd' => 0,
            'creditos_obtenidos' => $cantidad,
            'estado' => 'completada',
        ]);

        LogActividad::create([
            'accion' => 'CREDITOS_ASIGNADOS',
            'actor_id' => $cliente->uid,
            'actor_sistema' => true,
            'detalle' => ['creditos' => $cantidad, 'tipo' => 'manual'],
            'ip_origen' => 'sistema',
        ]);

        $cliente->refresh();
        $this->info("Saldo actualizado: {$cliente->saldo_creditos} créditos.");

        return 0;
    }
}
