<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CommunityReportController extends Controller
{
    /**
     * List the open reports, oldest first, with the reported message.
     */
    public function index(): Response
    {
        $reports = CommunityReport::query()
            ->whereNull('resolved_at')
            ->with(['user:id,name', 'reportable.user:id,name', 'reportable.space:id,name,slug'])
            ->oldest('id')
            ->paginate(20)
            ->through(function (CommunityReport $report) {
                $content = $report->reportable;
                $channel = $content?->space;

                return [
                    'id' => $report->id,
                    'reason' => $report->reason,
                    'reporter' => $report->user->name,
                    'created_at' => $report->created_at?->toIso8601String(),
                    'content' => $content ? [
                        'id' => $content->id,
                        'body' => $content->body,
                        'author' => $content->user->name,
                        'deleted' => $content->trashed(),
                        'channel' => $channel ? ['name' => $channel->name, 'slug' => $channel->slug] : null,
                    ] : null,
                ];
            });

        return Inertia::render('admin/community/Reports', [
            'reports' => $reports,
        ]);
    }

    /**
     * Close a report without removing the message.
     */
    public function update(Request $request, CommunityReport $report): RedirectResponse
    {
        $this->resolve(CommunityReport::query()->whereKey($report->id), $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Denúncia arquivada.']);

        return back();
    }

    /**
     * Remove the reported message and close every open report about it.
     */
    public function destroy(Request $request, CommunityReport $report): RedirectResponse
    {
        DB::transaction(function () use ($request, $report) {
            $report->reportable?->delete();

            $this->resolve(CommunityReport::query()
                ->where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id), $request);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mensagem removida.']);

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
