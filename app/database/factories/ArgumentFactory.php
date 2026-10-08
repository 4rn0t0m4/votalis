<?php

namespace Database\Factories;

use App\Enums\ArgumentSide;
use App\Enums\ArgumentStatus;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Argument>
 */
class ArgumentFactory extends Factory
{
    /**
     * @return array<model-property<Argument>, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'author_id' => User::factory(),
            'side' => fake()->randomElement(ArgumentSide::cases()),
            'body' => fake()->sentence(15),
            'source_url' => null,
            'status' => ArgumentStatus::Published,
        ];
    }

    public function side(ArgumentSide $side): static
    {
        return $this->state(fn () => ['side' => $side]);
    }
}
