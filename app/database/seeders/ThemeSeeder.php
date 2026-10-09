<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

/** Thèmes de lancement (cahier des charges, section 1), modifiables ensuite par le comité éditorial. */
class ThemeSeeder extends Seeder
{
    /** @var list<array{name: string, slug: string, icon: string, description: string}> */
    public const THEMES = [
        ['name' => 'Économie', 'slug' => 'economie', 'icon' => 'coins', 'description' => 'Budget de l’État, fiscalité, dette, pouvoir d’achat, entreprises.'],
        ['name' => 'Emploi', 'slug' => 'emploi', 'icon' => 'briefcase', 'description' => 'Travail, chômage, formation, retraites, conditions de travail.'],
        ['name' => 'Santé', 'slug' => 'sante', 'icon' => 'pulse', 'description' => 'Hôpital, médecine de ville, prévention, assurance maladie.'],
        ['name' => 'Éducation', 'slug' => 'education', 'icon' => 'graduation', 'description' => 'École, université, recherche, orientation.'],
        ['name' => 'Défense', 'slug' => 'defense', 'icon' => 'shield', 'description' => 'Armées, budget de la défense, sécurité nationale, Europe de la défense.'],
        ['name' => 'Écologie', 'slug' => 'ecologie', 'icon' => 'leaf', 'description' => 'Climat, énergie, eau, forêts, biodiversité, transition écologique.'],
        ['name' => 'Justice', 'slug' => 'justice', 'icon' => 'gavel', 'description' => 'Tribunaux, peines, prisons, accès au droit, réinsertion.'],
        ['name' => 'Logement', 'slug' => 'logement', 'icon' => 'home', 'description' => 'Construction, logement social, loyers, fiscalité du logement, rénovation.'],
        ['name' => 'Culture & Sport', 'slug' => 'culture-sport', 'icon' => 'book', 'description' => 'Accès à la culture, patrimoine, pratique sportive, sport professionnel.'],
    ];

    public function run(): void
    {
        foreach (self::THEMES as $position => $theme) {
            Theme::query()->updateOrCreate(['slug' => $theme['slug']], $theme + ['position' => $position]);
        }
    }
}
