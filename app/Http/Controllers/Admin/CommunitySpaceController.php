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
     * List the community spaces in their order.
     */
    public function index(): Response
    {
        return Inertia::render('admin/community/Spaces', [
            'spaces' => CommunitySpace::query()
                ->withCount('topics')
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(fn (CommunitySpace $space) => [
                    'id' => $space->id,
                    'name' => $space->name,
                    'slug' => $space->slug,
                    'description' => $space->description,
                    'staff_only' => $space->staff_only,
                    'position' => $space->position,
                    'topics_count' => (int) $space->topics_count,
                ]),
        ]);
    }

    /**
     * Create a space, placed last unless an order is given.
     */
    public function store(CommunitySpaceRequest $request): RedirectResponse
    {
        $space = new CommunitySpace;
        $this->fill($space, $request);
        $space->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Espaço criado.']);

        return back();
    }

    /**
     * Rename or reconfigure a space.
     */
    public function update(CommunitySpaceRequest $request, CommunitySpace $space): RedirectResponse
    {
        $this->fill($space, $request);
        $space->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Espaço atualizado.']);

        return back();
    }

    /**
     * Delete an empty space; spaces with topics are kept so no conversation is lost by accident.
     */
    public function destroy(CommunitySpace $space): RedirectResponse
    {
        if ($space->topics()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'space' => 'Este espaço tem tópicos. Mova ou exclua os tópicos antes de excluir o espaço.',
            ]);
        }

        $space->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Espaço excluído.']);

        return back();
    }

    private function fill(CommunitySpace $space, CommunitySpaceRequest $request): void
    {
        $name = $request->string('name')->squish()->value();

        $space->fill([
            'name' => $name,
            'slug' => Str::slug($request->string('slug')->value() ?: $name),
            'description' => $request->string('description')->trim()->value() ?: null,
            'staff_only' => $request->boolean('staff_only'),
        ]);
        $space->position = $request->filled('position')
            ? $request->integer('position')
            : ($space->exists ? $space->position : (int) CommunitySpace::query()->max('position') + 1);
    }
}
