<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteComment;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    /**
     * List the most recent comments, optionally from one course.
     */
    public function index(Request $request): Response
    {
        $courseId = $request->integer('curso') ?: null;

        $comments = Comment::query()
            ->with(['user:id,name', 'lesson:id,course_id,slug,title', 'lesson.course:id,slug,title'])
            ->when($courseId, fn (Builder $query, int $courseId) => $query->whereHas('lesson', fn (Builder $lessons) => $lessons->where('course_id', $courseId)))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Comment $comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author' => $comment->user->name,
                'is_reply' => ! $comment->isRoot(),
                'edited' => $comment->edited_at !== null,
                'created_at' => $comment->created_at?->toIso8601String(),
                'lesson' => [
                    'slug' => $comment->lesson->slug,
                    'title' => $comment->lesson->title,
                ],
                'course' => [
                    'slug' => $comment->lesson->course->slug,
                    'title' => $comment->lesson->course->title,
                ],
            ]);

        return Inertia::render('admin/comments/Index', [
            'comments' => $comments,
            'courses' => Course::query()->orderBy('position')->get(['id', 'title']),
            'filters' => ['course_id' => $courseId],
        ]);
    }

    /**
     * Remove a comment; a root comment takes its replies with it.
     */
    public function destroy(Request $request, Comment $comment, DeleteComment $deleteComment): RedirectResponse
    {
        $deleteComment->handle($comment, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comentário excluído.']);

        return back();
    }
}
