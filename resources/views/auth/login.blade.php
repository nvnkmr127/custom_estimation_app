<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            @if (app()->environment('local'))
                <div class="fixed bottom-4 right-4 p-4 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <p class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">Auto Login</p>
                    <div class="space-y-2">
                        <button type="button" onclick="autoLogin('super@example.com')"
                            class="block w-full text-left px-3 py-1.5 text-xs text-white bg-purple-600 hover:bg-purple-700 rounded transition-colors">
                            Super Admin
                        </button>
                        <button type="button" onclick="autoLogin('estimator@example.com')"
                            class="block w-full text-left px-3 py-1.5 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded transition-colors">
                            Estimator Admin
                        </button>
                        <button type="button" onclick="autoLogin('manager@example.com')"
                            class="block w-full text-left px-3 py-1.5 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors">
                            Sales Manager
                        </button>
                        <button type="button" onclick="autoLogin('sales@example.com')"
                            class="block w-full text-left px-3 py-1.5 text-xs text-white bg-gray-600 hover:bg-gray-700 rounded transition-colors">
                            Sales
                        </button>
                    </div>
                </div>

                <script>
                    function autoLogin(email) {
                        document.getElementById('email').value = email;
                        document.getElementById('password').value = 'password';
                        document.querySelector('form').submit();
                    }
                </script>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>