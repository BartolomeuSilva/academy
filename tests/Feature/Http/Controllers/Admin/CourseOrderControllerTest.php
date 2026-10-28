<?php

use App\Models\Course;
use App\Models\User;

test('saves the catalog order', function () {
    [$first, $second, $third] = Course::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.courses.order'), ['ids' => [$third->id, $first->id, $second->id]])
        ->assertRedirect();

    expect([$third->fresh()->position, $first->fresh()->position, $second->fresh()->position])->toBe([0, 1, 2]);
});

test('rejects a list that does not contain every course', function () {
    [$first] = Course::factory()->count(2)->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.courses.order'), ['ids' => [$first->id]]);

    $response->assertSessionHasErrors(['ids' => 'A lista enviada não corresponde aos itens atuais. Recarregue a página e tente novamente.']);
});
