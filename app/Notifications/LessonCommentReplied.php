<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells the author of a lesson comment, in the bell, that someone replied.
 */
class LessonCommentReplied extends Notification
{
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
        return ['database'];
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
