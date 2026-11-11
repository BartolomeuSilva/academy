<?php

namespace App\Notifications;

use App\Models\CommunityMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the author of a community message, in the bell and by e-mail unless turned off, that someone replied.
 */
class CommunityMessageReplied extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Skip the notification if the reply was deleted before it was sent.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new notification instance.
     */
    public function __construct(public CommunityMessage $reply) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $byEmail = $notifiable instanceof User && $notifiable->notify_by_email && $notifiable->hasVerifiedEmail();

        return $byEmail ? ['database', 'mail'] : ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject("{$data['actor']} respondeu você")
            ->greeting('Olá!')
            ->line("{$data['actor']} {$data['message']}:")
            ->line("“{$data['excerpt']}”")
            ->action('Ver resposta', $data['url'])
            ->line('Para não receber estes e-mails, desligue em Configurações › Notificações.')
            ->salutation('Equipe '.config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array{actor: string, message: string, excerpt: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $this->reply->loadMissing(['user:id,name', 'space:id,name,slug']);

        return [
            'actor' => $this->reply->user->name,
            'message' => "respondeu à sua mensagem em #{$this->reply->space->name}",
            'excerpt' => $this->reply->excerpt(120),
            'url' => route('community.channels.show', [$this->reply->space->slug, 'mensagem' => $this->reply->id]),
        ];
    }
}
