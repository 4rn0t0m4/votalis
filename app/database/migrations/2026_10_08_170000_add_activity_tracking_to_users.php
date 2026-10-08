<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Durée de conservation (CDC section 8) : date de dernière visite connectée, au jour près,
        // mise à jour au plus une fois par jour ; aucun journal de connexion ni adresse IP.
        Schema::table('users', function (Blueprint $table) {
            $table->date('last_seen_at')->nullable()->index();
            $table->timestamp('inactivity_notice_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['last_seen_at', 'inactivity_notice_sent_at']));
    }
};
