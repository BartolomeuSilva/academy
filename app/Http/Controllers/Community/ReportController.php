<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreReportRequest;
use App\Models\CommunityMessage;
use App\Models\CommunityReport;
use App\Models\CommunitySpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Report a message for the team to review, reopening the user's earlier report about it.
     */
    public function store(StoreReportRequest $request, CommunitySpace $space, CommunityMessage $message): RedirectResponse
    {
        Gate::authorize('report', $message);

        $report = CommunityReport::query()
            ->whereBelongsTo($request->user())
            ->whereMorphedTo('reportable', $message)
            ->first() ?? new CommunityReport;
        $report->user()->associate($request->user());
        $report->reportable()->associate($message);
        $report->reason = $request->string('reason')->trim()->value() ?: null;
        $report->resolved_at = null;
        $report->resolved_by = null;
        $report->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Obrigado! A equipe vai analisar.']);

        return back();
    }
}
