<?php

namespace App\Http\Controllers;

use App\Models\CommunityMessage;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Results shown per group.
     */
    public const int LIMIT = 5;

    /**
     * Search courses, lessons, materials and community messages the student can open.
     */
    public function index(Request $request): JsonResponse
    {
        $term = $request->string('q')->squish()->limit(100, '')->toString();

        if (mb_strlen($term) < 2) {
            return response()->json(['courses' => [], 'lessons' => [], 'materials' => [], 'community' => []]);
        }

        $like = "%{$term}%";
        $visibleLessons = $this->visibleLessons(...);

        return response()->json([
            'courses' => Course::query()
                ->visible()
                ->where(fn (Builder $query) => $query->whereLike('title', $like)->orWhereLike('summary', $like)->orWhereLike('category', $like))
                ->orderBy('position')
                ->limit(self::LIMIT)
                ->get(['id', 'slug', 'title', 'summary'])
                ->map(fn (Course $course) => [
                    'title' => $course->title,
                    'subtitle' => $course->summary,
                    'url' => route('courses.show', $course->slug),
                ]),
            'lessons' => Lesson::query()
                ->where($visibleLessons)
                ->whereLike('title', $like)
                ->with('course:id,slug,title')
                ->limit(self::LIMIT)
                ->get(['id', 'course_id', 'slug', 'title'])
                ->map(fn (Lesson $lesson) => [
                    'title' => $lesson->title,
                    'subtitle' => $lesson->course->title,
                    'url' => route('lessons.show', [$lesson->course->slug, $lesson->slug]),
                ]),
            'materials' => LessonAttachment::query()
                ->whereHas('lesson', $visibleLessons)
                ->whereLike('original_name', $like)
                ->with(['lesson:id,course_id,slug,title', 'lesson.course:id,slug'])
                ->limit(self::LIMIT)
                ->get(['id', 'lesson_id', 'original_name'])
                ->map(fn (LessonAttachment $attachment) => [
                    'title' => $attachment->original_name,
                    'subtitle' => "Aula: {$attachment->lesson->title}",
                    'url' => route('attachments.show', $attachment),
                ]),
            'community' => CommunityMessage::query()
                ->whereLike('body', $like)
                ->with('space:id,name,slug')
                ->latest('id')
                ->limit(self::LIMIT)
                ->get(['id', 'space_id', 'body'])
                ->map(fn (CommunityMessage $message) => [
                    'title' => $message->excerpt(80),
                    'subtitle' => "Comunidade · #{$message->space->name}",
                    'url' => route('community.channels.show', [$message->space->slug, 'mensagem' => $message->id]),
                ]),
        ]);
    }

    /**
     * Limit to lessons students can open in courses of the catalog.
     *
     * @param  Builder<Lesson>  $lessons
     * @return Builder<Lesson>
     */
    private function visibleLessons(Builder $lessons): Builder
    {
        return $lessons->visible()->whereHas('course', fn (Builder $courses) => $courses->visible());
    }
}
