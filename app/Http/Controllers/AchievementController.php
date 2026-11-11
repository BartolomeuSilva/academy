<?php

namespace App\Http\Controllers;

use App\Actions\BuildAchievements;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AchievementController extends Controller
{
    /**
     * Show the student's achievements, earned and still to come.
     */
    public function index(Request $request, BuildAchievements $buildAchievements): Response
    {
        $groups = $buildAchievements->handle($request->user());
        $achievements = array_merge([], ...array_column($groups, 'achievements'));

        return Inertia::render('achievements/Index', [
            'groups' => $groups,
            'earned' => count(array_filter($achievements, fn (array $achievement) => $achievement['earned'])),
            'total' => count($achievements),
        ]);
    }
}
