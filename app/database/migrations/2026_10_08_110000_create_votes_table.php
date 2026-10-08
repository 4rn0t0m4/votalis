<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            // Identifiant interne du compte, jamais l'e-mail ; effacé avec le compte (CDC section 8).
            $table->foreignId('participant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            // Souhaitable pour vous ? / Nécessaire pour le pays ? : 1 oui, 0 je ne sais pas, -1 non.
            $table->smallInteger('desirable');
            $table->smallInteger('necessary');
            // « Oui, à condition que… » : présent quand au moins une réponse est un oui conditionnel.
            $table->string('condition', 200)->nullable();
            // Vote initial conservé pour mesurer l'effet de l'information (CDC 4.3).
            $table->smallInteger('desirable_initial');
            $table->smallInteger('necessary_initial');
            $table->boolean('revised_after_arguments')->default(false);
            $table->timestamps();

            $table->primary(['participant_id', 'proposal_id']);
            $table->index(['proposal_id', 'desirable', 'necessary']);
            $table->index(['participant_id', 'created_at']);
        });

        DB::statement('ALTER TABLE votes ADD CONSTRAINT votes_desirable_check CHECK (desirable IN (-1, 0, 1))');
        DB::statement('ALTER TABLE votes ADD CONSTRAINT votes_necessary_check CHECK (necessary IN (-1, 0, 1))');
        DB::statement('ALTER TABLE votes ADD CONSTRAINT votes_desirable_initial_check CHECK (desirable_initial IN (-1, 0, 1))');
        DB::statement('ALTER TABLE votes ADD CONSTRAINT votes_necessary_initial_check CHECK (necessary_initial IN (-1, 0, 1))');

        Schema::table('proposals', function (Blueprint $table) {
            // Compteur dénormalisé, tenu par VoteService, pour le vote rapide et les classements.
            $table->unsignedInteger('votes_count')->default(0)->index();
        });
    }

    public function down(): void
    {
        Schema::table('proposals', fn (Blueprint $table) => $table->dropColumn('votes_count'));
        Schema::dropIfExists('votes');
    }
};
