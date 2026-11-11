<?php

use App\Models\CommunitySpace;
use App\Models\CommunityTopic;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins see the spaces in order with their topic counts', function () {
    CommunityTopic::factory()->for(CommunitySpace::query()->where('slug', 'geral')->sole(), 'space')->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.community.spaces.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community/Spaces')
            ->where('spaces.1.slug', 'geral')
            ->where('spaces.1.topics_count', 1));
});

test('admins create a space, placed last with a slug from its name', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.community.spaces.store'), ['name' => 'Tupi Form', 'description' => 'Dúvidas do produto'])
        ->assertRedirect();

    expect(CommunitySpace::query()->latest('id')->first())
        ->slug->toBe('tupi-form')
        ->staff_only->toBeFalse()
        ->position->toBe(4);
});

test('admins rename a space and make it team-only', function () {
    $space = CommunitySpace::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.community.spaces.update', $space), ['name' => 'Novidades', 'slug' => 'novidades', 'staff_only' => true, 'position' => 0]);

    expect($space->refresh())->name->toBe('Novidades')->slug->toBe('novidades')->staff_only->toBeTrue()->position->toBe(0);
});

test('space slugs are unique', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.community.spaces.store'), ['name' => 'Outro', 'slug' => 'geral'])
        ->assertSessionHasErrors('slug');
});

test('only empty spaces can be deleted', function () {
    $admin = User::factory()->admin()->create();
    $space = CommunitySpace::factory()->create();
    $topic = CommunityTopic::factory()->for($space, 'space')->create();
    $topic->delete();

    $this->actingAs($admin)->delete(route('admin.community.spaces.destroy', $space))->assertSessionHasErrors('space');
    expect($space->fresh())->not->toBeNull();

    $empty = CommunitySpace::factory()->create();
    $this->actingAs($admin)->delete(route('admin.community.spaces.destroy', $empty))->assertSessionHasNoErrors();
    expect($empty->fresh())->toBeNull();
});
