<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $dimension = (int) config('votalis.embeddings.dimension', 384);

        // Vecteur sémantique de la fiche (titre + mesure), calculé par le service Python interne.
        DB::statement("ALTER TABLE proposals ADD COLUMN embedding vector({$dimension})");

        Schema::table('proposals', function (Blueprint $table) {
            $table->string('embedding_version', 80)->nullable();
            $table->timestamp('embedded_at')->nullable();
        });

        DB::statement('CREATE INDEX proposals_embedding_hnsw ON proposals USING hnsw (embedding vector_cosine_ops)');

        // Regroupement des conditions « oui, à condition que… » par similarité sémantique (CDC 4.3).
        Schema::create('vote_condition_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('label', 200);
            $table->unsignedInteger('count');
            $table->jsonb('conditions');
            $table->timestamp('computed_at');

            $table->index(['proposal_id', 'count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_condition_groups');
        DB::statement('DROP INDEX IF EXISTS proposals_embedding_hnsw');
        Schema::table('proposals', fn (Blueprint $table) => $table->dropColumn(['embedding', 'embedding_version', 'embedded_at']));
    }
};
