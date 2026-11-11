<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\DeleteStoragePaths;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

class AvatarController extends Controller
{
    /**
     * Largest photo accepted, in kilobytes.
     */
    public const int MAX_KILOBYTES = 2048;

    /**
     * Replace the user's profile photo.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_KILOBYTES, 'dimensions:min_width=64,min_height=64']],
            ['avatar.dimensions' => 'A foto precisa ter pelo menos 64 × 64 pixels.'],
            ['avatar' => 'foto'],
        );

        $user = $request->user();
        $previous = $user->avatar_path;

        $user->avatar_path = $request->file('avatar')?->store('avatars') ?: throw new RuntimeException('Não foi possível salvar a foto.');
        $user->save();

        if ($previous !== null) {
            DeleteStoragePaths::dispatch([$previous]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Foto atualizada.']);

        return back();
    }

    /**
     * Remove the user's profile photo.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path !== null) {
            DeleteStoragePaths::dispatch([$user->avatar_path]);
            $user->avatar_path = null;
            $user->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Foto removida.']);

        return back();
    }
}
