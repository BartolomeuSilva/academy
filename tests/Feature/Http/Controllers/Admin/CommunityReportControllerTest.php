<?php

use App\Models\CommunityReply;
use App\Models\CommunityReport;
use App\Models\CommunityTopic;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins see the open reports with the reported content', function () {
    $reply = CommunityReply::factory()->for(User::factory()->create(['name' => 'Spammer']))->create(['body' => 'Compre já!']);
    CommunityReport::factory()->for(User::factory()->create(['name' => 'Ana']))->create([
        'reportable_type' => 'community_reply', 'reportable_id' => $reply->id, 'reason' => 'Propaganda',
    ]);
    CommunityReport::factory()->create(['resolved_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.community.reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community/Reports')
            ->has('reports.data', 1)
            ->where('reports.data.0.reporter', 'Ana')
            ->where('reports.data.0.reason', 'Propaganda')
            ->where('reports.data.0.content.kind', 'reply')
            ->where('reports.data.0.content.body', 'Compre já!')
            ->where('reports.data.0.content.author', 'Spammer')
            ->where('reports.data.0.content.topic.id', $reply->topic_id));
});

test('admins dismiss a report and keep the content', function () {
    $admin = User::factory()->admin()->create();
    $report = CommunityReport::factory()->create();

    $this->actingAs($admin)->patch(route('admin.community.reports.update', $report))
        ->assertInertiaFlash('toast.message', 'Denúncia arquivada.');

    expect($report->refresh())->resolved_at->not->toBeNull()->resolved_by->toBe($admin->id)
        ->and(CommunityTopic::query()->find($report->reportable_id))->not->toBeNull();
});

test('removing reported content closes every report about it and clears a solution', function () {
    $topic = CommunityTopic::factory()->create();
    $reply = CommunityReply::factory()->for($topic, 'topic')->create();
    $topic->forceFill(['solution_reply_id' => $reply->id])->save();
    $reports = CommunityReport::factory()->count(2)->create(['reportable_type' => 'community_reply', 'reportable_id' => $reply->id]);
    $unrelated = CommunityReport::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.community.reports.destroy', $reports[0]))
        ->assertInertiaFlash('toast.message', 'Conteúdo removido.');

    expect($reply->refresh()->trashed())->toBeTrue()
        ->and($topic->refresh()->solution_reply_id)->toBeNull()
        ->and(CommunityReport::query()->whereNull('resolved_at')->pluck('id')->all())->toBe([$unrelated->id]);
});
