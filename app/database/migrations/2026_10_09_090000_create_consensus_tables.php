<?php

use App\Enums\ConsensusRunStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Calculs de consensus (lot 7, CDC section 5). Aucune donnée de compte : seulement des
        // effectifs, les paramètres et l'empreinte des données envoyées au service.
        Schema::create('consensus_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20);
            $table->string('algo_version', 40)->nullable();
            $table->unsignedInteger('participants')->default(0);
            $table->unsignedInteger('proposals')->default(0);
            $table->unsignedSmallInteger('k')->default(0);
            $table->decimal('silhouette', 6, 4)->nullable();
            $table->json('groups')->nullable();
            $table->json('params');
            // SHA-256 de la charge envoyée au service, pour vérifier un export d'audit.
            $table->char('input_digest', 64)->nullable();
            $table->string('error', 200)->nullable();
            $table->timestamp('computed_at');

            $table->index(['status', 'computed_at']);
        });

        $statuses = implode(', ', array_map(fn (ConsensusRunStatus $s) => "'{$s->value}'", ConsensusRunStatus::cases()));
        DB::statement("ALTER TABLE consensus_runs ADD CONSTRAINT consensus_runs_status_check CHECK (status IN ({$statuses}))");

        // Scores par proposition (CDC section 10). L'appartenance individuelle aux groupes n'est
        // jamais écrite (minimisation, CDC section 8).
        Schema::create('consensus_scores', function (Blueprint $table) {
            $table->foreignId('run_id')->constrained('consensus_runs')->cascadeOnDelete();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 4)->nullable();
            $table->decimal('divisiveness', 5, 4)->nullable();
            $table->json('groups');

            $table->primary(['run_id', 'proposal_id']);
            $table->index(['run_id', 'score']);
            $table->index(['run_id', 'divisiveness']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consensus_scores');
        Schema::dropIfExists('consensus_runs');
    }
};
