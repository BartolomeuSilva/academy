<?php

namespace App\Actions;

use App\Models\CommunityMessage;
use App\Models\CommunityReaction;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @phpstan-type Achievement array{key: string, title: string, description: string, target: int, progress: int, earned: bool}
 * @phpstan-type AchievementGroup array{key: string, title: string, achievements: list<Achievement>}
 */
class BuildAchievements
{
    /**
     * Milestones of each group, from the first step to the hardest one.
     *
     * @var array<string, array{title: string, unit: array{string, string}, milestones: list<int>}>
     */
    private const array GROUPS = [
        'lessons' => ['title' => 'Aulas concluídas', 'unit' => ['aula concluída', 'aulas concluídas'], 'milestones' => [1, 5, 10, 25, 50]],
        'courses' => ['title' => 'Cursos concluídos', 'unit' => ['curso concluído', 'cursos concluídos'], 'milestones' => [1, 3, 5]],
        'ratings' => ['title' => 'Aulas avaliadas', 'unit' => ['aula avaliada', 'aulas avaliadas'], 'milestones' => [1, 5, 10]],
        'messages' => ['title' => 'Participação na comunidade', 'unit' => ['mensagem', 'mensagens'], 'milestones' => [1, 10, 50]],
        'reactions' => ['title' => 'Reações recebidas', 'unit' => ['reação recebida', 'reações recebidas'], 'milestones' => [1, 10, 50]],
    ];

    /**
     * The user's achievements, earned when a count reaches a milestone.
     *
     * @return list<AchievementGroup>
     */
    public function handle(User $user): array
    {
        $counts = [
            'lessons' => LessonProgress::query()
                ->whereBelongsTo($user)
                ->whereNotNull('completed_at')
                ->whereHas('lesson', fn (Builder $lessons) => $lessons->visible())
                ->count(),
            'courses' => $this->completedCourses($user),
            'ratings' => LessonRating::query()->whereBelongsTo($user)->count(),
            'messages' => CommunityMessage::query()->whereBelongsTo($user)->count(),
            'reactions' => CommunityReaction::query()
                ->whereNot('user_id', $user->id)
                ->whereHas('message', fn (Builder $messages) => $messages->whereBelongsTo($user))
                ->count(),
        ];

        $groups = [];

        foreach (self::GROUPS as $key => $group) {
            $achievements = [];

            foreach ($group['milestones'] as $target) {
                $achievements[] = [
                    'key' => "{$key}-{$target}",
                    'title' => $target.' '.$group['unit'][$target === 1 ? 0 : 1],
                    'description' => $this->description($key, $target),
                    'target' => $target,
                    'progress' => min($counts[$key], $target),
                    'earned' => $counts[$key] >= $target,
                ];
            }

            $groups[] = ['key' => $key, 'title' => $group['title'], 'achievements' => $achievements];
        }

        return $groups;
    }

    /**
     * Courses of the catalog whose visible lessons the user completed.
     */
    private function completedCourses(User $user): int
    {
        return Course::query()
            ->visible()
            ->withCount([
                'lessons' => $this->visibleLessons(...),
                'lessons as completed_lessons_count' => fn (Builder $lessons) => $this->visibleLessons($lessons)
                    ->whereHas('progress', fn (Builder $progress) => $progress->whereBelongsTo($user)->whereNotNull('completed_at')),
            ])
            ->get()
            ->filter(fn (Course $course) => $course->lessons_count > 0 && $course->completed_lessons_count >= $course->lessons_count)
            ->count();
    }

    /**
     * @param  Builder<Lesson>  $lessons
     * @return Builder<Lesson>
     */
    private function visibleLessons(Builder $lessons): Builder
    {
        return $lessons->visible();
    }

    private function description(string $key, int $target): string
    {
        $first = $target === 1;

        return match ($key) {
            'lessons' => $first ? 'Conclua sua primeira aula.' : "Conclua {$target} aulas.",
            'courses' => $first ? 'Termine todas as aulas de um curso.' : "Termine {$target} cursos.",
            'ratings' => $first ? 'Avalie uma aula.' : "Avalie {$target} aulas.",
            'messages' => $first ? 'Envie sua primeira mensagem na comunidade.' : "Envie {$target} mensagens na comunidade.",
            default => $first ? 'Receba uma reação de outro aluno.' : "Receba {$target} reações de outros alunos.",
        };
    }
}
