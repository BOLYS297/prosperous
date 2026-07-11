<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('debit_validations')) {
            Schema::create('debit_validations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('boutique_id')->constrained('boutiques')->cascadeOnDelete();
                $table->foreignId('initiator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('responder_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('source_type');            // 'achat' | 'depense'
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('motif')->nullable();
                $table->string('status')->default('pending'); // pending | confirmed | rejected
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();

                $table->index(['boutique_id', 'status']);
                $table->index(['source_type', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_validations');
    }
};
