<?php

use App\Models\CommunityMessage;
use App\Models\CommunityReport;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins see the open reports with the reported message', function () {
    $message = CommunityMessage::factory()->for(User::factory()->create(['name' => 'Spammer']))->create(['body' => 'Compre já!']);
    CommunityReport::factory()->for(User::factory()->create(['name' => 'Ana']))->create([
        'reportable_id' => $message->id, 'reason' => 'Propaganda',
    ]);
    CommunityReport::factory()->create(['resolved_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.community.reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community/Reports')
            ->has('reports.data', 1)
            ->where('reports.data.0.reporter', 'Ana')
            ->where('reports.data.0.reason', 'Propaganda')
            ->where('reports.data.0.content.id', $message->id)
            ->where('reports.data.0.content.body', 'Compre já!')
            ->where('reports.data.0.content.author', 'Spammer')
            ->where('reports.data.0.content.channel.slug', $message->space->slug));
});

test('admins dismiss a report and keep the message', function () {
    $admin = User::factory()->admin()->create();
    $report = CommunityReport::factory()->create();

    $this->actingAs($admin)->patch(route('admin.community.reports.update', $report))
        ->assertInertiaFlash('toast.message', 'Denúncia arquivada.');

    expect($report->refresh())->resolved_at->not->toBeNull()->resolved_by->toBe($admin->id)
        ->and(CommunityMessage::query()->find($report->reportable_id))->not->toBeNull();
});

test('removing a reported message closes every report about it', function () {
    $message = CommunityMessage::factory()->create();
    $reports = CommunityReport::factory()->count(2)->create(['reportable_id' => $message->id]);
    $unrelated = CommunityReport::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.community.reports.destroy', $reports[0]))
        ->assertInertiaFlash('toast.message', 'Mensagem removida.');

    expect($message->refresh()->trashed())->toBeTrue()
        ->and(CommunityReport::query()->whereNull('resolved_at')->pluck('id')->all())->toBe([$unrelated->id]);
});
