<?php

namespace Tests\Feature\Models;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pruebas de restricciones a nivel MySQL.
 *
 * @requires phpunit mysql
 */
class ExclusiveAccessRoleMysqlTest extends TestCase
{
    use RefreshDatabase;

    public function test_escritura_directa_no_puede_crear_usuario_con_cliente_y_staff(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requiere MySQL para restricciones compuestas.');
        }

        DB::table('usuarios')->insert([
            'email' => 'dual@test.com',
            'nombre' => 'Dual',
            'roles' => json_encode(['cliente', 'staff']),
            'tipo_acceso' => 'cliente',
        ]);

        $userId = DB::getPdo()->lastInsertId();

        $this->expectException(QueryException::class);

        DB::table('clientes')->insert([
            'usuario_id' => $userId,
            'tipo_acceso' => 'cliente',
        ]);

        DB::table('staff')->insert([
            'usuario_id' => $userId,
            'tipo_acceso' => 'staff',
            'rol_staff' => 'admin',
        ]);
    }

    public function test_escritura_directa_no_puede_crear_staff_para_usuario_cliente(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requiere MySQL para restricciones compuestas.');
        }

        DB::table('usuarios')->insert([
            'email' => 'cliente-staff@test.com',
            'nombre' => 'ClienteStaff',
            'roles' => json_encode(['cliente']),
            'tipo_acceso' => 'cliente',
        ]);

        $userId = DB::getPdo()->lastInsertId();

        DB::table('clientes')->insert([
            'usuario_id' => $userId,
            'tipo_acceso' => 'cliente',
        ]);

        $this->expectException(QueryException::class);

        DB::table('staff')->insert([
            'usuario_id' => $userId,
            'tipo_acceso' => 'staff',
            'rol_staff' => 'admin',
        ]);
    }

    public function test_escritura_directa_no_puede_crear_cliente_para_usuario_staff(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requiere MySQL para restricciones compuestas.');
        }

        DB::table('usuarios')->insert([
            'email' => 'staff-cliente@test.com',
            'nombre' => 'StaffCliente',
            'roles' => json_encode(['staff']),
            'tipo_acceso' => 'staff',
        ]);

        $userId = DB::getPdo()->lastInsertId();

        DB::table('staff')->insert([
            'usuario_id' => $userId,
            'tipo_acceso' => 'staff',
            'rol_staff' => 'admin',
        ]);

        $this->expectException(QueryException::class);

        DB::table('clientes')->insert([
            'usuario_id' => $userId,
            'tipo_acceso' => 'cliente',
        ]);
    }
}
