<?php

namespace App\Notifications;

use App\Models\Reunion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage ;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReunionUpdatedNotification extends Notification
{
    use Queueable;

    protected $reunion;
    protected $action; // 'updated', 'deleted', 'created'

    /**
     * Create a new notification instance.
     */
    public function __construct(Reunion $reunion, string $action = 'updated')
    {
        $this->reunion = $reunion;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
        //Determines which channels to send the notification through
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionText = $this->getActionText();
        
        return (new MailMessage)
                    ->subject("Réunion {$actionText} : " . $this->reunion->objet)
                    ->greeting('Bonjour / Bonsoire !'. $notifiable->name . ',')
                    ->line("Une réunion a été {$actionText}.")
                    ->line('Sujet : ' . $this->reunion->objet)
                    ->line('Date début : ' . $this->reunion->date_debut->format('d/m/Y H:i'))
                    ->line('Date fin : ' . $this->reunion->date_fin->format('d/m/Y H:i'))
                    ->line('**Lieu :** ' . ($this->reunion->lieu ?? 'Non spécifié'))
                    ->action('Voir le calendrier', url('/calendrier'))
                    ->action('Accepter l\'invitation', url('/reunion/respond/' . $this->reunion->id . '/accept'))
                    ->action('Refuser l\'invitation', url('/reunion/respond/' . $this->reunion->id . '/refuse'))
                    ->line('Merci !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
        //Alternative to toDatabase()Laravel will automatically use toArray() if toDatabase() is not defined
    public function toArray(object $notifiable): array
    {
        $actionText = $this->getActionText();
        
        return [
            'reunion_id' => $this->reunion->id,
            'objet' => $this->reunion->objet,
            'date_debut' => $this->reunion->date_debut->toDateTimeString(),
            'action' => $this->action,
            'message' => "Réunion {$actionText} : " . $this->reunion->objet
        ];
    }

    public function toBroadcast($notifiable)
    {
        $actionText = $this->getActionText();
        
        return new BroadcastMessage([
            'reunion_id' => $this->reunion->id,
            'message' => "Réunion {$actionText} : " . $this->reunion->objet,
            'action' => $this->action,
            'statut' => 'en_attente'
        ]);
    }
    /**
     * Get human readable action text
     */
    protected function getActionText(): string
    {
        return match($this->action) {
            'created' => 'crée',
            'deleted' => 'annulée',
            'updated' => 'modifiée'
        };
    }
}
