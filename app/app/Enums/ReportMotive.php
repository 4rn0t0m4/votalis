<?php

namespace App\Enums;

/**
 * Liste fermée des motifs de la charte de modération (CDC section 6).
 * La gravité sert au tri de la file ; seul « contenu illégal » masque immédiatement.
 */
enum ReportMotive: string
{
    case Illegal = 'illegal';
    case PersonalAttack = 'personal_attack';
    case OffTopic = 'off_topic';
    case Duplicate = 'duplicate';
    case Spam = 'spam';
    case Disinformation = 'disinformation';
    case CoordinatedCampaign = 'coordinated_campaign';

    public function label(): string
    {
        return match ($this) {
            self::Illegal => 'Contenu illégal',
            self::PersonalAttack => 'Attaque personnelle',
            self::OffTopic => 'Hors sujet',
            self::Duplicate => 'Doublon',
            self::Spam => 'Spam',
            self::Disinformation => 'Désinformation manifeste',
            self::CoordinatedCampaign => 'Campagne coordonnée',
        };
    }

    /** Explication en langage simple, publiée dans la charte. */
    public function description(): string
    {
        return match ($this) {
            self::Illegal => 'Propos interdits par la loi : injure publique, diffamation, incitation à la haine ou à la violence, atteinte à la vie privée.',
            self::PersonalAttack => 'Le texte vise une personne plutôt qu’une idée : moquerie, insulte, procès d’intention.',
            self::OffTopic => 'Le contenu n’a pas de rapport avec le thème ou avec la proposition commentée.',
            self::Duplicate => 'La proposition reprend une mesure déjà présente sur la plateforme.',
            self::Spam => 'Contenu publicitaire, répété ou sans rapport avec le débat.',
            self::Disinformation => 'Affirmation factuelle manifestement fausse, présentée comme un fait établi, sans source.',
            self::CoordinatedCampaign => 'Contributions ou votes organisés par un groupe pour fausser le débat.',
        };
    }

    /** 3 : masquage immédiat ; 2 : traitement prioritaire ; 1 : traitement ordinaire. */
    public function severity(): int
    {
        return match ($this) {
            self::Illegal => 3,
            self::PersonalAttack, self::Disinformation, self::CoordinatedCampaign => 2,
            self::OffTopic, self::Duplicate, self::Spam => 1,
        };
    }

    /** Un contenu signalé pour ce motif est masqué dès le signalement, en attendant la décision. */
    public function hidesImmediately(): bool
    {
        return $this === self::Illegal;
    }

    /** Le journal public ne montre jamais le contenu masqué pour ce motif. */
    public function hidesContentInLog(): bool
    {
        return $this === self::Illegal;
    }
}
