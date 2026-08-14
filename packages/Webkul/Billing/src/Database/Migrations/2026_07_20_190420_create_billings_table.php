<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billings', function (Blueprint $table) {
            $table->id();

            // Cada boleto/cobrança pertence a um plano de parcelamento
            $table->foreignId('billing_installment_id')
                ->constrained('billing_installments')
                ->onDelete('cascade');

            $table->unsignedInteger('installment_number');     // Número da parcela (1, 2, 3...)
            $table->decimal('original_value', 18, 2);          // Valor original desta parcela
            $table->decimal('delinquent_value', 18, 2);        // Valor em atraso desta parcela
            $table->decimal('paid_value', 18, 2);              // Valor pago desta parcela
            $table->decimal('interest_value', 18, 2);          // Juros desta parcela
            $table->decimal('penalty_value', 18, 2);           // Multa desta parcela
            $table->date('due_date');                           // Data de vencimento desta parcela
            $table->date('paid_date')->nullable();              // Data em que foi pago
            $table->string('status');                           // Status desta parcela

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
