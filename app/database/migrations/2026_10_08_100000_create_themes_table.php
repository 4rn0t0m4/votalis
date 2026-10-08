<?php

use App\Enums\ThemeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('themes')->restrictOnDelete();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->string('status', 20)->default(ThemeStatus::Open->value)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $statuses = implode(', ', array_map(fn (ThemeStatus $s) => "'{$s->value}'", ThemeStatus::cases()));
        DB::statement("ALTER TABLE themes ADD CONSTRAINT themes_status_check CHECK (status IN ({$statuses}))");
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
