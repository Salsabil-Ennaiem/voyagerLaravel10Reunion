<?php

namespace App\Notifications;

use App\Models\Reunion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReunionInvitationNotification extends Notification
{
    use Queueable;

    protected $reunion;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reunion $reunion)
    {
        $this->reunion = $reunion;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Invitation à une nouvelle réunion : ' . $this->reunion->objet)
                    ->greeting('Bonjour !')
                    ->line('Vous avez été invité à une nouvelle réunion.')
                    ->line('**Objet :** ' . $this->reunion->objet)
                    ->line('**Date :** ' . $this->reunion->date_debut->format('d/m/Y H:i'))
                    ->line('**Lieu :** ' . ($this->reunion->lieu ?? 'Non spécifié'))
                    ->action('Voir le calendrier', url('/calendrier'))
                    ->line('Merci de confirmer votre présence.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reunion_id' => $this->reunion->id,
            'objet' => $this->reunion->objet,
            'date_debut' => $this->reunion->date_debut->toIso8601String(),
            'message' => 'Nouvelle invitation pour : ' . $this->reunion->objet
        ];
    }
}
