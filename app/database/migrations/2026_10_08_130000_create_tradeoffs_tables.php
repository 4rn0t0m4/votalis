<?php

use App\Enums\SuggestionStatus;
use App\Enums\TradeoffDirection;
use App\Enums\TradeoffStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tradeoffs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 140)->unique();
            // Objectif chiffré et sourcé (CDC 4.9), ex. « trouver 40 milliards d'économies ou de recettes ».
            $table->string('objective', 1000);
            $table->decimal('constraint_value', 14, 2);
            $table->string('unit', 30);
            $table->string('direction', 10)->default(TradeoffDirection::AtLeast->value);
            $table->string('status', 10)->default(TradeoffStatus::Draft->value)->index();
            $table->string('source_url', 500)->nullable();
            $table->string('source_label', 200)->nullable();
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $statuses = implode(', ', array_map(fn (TradeoffStatus $s) => "'{$s->value}'", TradeoffStatus::cases()));
        $directions = implode(', ', array_map(fn (TradeoffDirection $d) => "'{$d->value}'", TradeoffDirection::cases()));
        DB::statement("ALTER TABLE tradeoffs ADD CONSTRAINT tradeoffs_status_check CHECK (status IN ({$statuses}))");
        DB::statement("ALTER TABLE tradeoffs ADD CONSTRAINT tradeoffs_direction_check CHECK (direction IN ({$directions}))");

        Schema::create('tradeoff_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tradeoff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->constrained()->restrictOnDelete();
            // Chiffrage obligatoire et sourcé : une mesure sans chiffrage fiable n'entre pas dans un arbitrage.
            $table->decimal('impact', 14, 2);
            $table->string('uncertainty', 120);
            $table->string('source_url', 500);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['tradeoff_id', 'proposal_id']);
        });

        Schema::create('tradeoff_answers', function (Blueprint $table) {
            $table->foreignId('participant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tradeoff_id')->constrained()->cascadeOnDelete();
            $table->jsonb('item_ids');
            $table->jsonb('conditions');
            $table->decimal('total', 14, 2);
            $table->timestamps();

            $table->primary(['participant_id', 'tradeoff_id']);
        });

        // Historique des combinaisons d'un participant, visible par lui seul (CDC 4.9).
        Schema::create('tradeoff_answer_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tradeoff_id')->constrained()->cascadeOnDelete();
            $table->jsonb('item_ids');
            $table->jsonb('conditions');
            $table->decimal('total', 14, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['participant_id', 'tradeoff_id']);
        });

        // Mesures candidates proposées par les participants, ajoutées par le comité après vérification.
        Schema::create('tradeoff_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tradeoff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('note', 500)->nullable();
            $table->string('status', 10)->default(SuggestionStatus::Pending->value);
            $table->timestamps();

            $table->unique(['tradeoff_id', 'proposal_id']);
        });

        $suggestionStatuses = implode(', ', array_map(fn (SuggestionStatus $s) => "'{$s->value}'", SuggestionStatus::cases()));
        DB::statement("ALTER TABLE tradeoff_suggestions ADD CONSTRAINT tradeoff_suggestions_status_check CHECK (status IN ({$suggestionStatuses}))");
    }

    public function down(): void
    {
        Schema::dropIfExists('tradeoff_suggestions');
        Schema::dropIfExists('tradeoff_answer_revisions');
        Schema::dropIfExists('tradeoff_answers');
        Schema::dropIfExists('tradeoff_items');
        Schema::dropIfExists('tradeoffs');
    }
};
