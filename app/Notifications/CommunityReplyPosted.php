<?php

namespace App\Notifications;

use App\Models\CommunityReply;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells a topic's author, in the bell, that someone replied.
 */
class CommunityReplyPosted extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public CommunityReply $reply) {}

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
        $this->reply->loadMissing(['user:id,name', 'topic:id,title']);

        return [
            'actor' => $this->reply->user->name,
            'message' => "respondeu ao seu tópico “{$this->reply->topic->title}”",
            'excerpt' => Str::limit(Str::squish(strip_tags($this->reply->bodyHtml())), 120),
            'url' => route('community.topics.show', $this->reply->topic_id).'#resposta-'.$this->reply->id,
        ];
    }
}
