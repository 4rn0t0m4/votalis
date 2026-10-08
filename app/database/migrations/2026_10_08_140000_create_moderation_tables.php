<?php

use App\Enums\ActorRole;
use App\Enums\ModerationAction;
use App\Enums\ProposalStatus;
use App\Enums\ReportMotive;
use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal public, en ajout seul (CDC section 6 et 13, lot 4). Aucune clé étrangère :
        // la suppression d'un compte ou d'un contenu (lot 5) ne doit jamais toucher une entrée.
        Schema::create('moderation_log', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 30);
            $table->unsignedBigInteger('target_id');
            $table->string('action', 30);
            $table->string('motive', 30)->nullable();
            // Identifiant interne de l'acteur, jamais affiché ; null pour une action automatique.
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_role', 20);
            // Identifiants seulement (fiche conservée pour un doublon, durée d'une suspension) : jamais de texte.
            $table->jsonb('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });

        $actions = $this->quoted(array_map(fn (ModerationAction $a) => $a->value, ModerationAction::cases()));
        $motives = $this->quoted(array_map(fn (ReportMotive $m) => $m->value, ReportMotive::cases()));
        $roles = $this->quoted(array_map(fn (ActorRole $r) => $r->value, ActorRole::cases()));
        DB::statement("ALTER TABLE moderation_log ADD CONSTRAINT moderation_log_action_check CHECK (action IN ({$actions}))");
        DB::statement("ALTER TABLE moderation_log ADD CONSTRAINT moderation_log_motive_check CHECK (motive IS NULL OR motive IN ({$motives}))");
        DB::statement("ALTER TABLE moderation_log ADD CONSTRAINT moderation_log_actor_role_check CHECK (actor_role IN ({$roles}))");

        // Immuabilité garantie par la base elle-même : UPDATE, DELETE et TRUNCATE sont refusés.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION moderation_log_immutable() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'moderation_log est en ajout seul : % interdit', TG_OP
                    USING ERRCODE = 'insufficient_privilege';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER moderation_log_no_update_delete
                BEFORE UPDATE OR DELETE ON moderation_log
                FOR EACH ROW EXECUTE FUNCTION moderation_log_immutable();

            CREATE TRIGGER moderation_log_no_truncate
                BEFORE TRUNCATE ON moderation_log
                FOR EACH STATEMENT EXECUTE FUNCTION moderation_log_immutable();
        SQL);

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 30);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motive', 30);
            $table->string('details', 300)->nullable();
            $table->string('status', 20)->default(ReportStatus::Open->value);
            $table->foreignId('log_entry_id')->nullable()->constrained('moderation_log');
            $table->timestamps();

            $table->unique(['target_type', 'target_id', 'reporter_id']);
            $table->index(['status', 'created_at']);
        });

        $statuses = $this->quoted(array_map(fn (ReportStatus $s) => $s->value, ReportStatus::cases()));
        DB::statement("ALTER TABLE reports ADD CONSTRAINT reports_motive_check CHECK (motive IN ({$motives}))");
        DB::statement("ALTER TABLE reports ADD CONSTRAINT reports_status_check CHECK (status IN ({$statuses}))");

        Schema::table('proposals', function (Blueprint $table) {
            $table->string('hidden_motive', 30)->nullable();
            $table->timestamp('rewrite_allowed_until')->nullable();
        });
        Schema::table('arguments', function (Blueprint $table) {
            $table->string('hidden_motive', 30)->nullable();
        });

        $proposalStatuses = $this->quoted(array_map(fn (ProposalStatus $s) => $s->value, ProposalStatus::cases()));
        DB::statement('ALTER TABLE proposals DROP CONSTRAINT proposals_status_check');
        DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ({$proposalStatuses}))");
        DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_hidden_motive_check CHECK (hidden_motive IS NULL OR hidden_motive IN ({$motives}))");
        DB::statement("ALTER TABLE arguments ADD CONSTRAINT arguments_hidden_motive_check CHECK (hidden_motive IS NULL OR hidden_motive IN ({$motives}))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE arguments DROP CONSTRAINT IF EXISTS arguments_hidden_motive_check');
        DB::statement('ALTER TABLE proposals DROP CONSTRAINT IF EXISTS proposals_hidden_motive_check');
        Schema::table('arguments', fn (Blueprint $table) => $table->dropColumn('hidden_motive'));
        Schema::table('proposals', fn (Blueprint $table) => $table->dropColumn(['hidden_motive', 'rewrite_allowed_until']));
        DB::statement('ALTER TABLE proposals DROP CONSTRAINT proposals_status_check');
        DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ('published', 'hidden', 'merged'))");

        Schema::dropIfExists('reports');
        Schema::dropIfExists('moderation_log');
        DB::unprepared('DROP FUNCTION IF EXISTS moderation_log_immutable()');
    }

    /**
     * @param  list<string>  $values
     */
    private function quoted(array $values): string
    {
        return implode(', ', array_map(fn (string $v) => "'{$v}'", $values));
    }
};
