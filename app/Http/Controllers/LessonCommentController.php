<?php

namespace App\Http\Controllers;

use App\Actions\DeleteComment;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonCommentController extends Controller
{
    /**
     * Post a comment, or a reply to a root comment, on the lesson.
     */
    public function store(StoreCommentRequest $request, Lesson $lesson): RedirectResponse
    {
        abort_unless($request->user()->can('view', $lesson), 404);

        $comment = new Comment(['body' => $request->string('body')->trim()->value()]);
        $comment->user()->associate($request->user());
        $comment->parent_id = $request->integer('parent_id') ?: null;
        $lesson->comments()->save($comment);

        return back();
    }

    /**
     * Edit the body of the user's own comment.
     */
    public function update(UpdateCommentRequest $request, Lesson $lesson, Comment $comment): RedirectResponse
    {
        $comment->body = $request->string('body')->trim()->value();

        if ($comment->isDirty('body')) {
            $comment->edited_at = now();
            $comment->save();
        }

        return back();
    }

    /**
     * Delete a comment: authors delete their own, admins any.
     */
    public function destroy(Request $request, Lesson $lesson, Comment $comment, DeleteComment $deleteComment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $deleteComment->handle($comment, $request->user());

        return back();
    }
}
