<?php

namespace Database\Factories;

use App\Enums\ProposalOrigin;
use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * @return array<model-property<Proposal>, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'author_id' => User::factory(),
            'title' => 'Instaurer '.rtrim(fake()->sentence(4), '.'),
            'problem' => fake()->sentence(12),
            'measure' => fake()->paragraph(3),
            'cost_estimate' => '2 milliards d’euros par an',
            'cost_unknown' => false,
            'origin' => ProposalOrigin::Citizen,
            'status' => ProposalStatus::Published,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Proposal $proposal): void {
            if ($proposal->sources()->doesntExist()) {
                $proposal->sources()->create(['url' => 'https://www.exemple.gouv.fr/rapport', 'is_personal' => false]);
            }
        });
    }

    public function locked(): static
    {
        return $this->state(fn () => ['content_locked_at' => now()]);
    }

    public function seed(): static
    {
        return $this->state(fn () => ['author_id' => null, 'origin' => ProposalOrigin::Seed, 'seed_source' => 'Rapport de test']);
    }
}
