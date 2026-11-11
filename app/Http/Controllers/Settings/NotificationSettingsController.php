<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    /**
     * Show the notification settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Notifications', [
            'notifyByEmail' => $request->user()->notify_by_email,
        ]);
    }

    /**
     * Turn the reply e-mails on or off; the bell always works.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(['notify_by_email' => ['required', 'boolean']]);

        $request->user()->forceFill(['notify_by_email' => $validated['notify_by_email']])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Preferência salva.']);

        return back();
    }
}
