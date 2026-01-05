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

            {{-- Direct Login Buttons --}}
            <div class="mt-6 grid grid-cols-2 gap-3 w-full border-t border-gray-100 pt-6">
                <button type="button" onclick="autoLogin('super@example.com')"
                    class="flex justify-center items-center px-4 py-2 bg-purple-50 text-purple-700 text-xs font-semibold rounded-lg hover:bg-purple-100 transition-colors border border-purple-200">
                    Super Admin
                </button>
                <button type="button" onclick="autoLogin('estimator@example.com')"
                    class="flex justify-center items-center px-4 py-2 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-100 transition-colors border border-indigo-200">
                    Est. Admin
                </button>
                <button type="button" onclick="autoLogin('manager@example.com')"
                    class="flex justify-center items-center px-4 py-2 bg-blue-50 text-blue-700 text-xs font-semibold rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                    Manager
                </button>
                <button type="button" onclick="autoLogin('sales@example.com')"
                    class="flex justify-center items-center px-4 py-2 bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-100 transition-colors border border-slate-200">
                    Estimator (Sales)
                </button>
            </div>

            <script>
                function autoLogin(email) {
                    const emailInput = document.getElementById('email');
                    const passInput = document.getElementById('password');

                    if (emailInput && passInput) {
                        emailInput.value = email;
                        passInput.value = 'password';
                        emailInput.form.submit();
                    }
                }
            </script>

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>