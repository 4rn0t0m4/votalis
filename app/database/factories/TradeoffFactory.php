<?php

namespace Database\Factories;

use App\Enums\TradeoffDirection;
use App\Enums\TradeoffStatus;
use App\Models\Tradeoff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tradeoff>
 */
class TradeoffFactory extends Factory
{
    /**
     * @return array<model-property<Tradeoff>, mixed>
     */
    public function definition(): array
    {
        $title = 'Trouver '.fake()->numberBetween(10, 60).' milliards '.fake()->unique()->word();

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'objective' => 'Trouver des économies ou des recettes pour ramener le déficit sous 3 % du PIB.',
            'constraint_value' => 40,
            'unit' => 'Md€',
            'direction' => TradeoffDirection::AtLeast,
            'status' => TradeoffStatus::Open,
            'source_url' => 'https://www.exemple.gouv.fr/budget',
            'source_label' => 'Projet de loi de finances',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => TradeoffStatus::Draft]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => TradeoffStatus::Closed]);
    }
}
