<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avis minimal envoyé à l'auteur : ni contenu, ni motif, ni décision dans l'e-mail (données
 * minimisées). Le détail se lit sur le compte, où la contestation est possible.
 */
class ModerationNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public const DECISION = 'decision';

    public const APPEAL_DECIDED = 'appeal_decided';

    public const ACCOUNT = 'account';

    public function __construct(public readonly string $kind) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = (int) config('votalis.moderation.appeal_days', 14);

        $line = match ($this->kind) {
            self::APPEAL_DECIDED => 'La contestation que vous avez déposée a été tranchée par le comité éditorial.',
            self::ACCOUNT => 'Une décision de modération concerne votre compte.',
            default => 'Une décision de modération concerne l’une de vos contributions.',
        };

        return (new MailMessage)
            ->subject('Une décision de modération vous concerne')
            ->greeting('Bonjour,')
            ->line($line)
            ->line("Connectez-vous pour en lire le détail. Une décision peut être contestée une fois, dans un délai de {$days} jours ; le comité éditorial tranche.")
            ->action('Consulter mon compte', route('account.moderation.index'))
            ->salutation('L’équipe de modération');
    }
}
