<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_installments', function (Blueprint $table) {
            $table->id();

            // Entidade centralizadora — pertence a uma pessoa
            $table->integer('person_id')->unsigned();
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('restrict');

            $table->string('number');                          // Número do plano de parcelamento
            $table->decimal('original_value', 18, 2);          // Valor total original
            $table->decimal('delinquent_value', 18, 2);        // Valor em atraso
            $table->decimal('paid_value', 18, 2);              // Valor já pago
            $table->decimal('interest_value', 18, 2);          // Juros acumulados
            $table->decimal('penalty_value', 18, 2);           // Multas acumuladas
            $table->date('due_date');                           // Vencimento mais antigo
            $table->string('status');                           // Status geral do plano

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_installments');
    }
};
