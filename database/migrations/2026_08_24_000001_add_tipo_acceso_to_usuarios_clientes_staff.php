<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        // --- usuarios ---
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('tipo_acceso')->nullable()->after('roles');
        });

        if ($isMysql) {
            DB::unprepared('CREATE UNIQUE INDEX uq_usuarios_id_tipo_acceso ON usuarios (id, tipo_acceso)');
        } else {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->unique(['id', 'tipo_acceso'], 'uq_usuarios_id_tipo_acceso');
            });
        }

        // --- clientes ---
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('tipo_acceso')->default('cliente');
        });

        if ($isMysql) {
            // Drop existing simple FK and unique index on usuario_id.
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'clientes'
                  AND COLUMN_NAME = 'usuario_id'
                  AND REFERENCED_TABLE_NAME = 'usuarios'
            ");
            foreach ($foreignKeys as $fk) {
                DB::unprepared("ALTER TABLE clientes DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            $indexes = DB::select("
                SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'clientes'
                  AND COLUMN_NAME = 'usuario_id'
                  AND NON_UNIQUE = 0
                  AND INDEX_NAME != 'PRIMARY'
            ");
            foreach ($indexes as $idx) {
                DB::unprepared("ALTER TABLE clientes DROP INDEX `{$idx->INDEX_NAME}`");
            }

            // Composite unique index + FK.
            DB::unprepared('CREATE UNIQUE INDEX uq_clientes_usuario_tipo ON clientes (usuario_id, tipo_acceso)');
            DB::unprepared('
                ALTER TABLE clientes
                ADD CONSTRAINT fk_clientes_usuario_tipo
                FOREIGN KEY (usuario_id, tipo_acceso)
                REFERENCES usuarios (id, tipo_acceso)
                ON DELETE CASCADE ON UPDATE CASCADE
            ');
        } else {
            Schema::table('clientes', function (Blueprint $table) {
                $table->unique(['usuario_id', 'tipo_acceso'], 'uq_clientes_usuario_tipo');
            });
        }

        // --- staff ---
        Schema::table('staff', function (Blueprint $table) {
            $table->string('tipo_acceso')->default('staff');
        });

        if ($isMysql) {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'staff'
                  AND COLUMN_NAME = 'usuario_id'
                  AND REFERENCED_TABLE_NAME = 'usuarios'
            ");
            foreach ($foreignKeys as $fk) {
                DB::unprepared("ALTER TABLE staff DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            $indexes = DB::select("
                SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'staff'
                  AND COLUMN_NAME = 'usuario_id'
                  AND NON_UNIQUE = 0
                  AND INDEX_NAME != 'PRIMARY'
            ");
            foreach ($indexes as $idx) {
                DB::unprepared("ALTER TABLE staff DROP INDEX `{$idx->INDEX_NAME}`");
            }

            DB::unprepared('CREATE UNIQUE INDEX uq_staff_usuario_tipo ON staff (usuario_id, tipo_acceso)');
            DB::unprepared('
                ALTER TABLE staff
                ADD CONSTRAINT fk_staff_usuario_tipo
                FOREIGN KEY (usuario_id, tipo_acceso)
                REFERENCES usuarios (id, tipo_acceso)
                ON DELETE CASCADE ON UPDATE CASCADE
            ');
        } else {
            Schema::table('staff', function (Blueprint $table) {
                $table->unique(['usuario_id', 'tipo_acceso'], 'uq_staff_usuario_tipo');
            });
        }
    }

    public function down(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        if ($isMysql) {
            DB::unprepared('ALTER TABLE clientes DROP FOREIGN KEY fk_clientes_usuario_tipo');
            DB::unprepared('ALTER TABLE clientes DROP INDEX uq_clientes_usuario_tipo');

            DB::unprepared('ALTER TABLE staff DROP FOREIGN KEY fk_staff_usuario_tipo');
            DB::unprepared('ALTER TABLE staff DROP INDEX uq_staff_usuario_tipo');
        } else {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropIndex('uq_clientes_usuario_tipo');
            });
            Schema::table('staff', function (Blueprint $table) {
                $table->dropIndex('uq_staff_usuario_tipo');
            });
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('tipo_acceso');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('tipo_acceso');
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropIndex('uq_usuarios_id_tipo_acceso');
            $table->dropColumn('tipo_acceso');
        });

        if ($isMysql) {
            // Restore original simple FKs + unique index.
            Schema::table('clientes', function (Blueprint $table) {
                $table->unique('usuario_id');
                $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            });
            Schema::table('staff', function (Blueprint $table) {
                $table->unique('usuario_id');
                $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            });
        }
    }
};
