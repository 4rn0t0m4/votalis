<?php

use App\Enums\SignalStatus;
use App\Enums\SignalType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Signaux d'intégrité (CDC section 7 et 10) : visibles des modérateurs, jamais appliqués automatiquement.
        Schema::create('integrity_signals', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->unsignedSmallInteger('severity');
            // Identifiants internes seulement (propositions, comptes) : jamais de texte ni d'e-mail.
            $table->jsonb('targets');
            $table->jsonb('details')->nullable();
            $table->string('status', 20)->default(SignalStatus::New->value);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->date('window_date');
            $table->timestamps();

            $table->index(['status', 'severity', 'created_at']);
            $table->unique(['type', 'window_date', 'targets']);
        });

        $types = implode(', ', array_map(fn (SignalType $t) => "'{$t->value}'", SignalType::cases()));
        $statuses = implode(', ', array_map(fn (SignalStatus $s) => "'{$s->value}'", SignalStatus::cases()));
        DB::statement("ALTER TABLE integrity_signals ADD CONSTRAINT integrity_signals_type_check CHECK (type IN ({$types}))");
        DB::statement("ALTER TABLE integrity_signals ADD CONSTRAINT integrity_signals_status_check CHECK (status IN ({$statuses}))");

        // Rapports de transparence trimestriels (CDC section 6) : agrégats publics.
        Schema::create('transparency_reports', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->jsonb('data');
            $table->timestamp('generated_at')->useCurrent();

            $table->unique(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transparency_reports');
        Schema::dropIfExists('integrity_signals');
    }
};
