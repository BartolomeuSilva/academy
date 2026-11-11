<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LessonDiscussionController extends Controller
{
    public const int PER_PAGE = 20;

    /**
     * List the latest questions and comments from every lesson in the catalog.
     */
    public function index(Request $request): Response
    {
        $unanswered = $request->query('filtro') === 'sem-resposta';

        $discussions = Comment::query()
            ->whereNull('parent_id')
            ->whereHas('lesson', fn (Builder $lessons) => $lessons
                ->visible()
                ->whereHas('course', fn (Builder $courses) => $courses->visible()))
            ->when($unanswered, fn (Builder $query) => $query->doesntHave('replies'))
            ->with(['user:id,name,role', 'lesson:id,course_id,slug,title', 'lesson.course:id,slug,title'])
            ->withCount('replies')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Comment $comment) => [
                'id' => $comment->id,
                'excerpt' => Str::limit($comment->body, 200),
                'author' => ['name' => $comment->user->name, 'is_team' => $comment->user->isAdmin()],
                'replies_count' => (int) $comment->replies_count,
                'created_at' => $comment->created_at?->toIso8601String(),
                'course' => ['slug' => $comment->lesson->course->slug, 'title' => $comment->lesson->course->title],
                'lesson' => ['slug' => $comment->lesson->slug, 'title' => $comment->lesson->title],
            ]);

        return Inertia::render('community/Lessons', [
            'discussions' => $discussions,
            'filters' => ['filter' => $unanswered ? 'sem-resposta' : 'recentes'],
        ]);
    }
}
