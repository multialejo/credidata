<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('uid')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('firebase_uid')->unique()->nullable();
            $table->string('nombre');
            $table->string('password')->nullable();
            $table->string('estado')->default('activo');
            $table->rememberToken();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->json('roles');
            $table->string('tipo_acceso')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tipo_acceso'], 'uq_usuarios_id_tipo_acceso');
        });

        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('tipo_acceso')->default('cliente');
            $table->decimal('saldo_creditos', 10, 2)->default(0);
            $table->string('metodo_pago_preferido')->default('paypal');
            $table->timestamps();

            $table->unique(['usuario_id', 'tipo_acceso'], 'uq_clientes_usuario_tipo');
            $table->foreign(['usuario_id', 'tipo_acceso'], 'fk_clientes_usuario_tipo')
                ->references(['id', 'tipo_acceso'])->on('usuarios')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('colaboradores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()->unique();
            $table->string('terminos_version')->nullable();
            $table->timestamp('terminos_aceptados_en')->nullable();
            $table->integer('creditos_ganados')->default(0);
            $table->integer('creditos_acreditados')->default(0);
            $table->integer('total_aportes')->default(0);
            $table->integer('aportes_aprobados')->default(0);
            $table->integer('aportes_rechazados')->default(0);
            $table->decimal('tasa_aprobacion', 5, 2)->default(0);
            $table->string('nivel_confianza')->default('bronce');
            $table->string('estado_colaborador')->default('activo');
            $table->timestamp('fecha_suspension')->nullable();
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('tipo_acceso')->default('staff');
            $table->string('rol_staff');
            $table->timestamp('fecha_asignacion')->useCurrent();
            $table->timestamps();

            $table->unique(['usuario_id', 'tipo_acceso'], 'uq_staff_usuario_tipo');
            $table->foreign(['usuario_id', 'tipo_acceso'], 'fk_staff_usuario_tipo')
                ->references(['id', 'tipo_acceso'])->on('usuarios')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('config_parametros', function (Blueprint $table): void {
            $table->string('modulo');
            $table->string('clave');
            $table->json('valor');
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->primary(['modulo', 'clave']);
            $table->timestamps();
        });

        $now = now();

        DB::table('config_parametros')->insertOrIgnore([
            [
                'modulo' => 'financiero',
                'clave' => 'creditosBienvenida',
                'valor' => json_encode(0),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'modulo' => 'colaboracion',
                'clave' => 'limiteDiarioPorIdentificador',
                'valor' => json_encode(3),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'modulo' => 'colaboracion',
                'clave' => 'limiteDiarioPorColaborador',
                'valor' => json_encode(10),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Schema::create('config_rate_overrides', function (Blueprint $table): void {
            $table->foreignId('cliente_id')->primary()->constrained('clientes')->cascadeOnDelete();
            $table->integer('por_minuto');
            $table->integer('por_dia');
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->string('prefijo')->unique();
            $table->string('hash');
            $table->string('formato')->default('v2');
            $table->timestamp('creada_en')->useCurrent();
            $table->boolean('revocada')->default(false);
            $table->timestamp('revocada_en')->nullable();
            $table->timestamp('ultimo_uso_en')->nullable();
            $table->json('ips_permitidas')->nullable();
            $table->json('alcance')->nullable();
            $table->timestamp('rotacion_sugerida_en')->nullable();
            $table->timestamp('notificacion_rotacion_enviada_en')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'nombre']);
            $table->index(['cliente_id', 'revocada']);
        });

        Schema::create('consultas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('tipo');
            $table->string('identificador');
            $table->string('sujeto_id')->nullable();
            $table->string('origen')->default('api');
            $table->integer('creditos_gastados')->default(0);
            $table->json('resultado_json')->nullable();
            $table->json('fuentes_utilizadas')->nullable();
            $table->boolean('exitosa')->default(false);
            $table->string('ip_origen')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
        });

        Schema::create('recargas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('metodo');
            $table->decimal('monto_usd', 10, 2)->default(0);
            $table->integer('creditos_obtenidos')->default(0);
            $table->string('estado')->default('pendiente');
            $table->string('referencia_externa')->nullable()->unique();
            $table->string('provider_payment_id')->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->string('provider_authorization_code')->nullable();
            $table->string('provider_status')->nullable();
            $table->decimal('provider_amount', 10, 2)->nullable();
            $table->string('provider_currency', 3)->nullable();
            $table->timestamp('provider_verified_at')->nullable();
            $table->string('comprobante_url')->nullable();
            $table->string('motivo_rechazo', 500)->nullable();
            $table->timestamp('rechazada_at')->nullable();
            $table->foreignId('rechazada_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            $table->unique(['metodo', 'provider_payment_id']);
            $table->unique(['metodo', 'provider_transaction_id']);
        });

        Schema::create('aportes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('identificador_relacionado');
            $table->string('tipo_dato');
            $table->string('valor');
            $table->string('evidencia_url')->nullable();
            $table->string('estado')->default('pendiente');
            $table->unsignedBigInteger('revisado_por')->nullable();
            $table->text('comentario_rechazo')->nullable();
            $table->text('comentario_revision')->nullable();
            $table->timestamp('revisado_en')->nullable();
            $table->unsignedInteger('recompensa_creditos')->default(0);
            $table->timestamp('recompensado_en')->nullable();
            $table->boolean('aplicacion_pendiente')->default(false);
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            $table->index(['estado', 'fecha']);
        });

        Schema::create('logs_actividad', function (Blueprint $table): void {
            $table->id();
            $table->string('accion');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->boolean('actor_sistema')->default(false);
            $table->json('detalle')->nullable();
            $table->string('ip_origen')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
        });

        Schema::create('intenciones_payphone', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('ctid')->unique();
            $table->string('payment_id')->nullable();
            $table->decimal('monto_usd', 10, 2);
            $table->integer('creditos_estimados');
            $table->string('moneda', 3)->default('USD');
            $table->string('estado')->default('pendiente');
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            $table->index(['cliente_id', 'estado']);
            $table->index(['estado', 'expira_en']);
        });

        Schema::create('intenciones_paypal', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('order_id')->unique();
            $table->decimal('monto_usd', 10, 2);
            $table->integer('creditos_estimados');
            $table->string('moneda', 3)->default('USD');
            $table->string('estado')->default('pendiente');
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            $table->index(['cliente_id', 'estado']);
            $table->index(['estado', 'expira_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intenciones_paypal');
        Schema::dropIfExists('intenciones_payphone');
        Schema::dropIfExists('logs_actividad');
        Schema::dropIfExists('aportes');
        Schema::dropIfExists('recargas');
        Schema::dropIfExists('consultas');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('config_rate_overrides');
        Schema::dropIfExists('config_parametros');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('colaboradores');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('usuarios');
    }
};
