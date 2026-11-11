<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityReply;
use App\Models\CommunityReport;
use App\Models\CommunityTopic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CommunityReportController extends Controller
{
    /**
     * List the open reports, oldest first, with the reported content.
     */
    public function index(): Response
    {
        $reports = CommunityReport::query()
            ->whereNull('resolved_at')
            ->with(['user:id,name', 'reportable.user:id,name'])
            ->oldest('id')
            ->paginate(20)
            ->through(function (CommunityReport $report) {
                $content = $report->reportable;
                $topic = $content instanceof CommunityReply ? $content->topic()->withTrashed()->first() : $content;

                return [
                    'id' => $report->id,
                    'reason' => $report->reason,
                    'reporter' => $report->user->name,
                    'created_at' => $report->created_at?->toIso8601String(),
                    'content' => $content ? [
                        'kind' => $content instanceof CommunityTopic ? 'topic' : 'reply',
                        'body' => $content->body,
                        'author' => $content->user->name,
                        'deleted' => $content->trashed(),
                        'topic' => $topic ? ['id' => $topic->id, 'title' => $topic->title] : null,
                    ] : null,
                ];
            });

        return Inertia::render('admin/community/Reports', [
            'reports' => $reports,
        ]);
    }

    /**
     * Close a report without removing the content.
     */
    public function update(Request $request, CommunityReport $report): RedirectResponse
    {
        $this->resolve(CommunityReport::query()->whereKey($report->id), $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Denúncia arquivada.']);

        return back();
    }

    /**
     * Remove the reported content and close every open report about it.
     */
    public function destroy(Request $request, CommunityReport $report): RedirectResponse
    {
        DB::transaction(function () use ($request, $report) {
            $content = $report->reportable;

            if ($content instanceof CommunityReply) {
                CommunityTopic::withTrashed()
                    ->whereKey($content->topic_id)
                    ->where('solution_reply_id', $content->id)
                    ->update(['solution_reply_id' => null]);
            }

            $content?->delete();

            $this->resolve(CommunityReport::query()
                ->where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id), $request);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conteúdo removido.']);

        return back();
    }

    /**
     * @param  Builder<CommunityReport>  $reports
     */
    private function resolve(Builder $reports, Request $request): void
    {
        $reports->whereNull('resolved_at')->update(['resolved_at' => now(), 'resolved_by' => $request->user()->id]);
    }
}
