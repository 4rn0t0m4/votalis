<?php

namespace App\Enums;

/**
 * Pictogrammes de thème : liste fermée de tracés neutres (aucun symbole politique),
 * choisis par le comité éditorial. La valeur est le nom du pictogramme (`App\View\Components\Icon`).
 */
enum ThemeIcon: string
{
    case Coins = 'coins';
    case Briefcase = 'briefcase';
    case Pulse = 'pulse';
    case Graduation = 'graduation';
    case Shield = 'shield';
    case Home = 'home';
    case Leaf = 'leaf';
    case Globe = 'globe';
    case Gavel = 'gavel';
    case Bus = 'bus';
    case Cpu = 'cpu';
    case Landmark = 'landmark';
    case Map = 'map';
    case Wheat = 'wheat';
    case Book = 'book';
    case Bolt = 'bolt';
    case Drop = 'drop';

    public function label(): string
    {
        return match ($this) {
            self::Coins => 'Pièces (économie, budget)',
            self::Briefcase => 'Mallette (emploi, travail)',
            self::Pulse => 'Pouls (santé)',
            self::Graduation => 'Diplôme (éducation)',
            self::Shield => 'Bouclier (défense, sécurité)',
            self::Home => 'Maison (logement)',
            self::Leaf => 'Feuille (environnement)',
            self::Globe => 'Globe (Europe, international)',
            self::Gavel => 'Marteau (justice)',
            self::Bus => 'Bus (transports)',
            self::Cpu => 'Puce (numérique)',
            self::Landmark => 'Monument (institutions)',
            self::Map => 'Carte (territoires)',
            self::Wheat => 'Épi (agriculture, alimentation)',
            self::Book => 'Livre (culture)',
            self::Bolt => 'Éclair (énergie)',
            self::Drop => 'Goutte (eau, ressources)',
        };
    }
}
