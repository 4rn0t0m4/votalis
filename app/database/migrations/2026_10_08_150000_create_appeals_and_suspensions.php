<?php

use App\Enums\AppealStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contestation d'une décision (CDC section 6) : une seule par entrée du journal.
        Schema::create('appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_entry_id')->unique()->constrained('moderation_log');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('body', 1000);
            $table->string('status', 20)->default(AppealStatus::Pending->value);
            // Arbitre : identifiant interne, jamais affiché ; vérifié différent de l'auteur de la décision par le service.
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_note', 500)->nullable();
            $table->foreignId('decision_log_entry_id')->nullable()->constrained('moderation_log');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        $statuses = implode(', ', array_map(fn (AppealStatus $s) => "'{$s->value}'", AppealStatus::cases()));
        DB::statement("ALTER TABLE appeals ADD CONSTRAINT appeals_status_check CHECK (status IN ({$statuses}))");

        // Suspension d'un compte par le comité éditorial : le motif vit dans le journal, pas sur le compte.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('suspended_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['suspended_at', 'suspended_until']));
        Schema::dropIfExists('appeals');
    }
};
