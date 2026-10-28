<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Admins preview every course; students see the courses listed in the catalog.
     */
    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return Course::query()->visible()->whereKey($course->id)->exists();
    }
}
