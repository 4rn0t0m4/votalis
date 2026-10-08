<?php

namespace Database\Seeders;

use App\Enums\TradeoffStatus;
use App\Models\Proposal;
use App\Models\Theme;
use App\Models\Tradeoff;
use App\Services\TradeoffService;
use Illuminate\Database\Seeder;

/** Développement uniquement : un arbitrage de démonstration sur le thème Économie. */
class DemoTradeoffSeeder extends Seeder
{
    public function run(TradeoffService $service): void
    {
        $theme = Theme::query()->where('slug', 'economie')->first();

        if ($theme === null || Tradeoff::query()->where('slug', 'trouver-40-milliards')->exists()) {
            return;
        }

        $tradeoff = Tradeoff::create([
            'title' => 'Trouver 40 milliards d’économies ou de recettes',
            'slug' => 'trouver-40-milliards',
            'objective' => 'Ramener le déficit public sous 3 % du PIB en trouvant 40 milliards d’euros par an d’économies ou de recettes nouvelles (jeu de démonstration, chiffrages fictifs).',
            'constraint_value' => 40,
            'unit' => 'Md€',
            'direction' => 'at_least',
            'status' => TradeoffStatus::Draft,
            'source_url' => 'https://www.exemple-sources.gouv.fr/trajectoire',
            'source_label' => 'Trajectoire de finances publiques (démonstration)',
            'theme_id' => $theme->id,
        ]);

        $proposals = Proposal::query()->published()->where('theme_id', $theme->id)->orderBy('id')->limit(8)->get();
        $impacts = [12, 8, 15, 6, 10, 20, 4, 9];

        foreach ($proposals as $i => $proposal) {
            $service->addItem($tradeoff, [
                'proposal_id' => $proposal->id,
                'impact' => $impacts[$i] ?? 5,
                'uncertainty' => '± 30 %, ordre de grandeur',
                'source_url' => 'https://www.exemple-sources.gouv.fr/chiffrage-'.$proposal->id,
            ]);
        }

        if ($proposals->count() >= 2) {
            $service->changeStatus($tradeoff, TradeoffStatus::Open);
        }
    }
}
