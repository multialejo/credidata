<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recargas', function (Blueprint $table) {
            $table->string('provider_payment_id')->nullable()->after('referencia_externa');
            $table->string('provider_transaction_id')->nullable()->after('provider_payment_id');
            $table->string('provider_authorization_code')->nullable()->after('provider_transaction_id');
            $table->string('provider_status')->nullable()->after('provider_authorization_code');
            $table->decimal('provider_amount', 10, 2)->nullable()->after('provider_status');
            $table->string('provider_currency', 3)->nullable()->after('provider_amount');
            $table->timestamp('provider_verified_at')->nullable()->after('provider_currency');

            $table->unique(['metodo', 'provider_payment_id']);
            $table->unique(['metodo', 'provider_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::table('recargas', function (Blueprint $table) {
            $table->dropUnique(['metodo', 'provider_payment_id']);
            $table->dropUnique(['metodo', 'provider_transaction_id']);
            $table->dropColumn([
                'provider_payment_id',
                'provider_transaction_id',
                'provider_authorization_code',
                'provider_status',
                'provider_amount',
                'provider_currency',
                'provider_verified_at',
            ]);
        });
    }
};
