<?php

use App\Enums\ArgumentSide;
use App\Enums\ArgumentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arguments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('side', 10);
            $table->string('body', 600);
            $table->string('source_url', 500)->nullable();
            $table->string('status', 20)->default(ArgumentStatus::Published->value);
            $table->timestamps();

            $table->index(['proposal_id', 'side', 'status']);
        });

        $sides = implode(', ', array_map(fn (ArgumentSide $s) => "'{$s->value}'", ArgumentSide::cases()));
        $statuses = implode(', ', array_map(fn (ArgumentStatus $s) => "'{$s->value}'", ArgumentStatus::cases()));
        DB::statement("ALTER TABLE arguments ADD CONSTRAINT arguments_side_check CHECK (side IN ({$sides}))");
        DB::statement("ALTER TABLE arguments ADD CONSTRAINT arguments_status_check CHECK (status IN ({$statuses}))");

        Schema::create('argument_marks', function (Blueprint $table) {
            $table->foreignId('participant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('argument_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['participant_id', 'argument_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('argument_marks');
        Schema::dropIfExists('arguments');
    }
};
