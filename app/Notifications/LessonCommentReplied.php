<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells the author of a lesson comment, in the bell and by e-mail unless turned off, that someone replied.
 */
class LessonCommentReplied extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Skip the notification if the reply was deleted before it was sent.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Comment $reply) {}

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
        $this->reply->loadMissing(['user:id,name', 'lesson:id,course_id,slug,title', 'lesson.course:id,slug']);
        $lesson = $this->reply->lesson;

        return [
            'actor' => $this->reply->user->name,
            'message' => "respondeu ao seu comentário na aula “{$lesson->title}”",
            'excerpt' => Str::limit($this->reply->body, 120),
            'url' => route('lessons.show', [$lesson->course->slug, $lesson->slug, 'aba' => 'comentarios']),
        ];
    }
}
