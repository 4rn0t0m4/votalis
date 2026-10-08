<?php

namespace Database\Factories;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    /**
     * @return array<model-property<Theme>, mixed>
     */
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(2), '.');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'status' => ThemeStatus::Open,
            'position' => 0,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => ThemeStatus::Closed]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => ThemeStatus::Archived]);
    }

    public function childOf(Theme $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
