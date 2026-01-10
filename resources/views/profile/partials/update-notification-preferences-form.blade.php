<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Notification Preferences') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Manage how and when you receive notifications.') }}
        </p>
    </header>

    <form method="post" action="{{ route('preferences.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="space-y-4">
            @php
                $types = \App\Services\Notifications\NotificationPreferenceService::getAvailableEventTypes();
                $userPrefs = \App\Models\NotificationPreference::where('user_id', auth()->id())->get()->keyBy('event_type');
                $frequencies = [
                    \App\Models\NotificationPreference::FREQUENCY_INSTANT => 'Instant',
                    \App\Models\NotificationPreference::FREQUENCY_DAILY => 'Daily Digest',
                    \App\Models\NotificationPreference::FREQUENCY_WEEKLY => 'Weekly Digest',
                    \App\Models\NotificationPreference::FREQUENCY_MUTED => 'Muted',
                ];
            @endphp

            @foreach($types as $type => $config)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="max-w-xl">
                        <h3 class="text-sm font-semibold text-gray-800">{{ $config['name'] }}</h3>
                        <p class="text-xs text-gray-500">{{ $config['description'] }}</p>
                    </div>

                    <div class="ml-4">
                        <select name="preferences[{{ $type }}]"
                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($frequencies as $value => $label)
                                <option value="{{ $value }}" {{ ($userPrefs[$type]->frequency ?? $config['default']) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Preferences') }}</x-primary-button>

            @if (session('status') === 'notification-preferences-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>