<?php

namespace App\Enums;

/**
 * Actions consignées dans le journal public de modération. Pas de suppression (CDC section 6).
 */
enum ModerationAction: string
{
    /** Masquage automatique à la réception d'un signalement « contenu illégal ». */
    case AutoHide = 'auto_hide';
    case Keep = 'keep';
    case Hide = 'hide';
    case RequestRewrite = 'request_rewrite';
    case RewriteReceived = 'rewrite_received';
    case Suspend = 'suspend';
    case AppealConfirmed = 'appeal_confirmed';
    case AppealOverturned = 'appeal_overturned';

    public function label(): string
    {
        return match ($this) {
            self::AutoHide => 'Masqué en attente de décision',
            self::Keep => 'Conservé',
            self::Hide => 'Masqué',
            self::RequestRewrite => 'Reformulation demandée',
            self::RewriteReceived => 'Reformulation reçue',
            self::Suspend => 'Compte suspendu',
            self::AppealConfirmed => 'Contestation rejetée',
            self::AppealOverturned => 'Contestation acceptée',
        };
    }

    /** Décisions que l'auteur peut contester une fois (CDC section 6). */
    public function isAppealable(): bool
    {
        return in_array($this, [self::Hide, self::RequestRewrite, self::Suspend], true);
    }
}
