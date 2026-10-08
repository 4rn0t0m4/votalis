<?php

namespace App\Enums;

/**
 * Jalons du parcours personnel (lot 6). Liste fermée, règles calculées côté serveur par
 * `App\Services\Journey`. Aucun jalon ne récompense un volume : chacun marque une pratique
 * du débat (lire les deux camps, réviser, nuancer, arbitrer, sourcer). Jamais affichés publiquement.
 */
enum Milestone: string
{
    case FirstVoice = 'premiere_voix';
    case FullReading = 'lecture_complete';
    case TwoViewpoints = 'deux_points_de_vue';
    case OpenMind = 'esprit_ouvert';
    case Nuance = 'oui_a_condition';
    case Arbiter = 'premier_arbitrage';
    case Exploration = 'themes_explores';
    case Sourced = 'argument_source';
    case Proposer = 'premiere_proposition';

    public function label(): string
    {
        return match ($this) {
            self::FirstVoice => 'Première voix',
            self::FullReading => 'Lecture complète',
            self::TwoViewpoints => 'Deux points de vue',
            self::OpenMind => 'Esprit ouvert',
            self::Nuance => 'Nuance',
            self::Arbiter => 'Arbitre',
            self::Exploration => 'Exploration',
            self::Sourced => 'Sourcé',
            self::Proposer => 'Proposant',
        };
    }

    /** Règle, écrite en clair sous chaque jalon. */
    public function rule(): string
    {
        return match ($this) {
            self::FirstVoice => 'Donner un premier avis sur une proposition.',
            self::FullReading => 'Voter après avoir déplié les arguments pour et contre.',
            self::TwoViewpoints => 'Juger utile au moins un argument pour et un argument contre.',
            self::OpenMind => 'Réviser un vote après lecture des arguments, quel qu’en soit le sens.',
            self::Nuance => 'Déposer un « oui, à condition que… ».',
            self::Arbiter => 'Valider une combinaison dans un arbitrage sous contrainte.',
            self::Exploration => 'Voter dans '.config('votalis.journey.exploration_themes', 5).' thèmes différents.',
            self::Sourced => 'Publier un argument accompagné d’une source.',
            self::Proposer => 'Publier une proposition.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FirstVoice => 'hand',
            self::FullReading => 'book',
            self::TwoViewpoints => 'eye',
            self::OpenMind => 'refresh',
            self::Nuance => 'sparkle',
            self::Arbiter => 'scale',
            self::Exploration => 'compass',
            self::Sourced => 'link',
            self::Proposer => 'pen',
        };
    }
}
