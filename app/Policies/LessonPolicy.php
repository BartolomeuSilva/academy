<?php

namespace App\Policies;

use App\Enums\PublicationStatus;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Video;

class LessonPolicy
{
    /**
     * Admins see every lesson; students see published lessons of published courses that have a video.
     */
    public function view(User $user, Lesson $lesson): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $lesson->status === PublicationStatus::Published
            && $lesson->hasPlayableVideo()
            && $lesson->course->status === PublicationStatus::Published;
    }

    /**
     * Admins may preview any encoded video; students only the current video of a lesson they can view.
     */
    public function watch(User $user, Lesson $lesson, Video $video): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $lesson->video_id === $video->id && $this->view($user, $lesson);
    }
}
