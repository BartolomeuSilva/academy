<?php

use App\Actions\BuildAchievements;
use App\Models\CommunityMessage;
use App\Models\CommunityReaction;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonRating;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
});

/**
 * @return array<string, array{progress: int, earned: bool}>
 */
function achievementsOf(User $user): array
{
    return collect(app(BuildAchievements::class)->handle($user))
        ->flatMap(fn (array $group) => $group['achievements'])
        ->mapWithKeys(fn (array $achievement) => [$achievement['key'] => ['progress' => $achievement['progress'], 'earned' => $achievement['earned']]])
        ->all();
}

test('lessons and courses count only what the student completed in the catalog', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $module = Module::factory()->for($course)->create();
    $lessons = collect(range(1, 2))->map(fn () => visibleLesson($module));
    $lessons->each(fn (Lesson $lesson) => LessonProgress::factory()->completed()->for($student)->for($lesson)->create());
    LessonProgress::factory()->completed()->for($student)->for(Lesson::factory()->for($module)->withVideo())->create();
    LessonProgress::factory()->for($student)->for(visibleLesson(Module::factory()->for(Course::factory()->published())->create()))->create();

    $achievements = achievementsOf($student);

    expect($achievements['lessons-1'])->toBe(['progress' => 1, 'earned' => true])
        ->and($achievements['lessons-5'])->toBe(['progress' => 2, 'earned' => false])
        ->and($achievements['courses-1'])->toBe(['progress' => 1, 'earned' => true]);
});

test('ratings, messages and reactions from others count toward their milestones', function () {
    $student = User::factory()->create();
    LessonRating::factory()->count(5)->for($student)->create();
    $message = CommunityMessage::factory()->for($student)->create();
    CommunityMessage::factory()->create();
    foreach ([User::factory()->create(), $student] as $user) {
        $reaction = new CommunityReaction;
        $reaction->emoji = '👍';
        $reaction->user()->associate($user);
        $message->reactions()->save($reaction);
    }

    $achievements = achievementsOf($student);

    expect($achievements['ratings-5']['earned'])->toBeTrue()
        ->and($achievements['ratings-10'])->toBe(['progress' => 5, 'earned' => false])
        ->and($achievements['messages-1']['earned'])->toBeTrue()
        ->and($achievements['messages-10']['progress'])->toBe(1)
        ->and($achievements['reactions-1'])->toBe(['progress' => 1, 'earned' => true])
        ->and($achievements['reactions-10']['progress'])->toBe(1);
});

test('the achievements page shows how many were earned', function () {
    $student = User::factory()->create();
    LessonRating::factory()->for($student)->create();

    $this->actingAs($student)
        ->get(route('achievements.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('achievements/Index')
            ->where('earned', 1)
            ->where('total', 17)
            ->where('groups.2.title', 'Aulas avaliadas')
            ->where('groups.2.achievements.0.title', '1 aula avaliada'));
});
