<?php

namespace Database\Seeders;

use App\Enums\TradeoffStatus;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\Tradeoff;
use App\Services\TradeoffService;
use Illuminate\Database\Seeder;

/**
 * Développement uniquement : un arbitrage de démonstration sur le thème Économie, bâti sur les
 * mesures du jeu d'amorçage de démonstration. Les impacts sont des ordres de grandeur tirés des
 * rapports cités ; l'hypothèse de calcul est écrite dans le champ « incertitude ».
 */
class DemoTradeoffSeeder extends Seeder
{
    private const CC = 'https://www.ccomptes.fr';

    /** @var list<array{title: string, impact: float|int, uncertainty: string, source_url: string}> */
    private const ITEMS = [
        ['title' => 'Supprimer les taux réduits de TVA dont l’inefficacité est établie, ou les relever', 'impact' => 12, 'uncertainty' => 'Très élevée : hypothèse d’un quart des 47 Md€ de taux réduits supprimés ou relevés (CPO, 2023)', 'source_url' => self::CC.'/fr/publications/la-tva-une-taxe-recentrer-sur-son-objectif-de-rendement-pour-les-finances-publiques'],
        ['title' => 'Réformer les niches des droits de succession : assurance-vie, démembrement, pacte Dutreil', 'impact' => 3, 'uncertainty' => 'Élevée : coût du seul pacte Dutreil estimé entre 2 et 3 Md€ par an (CAE, 2021), assurance-vie non chiffrée', 'source_url' => 'https://cae-eco.fr/repenser-lheritage'],
        ['title' => 'Recalibrer les allègements généraux de cotisations patronales pour redresser les comptes sociaux', 'impact' => 4, 'uncertainty' => 'Élevée : hypothèse d’un recalibrage de 5 % des 77,3 Md€ d’allègements (Cour des comptes, 2025)', 'source_url' => self::CC.'/fr/publications/securite-sociale-2025'],
        ['title' => 'Adopter un programme pluriannuel de maîtrise des dépenses d’assurance maladie fondé sur la prévention', 'impact' => 4, 'uncertainty' => 'Moyenne : 4 Md€ par an d’économies jugées nécessaires d’ici 2029 (Cour des comptes, avril 2025)', 'source_url' => self::CC.'/sites/default/files/2025-04/20250414-Lobjectif-national-de-depenses-dassurance-maladie-Ondam.pdf'],
        ['title' => 'Recentrer l’aide à l’embauche d’apprentis sur les jeunes les moins qualifiés', 'impact' => 2, 'uncertainty' => 'Élevée : hypothèse d’une réduction de moitié des 4,4 Md€ d’aides versées en 2022 (Cour des comptes, 2023)', 'source_url' => self::CC.'/fr/documents/65357'],
        ['title' => 'Instaurer une participation du titulaire aux formations financées par le compte personnel de formation', 'impact' => 0.25, 'uncertainty' => 'Moyenne : 10 % des 2,5 Md€ de dépenses annuelles du CPF (Cour des comptes, 2023)', 'source_url' => self::CC.'/fr/documents/65357'],
        ['title' => 'Relever le barème de la taxe sur les boissons sucrées et l’étendre aux sirops et boissons végétales', 'impact' => 0.5, 'uncertainty' => 'Élevée : hypothèse d’un doublement du rendement actuel de 0,5 Md€ (CPO, 2023)', 'source_url' => self::CC.'/fr/publications/la-fiscalite-nutritionnelle'],
        ['title' => 'Aligner les niveaux de prise en charge des contrats d’apprentissage sur leurs coûts réels', 'impact' => 0.6, 'uncertainty' => 'Moyenne : 210 M€ par tranche de 5 % de baisse, hypothèse de trois tranches (Cour des comptes, 2023)', 'source_url' => self::CC.'/fr/documents/65357'],
    ];

    public function run(TradeoffService $service): void
    {
        $theme = Theme::query()->where('slug', 'economie')->first();

        if ($theme === null || Tradeoff::query()->where('slug', 'trouver-20-milliards')->exists()) {
            return;
        }

        $tradeoff = Tradeoff::create([
            'title' => 'Trouver 20 milliards d’euros par an d’économies ou de recettes',
            'slug' => 'trouver-20-milliards',
            'objective' => 'La Cour des comptes évalue à près de 105 milliards d’euros l’effort nécessaire d’ici 2029 pour ramener le déficit public sous 3 % du PIB. Composez une combinaison de mesures qui en couvre une première marche de 20 milliards d’euros par an. Les chiffrages sont des ordres de grandeur tirés des rapports cités, avec leur hypothèse de calcul.',
            'constraint_value' => 20,
            'unit' => 'Md€',
            'direction' => 'at_least',
            'status' => TradeoffStatus::Draft,
            'source_url' => self::CC.'/fr/publications/la-situation-et-les-perspectives-des-finances-publiques-16',
            'source_label' => 'Cour des comptes, La situation et les perspectives des finances publiques, juillet 2025',
            'theme_id' => $theme->id,
        ]);

        foreach (self::ITEMS as $item) {
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
