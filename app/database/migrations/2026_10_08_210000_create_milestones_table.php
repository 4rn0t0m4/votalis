<?php

use App\Enums\Milestone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jalons du parcours personnel (lot 6) : strictement privés, supprimés avec le compte.
        Schema::create('milestones', function (Blueprint $table) {
            $table->foreignId('participant_id')->constrained('users')->cascadeOnDelete();
            $table->string('key', 40);
            $table->timestamp('reached_at');
            // Célébration affichée une seule fois.
            $table->timestamp('seen_at')->nullable();

            $table->primary(['participant_id', 'key']);
        });

        $keys = implode(', ', array_map(fn (Milestone $m) => "'{$m->value}'", Milestone::cases()));
        DB::statement("ALTER TABLE milestones ADD CONSTRAINT milestones_key_check CHECK (key IN ({$keys}))");

        // Le vote initial a-t-il été posé depuis une vue où les arguments étaient visibles ?
        Schema::table('votes', function (Blueprint $table) {
            $table->boolean('after_arguments')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('votes', fn (Blueprint $table) => $table->dropColumn('after_arguments'));
        Schema::dropIfExists('milestones');
    }
};
