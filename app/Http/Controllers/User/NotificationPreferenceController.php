<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class NotificationPreferenceController extends Controller
{
    /**
     * Update the user's notification preferences.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $preferences = $request->input('preferences', []);
        $availableTypes = NotificationPreferenceService::getAvailableEventTypes();

        foreach ($availableTypes as $type => $config) {
            $frequency = $preferences[$type] ?? $config['default'];

            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'event_type' => $type,
                    'channel' => 'email',
                ],
                [
                    'frequency' => $frequency,
                ]
            );
        }

        return Redirect::route('profile.edit')->with('status', 'notification-preferences-updated');
    }
}
