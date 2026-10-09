<?php

namespace Database\Seeders;

use App\Enums\TradeoffStatus;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\Tradeoff;
use App\Services\TradeoffService;
use Illuminate\Database\Seeder;

/**
 * Développement uniquement : deux arbitrages de démonstration bâtis sur les mesures du jeu
 * d'amorçage. Les impacts sont des ordres de grandeur tirés des rapports cités ; l'hypothèse de
 * calcul est écrite dans le champ « incertitude ». Les mesures sont retrouvées par leur titre.
 */
class DemoTradeoffSeeder extends Seeder
{
    private const CC = 'https://www.ccomptes.fr';

    private const RETRAITES = self::CC.'/fr/publications/situation-financiere-et-perspectives-du-systeme-de-retraites';

    /**
     * @var list<array{theme: string, slug: string, title: string, objective: string, constraint: int, source_url: string, source_label: string, items: list<array{title: string, impact: float|int, uncertainty: string, source_url: string}>}>
     */
    private const TRADEOFFS = [
        [
            'theme' => 'economie',
            'slug' => 'trouver-20-milliards',
            'title' => 'Trouver 20 milliards d’euros par an d’économies ou de recettes',
            'objective' => 'La Cour des comptes évalue à près de 105 milliards d’euros l’effort nécessaire d’ici 2029 pour ramener le déficit public sous 3 % du PIB. Composez une combinaison de mesures qui en couvre une première marche de 20 milliards d’euros par an. Les chiffrages sont des ordres de grandeur tirés des rapports cités, avec leur hypothèse de calcul.',
            'constraint' => 20,
            'source_url' => self::CC.'/fr/publications/la-situation-et-les-perspectives-des-finances-publiques-16',
            'source_label' => 'Cour des comptes, La situation et les perspectives des finances publiques, juillet 2025',
            'items' => [
                ['title' => 'Supprimer les taux réduits de TVA dont l’inefficacité est établie, ou les relever', 'impact' => 12, 'uncertainty' => 'Très élevée : hypothèse d’un quart des 47 Md€ de taux réduits supprimés ou relevés (CPO, 2023)', 'source_url' => self::CC.'/fr/publications/la-tva-une-taxe-recentrer-sur-son-objectif-de-rendement-pour-les-finances-publiques'],
                ['title' => 'Réformer les niches des droits de succession : assurance-vie, démembrement, pacte Dutreil', 'impact' => 3, 'uncertainty' => 'Élevée : coût du seul pacte Dutreil estimé entre 2 et 3 Md€ par an (CAE, 2021), assurance-vie non chiffrée', 'source_url' => 'https://cae-eco.fr/repenser-lheritage'],
                ['title' => 'Recalibrer les allègements généraux de cotisations patronales pour redresser les comptes sociaux', 'impact' => 4, 'uncertainty' => 'Élevée : hypothèse d’un recalibrage de 5 % des 77,3 Md€ d’allègements (Cour des comptes, 2025)', 'source_url' => self::CC.'/fr/publications/securite-sociale-2025'],
                ['title' => 'Adopter un programme pluriannuel de maîtrise des dépenses d’assurance maladie fondé sur la prévention', 'impact' => 4, 'uncertainty' => 'Moyenne : 4 Md€ par an d’économies jugées nécessaires d’ici 2029 (Cour des comptes, avril 2025)', 'source_url' => self::CC.'/sites/default/files/2025-04/20250414-Lobjectif-national-de-depenses-dassurance-maladie-Ondam.pdf'],
                ['title' => 'Recentrer l’aide à l’embauche d’apprentis sur les jeunes les moins qualifiés', 'impact' => 2, 'uncertainty' => 'Élevée : hypothèse d’une réduction de moitié des 4,4 Md€ d’aides versées en 2022 (Cour des comptes, 2023)', 'source_url' => self::CC.'/fr/documents/65357'],
                ['title' => 'Instaurer une participation du titulaire aux formations financées par le compte personnel de formation', 'impact' => 0.25, 'uncertainty' => 'Moyenne : 10 % des 2,5 Md€ de dépenses annuelles du CPF (Cour des comptes, 2023)', 'source_url' => self::CC.'/fr/documents/65357'],
                ['title' => 'Relever le barème de la taxe sur les boissons sucrées et l’étendre aux sirops et boissons végétales', 'impact' => 0.5, 'uncertainty' => 'Élevée : hypothèse d’un doublement du rendement actuel de 0,5 Md€ (CPO, 2023)', 'source_url' => self::CC.'/fr/publications/la-fiscalite-nutritionnelle'],
                ['title' => 'Aligner les niveaux de prise en charge des contrats d’apprentissage sur leurs coûts réels', 'impact' => 0.6, 'uncertainty' => 'Moyenne : 210 M€ par tranche de 5 % de baisse, hypothèse de trois tranches (Cour des comptes, 2023)', 'source_url' => self::CC.'/fr/documents/65357'],
            ],
        ],
        [
            'theme' => 'emploi',
            'slug' => 'equilibrer-les-retraites-en-2035',
            'title' => 'Équilibrer les retraites en 2035',
            'objective' => 'Selon la Cour des comptes, le déficit du système de retraites atteindrait 14 à 15 milliards d’euros en 2035 à règles inchangées. Composez votre propre combinaison des quatre leviers paramétriques, âge d’ouverture des droits, durée d’assurance, taux de cotisation et indexation des pensions, pour couvrir le bas de cette fourchette. Les options sont présentées dans les deux sens : baisser l’âge ou la durée est possible, à condition de le compenser. Les chiffrages sont ceux de la Cour, qui ne recommande aucun levier.',
            'constraint' => 14,
            'source_url' => self::RETRAITES,
            'source_label' => 'Cour des comptes, Situation financière et perspectives du système de retraites, février 2025',
            'items' => [
                ['title' => 'Reculer l’âge d’ouverture des droits à la retraite de 64 à 65 ans', 'impact' => 8.4, 'uncertainty' => 'Moyenne : jusqu’à 8,4 Md€ en 2035 si appliqué dès la génération 1968 ; effet stabilisé ensuite (Cour, 2025)', 'source_url' => self::RETRAITES],
                ['title' => 'Ramener l’âge d’ouverture des droits à la retraite de 64 à 63 ans', 'impact' => -5.8, 'uncertainty' => 'Moyenne : 5,8 Md€ de dépense en plus en 2035 pour les retraites, 13 Md€ pour les finances publiques (Cour, 2025)', 'source_url' => self::RETRAITES],
                ['title' => 'Allonger d’un an la durée d’assurance requise pour une retraite à taux plein, de 43 à 44 ans', 'impact' => 5.2, 'uncertainty' => 'Moyenne : 5,2 Md€ en 2035, effet croissant dans le temps, 8 Md€ en 2045 (Cour des comptes, 2025)', 'source_url' => self::RETRAITES],
                ['title' => 'Réduire d’un an la durée d’assurance requise pour une retraite à taux plein, de 43 à 42 ans', 'impact' => -3.9, 'uncertainty' => 'Moyenne : 3,9 Md€ de coût en 2035, 7,7 Md€ en 2045 (Cour des comptes, 2025)', 'source_url' => self::RETRAITES],
                ['title' => 'Relever d’un point le taux de cotisation retraite', 'impact' => 6, 'uncertainty' => 'Élevée : 4,8 à 7,6 Md€ par an selon l’assiette retenue ; hypothèse médiane de 6 Md€ (Cour des comptes, 2025)', 'source_url' => self::RETRAITES],
                ['title' => 'Revaloriser les pensions d’un point de moins que l’inflation pendant une année', 'impact' => 2.9, 'uncertainty' => 'Moyenne : 2,9 Md€ par point et par an, base 2025 ; l’effet se reporte sur les années suivantes (Cour, 2025)', 'source_url' => self::RETRAITES],
            ],
        ],
    ];

