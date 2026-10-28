<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;

/**
 * A ready video with stored playlists, attached to a lesson with the given publication state.
 */
function streamableVideo(bool $coursePublished = true, bool $lessonPublished = true): Video
{
    $course = $coursePublished ? Course::factory()->published()->create() : Course::factory()->create();
    $lesson = Lesson::factory()->for(Module::factory()->for($course))->create(['status' => $lessonPublished ? 'published' : 'draft']);
    $video = Video::factory()->ready()->for($lesson)->create();
    $lesson->video()->associate($video)->save();

    Storage::put("{$video->hlsDirectory()}/master.m3u8", "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=928000\n480p.m3u8\n#EXT-X-STREAM-INF:BANDWIDTH=2128000\n720p.m3u8\n");
    Storage::put("{$video->hlsDirectory()}/480p.m3u8", "#EXTM3U\n#EXTINF:6.0,\n480p_0000.ts\n#EXT-X-ENDLIST\n");

    return $video;
}

beforeEach(function () {
    Storage::fake();
});

test('the master playlist points each rendition back to the app', function () {
    $video = streamableVideo();

    $response = $this->actingAs(User::factory()->create())->get(route('videos.playlist', [$video, 'master']));

    $response->assertOk()->assertHeader('Content-Type', 'application/vnd.apple.mpegurl');
    expect($response->getContent())
        ->toContain(route('videos.playlist', [$video, '480p']))
        ->toContain(route('videos.playlist', [$video, '720p']))
        ->not->toContain("\n480p.m3u8");
});

test('rendition playlists serve signed segment urls', function () {
    $video = streamableVideo();

    $response = $this->actingAs(User::factory()->create())->get(route('videos.playlist', [$video, '480p']));

    $response->assertOk();
    expect($response->getContent())
        ->toContain("{$video->hlsDirectory()}/480p_0000.ts?expiration=")
        ->not->toContain("\n480p_0000.ts");
});

test('students can not stream lessons that are not visible to them', function (bool $coursePublished, bool $lessonPublished) {
    $video = streamableVideo($coursePublished, $lessonPublished);

    $this->actingAs(User::factory()->create())
        ->get(route('videos.playlist', [$video, 'master']))
        ->assertForbidden();
})->with([
    'draft lesson' => [true, false],
    'draft course' => [false, true],
]);

test('admins can preview videos of draft lessons', function () {
    $video = streamableVideo(false, false);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('videos.playlist', [$video, 'master']))
        ->assertOk();
});

test('students can not stream a video that is not the current one of the lesson', function () {
    $video = streamableVideo();
    $replacement = Video::factory()->ready()->for($video->lesson)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('videos.playlist', [$replacement, 'master']))
        ->assertForbidden();
});

test('unknown renditions and unfinished videos are not found', function () {
    $video = streamableVideo();
    $queued = Video::factory()->queued()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->get(route('videos.playlist', [$video, '1080p']))->assertNotFound();
    $this->actingAs($student)->get(route('videos.playlist', [$queued, 'master']))->assertNotFound();
});

test('guests are redirected to login', function () {
    $video = streamableVideo();

    $this->get(route('videos.playlist', [$video, 'master']))->assertRedirect(route('login'));
});
