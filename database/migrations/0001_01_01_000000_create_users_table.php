<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // La clé étrangère vers "boutiques" est ajoutée plus tard
            // (2026_05_13_221000_add_boutique_fk_to_users_table) car la table
            // "boutiques" n'existe pas encore à ce stade des migrations.
            $table->foreignId('boutique_id')->nullable()->index();
            $table->string('nom_utilisateur')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['super_admin', 'magasinier', 'boutiquier']);
            $table->enum('shift', ['matin', 'soir'])->nullable();
            $table->string('device_token')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
