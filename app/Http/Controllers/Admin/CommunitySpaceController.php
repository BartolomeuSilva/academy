<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommunitySpaceRequest;
use App\Models\CommunitySpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CommunitySpaceController extends Controller
{
    /**
     * List the community channels in their order.
     */
    public function index(): Response
    {
        return Inertia::render('admin/community/Spaces', [
            'spaces' => CommunitySpace::query()
                ->withCount('messages')
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(fn (CommunitySpace $space) => [
                    'id' => $space->id,
                    'name' => $space->name,
                    'slug' => $space->slug,
                    'emoji' => $space->emoji,
                    'description' => $space->description,
                    'staff_only' => $space->staff_only,
                    'position' => $space->position,
                    'messages_count' => (int) $space->messages_count,
                ]),
        ]);
    }

    /**
     * Create a channel, placed last unless an order is given.
     */
    public function store(CommunitySpaceRequest $request): RedirectResponse
    {
        $space = new CommunitySpace;
        $this->fill($space, $request);
        $space->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Canal criado.']);

        return back();
    }

    /**
     * Rename or reconfigure a channel.
     */
    public function update(CommunitySpaceRequest $request, CommunitySpace $space): RedirectResponse
    {
        $this->fill($space, $request);
        $space->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Canal atualizado.']);

        return back();
    }

    /**
     * Delete an empty channel; channels with messages are kept so no conversation is lost by accident.
     */
    public function destroy(CommunitySpace $space): RedirectResponse
    {
        if ($space->messages()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'space' => 'Este canal tem mensagens e não pode ser excluído.',
            ]);
        }

        $space->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Canal excluído.']);

        return back();
    }

    private function fill(CommunitySpace $space, CommunitySpaceRequest $request): void
    {
        $name = $request->string('name')->squish()->value();

        $space->fill([
            'name' => $name,
            'slug' => Str::slug($request->string('slug')->value() ?: $name),
            'emoji' => $request->string('emoji')->trim()->value() ?: null,
            'description' => $request->string('description')->trim()->value() ?: null,
            'staff_only' => $request->boolean('staff_only'),
        ]);
        $space->position = $request->filled('position')
            ? $request->integer('position')
            : ($space->exists ? $space->position : (int) CommunitySpace::query()->max('position') + 1);
    }
}
