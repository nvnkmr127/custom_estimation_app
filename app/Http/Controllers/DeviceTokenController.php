<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Register or update a device token for push notifications.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'required|string|max:50',
        ]);

        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => auth()->id(),
                'platform' => $validated['platform'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully.',
            'device_token' => $deviceToken,
        ]);
    }

    /**
     * Deregister a device token (e.g., when logging out).
     */
    public function deregister(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
        ]);

        // Scope to the current user so one account can't deregister another's device token.
        DeviceToken::where('token', $validated['token'])
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device token deregistered successfully.',
        ]);
    }
}
