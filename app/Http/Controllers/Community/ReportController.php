<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreReportRequest;
use App\Models\CommunityReply;
use App\Models\CommunityReport;
use App\Models\CommunityTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Report a topic for the team to review.
     */
    public function topic(StoreReportRequest $request, CommunityTopic $topic): RedirectResponse
    {
        return $this->report($request, $topic);
    }

    /**
     * Report a reply for the team to review.
     */
    public function reply(StoreReportRequest $request, CommunityTopic $topic, CommunityReply $reply): RedirectResponse
    {
        return $this->report($request, $reply);
    }

    /**
     * Open a report, or reopen the user's earlier one about the same content.
     */
    private function report(StoreReportRequest $request, CommunityTopic|CommunityReply $reportable): RedirectResponse
    {
        Gate::authorize('report', $reportable);

        $report = CommunityReport::query()
            ->whereBelongsTo($request->user())
            ->whereMorphedTo('reportable', $reportable)
            ->first() ?? new CommunityReport;
        $report->user()->associate($request->user());
        $report->reportable()->associate($reportable);
        $report->reason = $request->string('reason')->trim()->value() ?: null;
        $report->resolved_at = null;
        $report->resolved_by = null;
        $report->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Obrigado! A equipe vai analisar.']);

        return back();
    }
}
