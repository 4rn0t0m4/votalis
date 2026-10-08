<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Préavis unique avant la suppression d'un compte inactif (CDC section 8). */
class InactivityNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $daysLeft) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $months = (int) config('votalis.retention.inactive_months', 36);

        return (new MailMessage)
            ->subject('Votre compte sera supprimé dans '.$this->daysLeft.' jours')
            ->greeting('Bonjour,')
            ->line("Vous ne vous êtes pas connecté depuis près de {$months} mois. Conformément à notre politique de conservation, votre compte et vos votes seront supprimés dans {$this->daysLeft} jours.")
            ->line('Vos propositions et arguments publiés resteront en ligne, rattachés à « participant supprimé ».')
            ->line('Pour conserver votre compte, il suffit de vous connecter une fois.')
            ->action('Me connecter', route('login'))
            ->salutation('L’équipe de la plateforme');
    }
}
