<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('pseudonym', 40);
            // E-mail chiffré (cast `encrypted`) : jamais interrogé directement.
            $table->text('email');
            // HMAC-SHA256 de l'e-mail normalisé, clé dédiée : sert à l'unicité et à la connexion.
            $table->char('email_hash', 64)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 20)->default(Role::Participant->value)->index();
            // Consentement explicite au traitement de données d'opinion (RGPD art. 9).
            $table->timestamp('consented_at');
            $table->rememberToken();
            $table->timestamps();
        });

        // Unicité du pseudonyme insensible à la casse.
        DB::statement('CREATE UNIQUE INDEX users_pseudonym_lower_unique ON users (lower(pseudonym))');

        $roles = implode(', ', array_map(fn (Role $r) => "'{$r->value}'", Role::cases()));
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$roles}))");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            // Contient le haché de l'e-mail (User::getEmailForPasswordReset), jamais l'e-mail en clair.
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

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