    public function run(TradeoffService $service): void
    {
        foreach (self::TRADEOFFS as $definition) {
            $theme = Theme::query()->where('slug', $definition['theme'])->first();

            if ($theme === null || Tradeoff::query()->where('slug', $definition['slug'])->exists()) {
                continue;
            }

            $tradeoff = Tradeoff::create([
                'title' => $definition['title'],
                'slug' => $definition['slug'],
                'objective' => $definition['objective'],
                'constraint_value' => $definition['constraint'],
                'unit' => 'Md€',
                'direction' => 'at_least',
                'status' => TradeoffStatus::Draft,
                'source_url' => $definition['source_url'],
                'source_label' => $definition['source_label'],
                'theme_id' => $theme->id,
            ]);

            foreach ($definition['items'] as $item) {
                $proposal = Proposal::query()->published()->where('title', $item['title'])->first();

                if ($proposal === null) {
                    $this->command->warn("Mesure absente du jeu de démonstration : {$item['title']}");

                    continue;
                }

                $service->addItem($tradeoff, [
                    'proposal_id' => $proposal->id,
                    'impact' => $item['impact'],
                    'uncertainty' => $item['uncertainty'],
                    'source_url' => $item['source_url'],
                ]);
            }

            $service->changeStatus($tradeoff, TradeoffStatus::Open);
        }
    }
}
