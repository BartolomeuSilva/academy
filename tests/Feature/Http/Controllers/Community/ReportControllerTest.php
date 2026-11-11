<?php

use App\Models\CommunityReply;
use App\Models\CommunityReport;
use App\Models\CommunityTopic;
use App\Models\User;

test('a student reports a topic or a reply for the team', function () {
    $student = User::factory()->create();
    $topic = CommunityTopic::factory()->create();
    $reply = CommunityReply::factory()->for($topic, 'topic')->create();

    $this->actingAs($student)
        ->post(route('community.topics.report', $topic), ['reason' => 'Propaganda'])
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Obrigado! A equipe vai analisar.');
    $this->actingAs($student)->post(route('community.topics.replies.report', [$topic, $reply]));

    expect(CommunityReport::query()->pluck('reportable_type')->sort()->values()->all())->toBe(['community_reply', 'community_topic'])
        ->and(CommunityReport::query()->where('reportable_type', 'community_topic')->sole()->reason)->toBe('Propaganda');
});

test('reporting the same content again reopens the earlier report', function () {
    $student = User::factory()->create();
    $topic = CommunityTopic::factory()->create();
    CommunityReport::factory()->for($student)->create(['reportable_id' => $topic->id, 'resolved_at' => now()]);

    $this->actingAs($student)->post(route('community.topics.report', $topic), ['reason' => 'De novo']);

    expect(CommunityReport::query()->sole())->reason->toBe('De novo')->resolved_at->toBeNull();
});

test('nobody reports their own content', function () {
    $topic = CommunityTopic::factory()->create();

    $this->actingAs($topic->user)->post(route('community.topics.report', $topic))->assertForbidden();

    expect(CommunityReport::query()->count())->toBe(0);
});
