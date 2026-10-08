<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

/** Thèmes de lancement (cahier des charges, section 1), modifiables ensuite par le comité éditorial. */
class ThemeSeeder extends Seeder
{
    /** @var list<array{name: string, slug: string, description: string}> */
    public const THEMES = [
        ['name' => 'Économie', 'slug' => 'economie', 'description' => 'Budget de l’État, fiscalité, dette, pouvoir d’achat, entreprises.'],
        ['name' => 'Emploi', 'slug' => 'emploi', 'description' => 'Travail, chômage, formation, retraites, conditions de travail.'],
        ['name' => 'Santé', 'slug' => 'sante', 'description' => 'Hôpital, médecine de ville, prévention, assurance maladie.'],
        ['name' => 'Éducation', 'slug' => 'education', 'description' => 'École, université, recherche, orientation.'],
        ['name' => 'Défense', 'slug' => 'defense', 'description' => 'Armées, budget de la défense, sécurité nationale, Europe de la défense.'],
    ];

    public function run(): void
    {
        foreach (self::THEMES as $position => $theme) {
            Theme::query()->updateOrCreate(['slug' => $theme['slug']], $theme + ['position' => $position]);
        }
    }
}
