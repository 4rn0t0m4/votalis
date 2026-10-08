<?php

use App\Enums\ProposalOrigin;
use App\Enums\ProposalStatus;
use App\Enums\RevisionKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->restrictOnDelete();
            // Null pour un contenu d'amorçage ou un compte supprimé (« participant supprimé »).
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            // Familles et variantes (V2) : colonnes réservées.
            $table->unsignedBigInteger('family_id')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('proposals')->nullOnDelete();
            $table->string('title', 120);
            $table->string('problem', 500);
            $table->string('measure', 1500);
            $table->string('cost_estimate', 300)->nullable();
            $table->boolean('cost_unknown')->default(false);
            $table->string('origin', 20)->default(ProposalOrigin::Citizen->value);
            $table->string('seed_source', 300)->nullable();
            $table->string('status', 20)->default(ProposalStatus::Published->value);
            // Posé par le premier vote (lot 3) : ensuite seules les corrections de forme sont permises.
            $table->timestamp('content_locked_at')->nullable();
            $table->timestamps();

            $table->index(['theme_id', 'status', 'created_at']);
        });

        $origins = implode(', ', array_map(fn (ProposalOrigin $o) => "'{$o->value}'", ProposalOrigin::cases()));
        $statuses = implode(', ', array_map(fn (ProposalStatus $s) => "'{$s->value}'", ProposalStatus::cases()));
        DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_origin_check CHECK (origin IN ({$origins}))");
        DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ({$statuses}))");

        Schema::create('proposal_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('url', 500)->nullable();
            $table->string('label', 200)->nullable();
            $table->boolean('is_personal')->default(false);
        });

        Schema::create('proposal_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 20)->default(RevisionKind::Content->value);
            $table->jsonb('snapshot');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_revisions');
        Schema::dropIfExists('proposal_sources');
        Schema::dropIfExists('proposals');
    }
};
