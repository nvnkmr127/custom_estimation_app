<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create User</h1>
            <p class="mt-2 text-sm text-slate-500">Add a new user to the system with role and permissions.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('users.index') }}"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to Users
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
        <form action="{{ route('users.store') }}" method="POST" class="px-4 py-6 sm:p-8">
            @csrf

            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <!-- Name -->
                <div class="sm:col-span-3">
                    <label for="name" class="block text-sm font-medium leading-6 text-gray-900">Full Name</label>
                    <div class="mt-2">
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('name') ring-red-300 @enderror">
                    </div>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="sm:col-span-3">
                    <label for="email" class="block text-sm font-medium leading-6 text-gray-900">Email Address</label>
                    <div class="mt-2">
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('email') ring-red-300 @enderror">
                    </div>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role -->
                <div class="sm:col-span-3">
                    <label for="role" class="block text-sm font-medium leading-6 text-gray-900">Role</label>
                    <div class="mt-2">
                        <select name="role" id="role" required
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('role') ring-red-300 @enderror">
                            <option value="">Select a role...</option>
                            @foreach($roles as $value => $label)
                                <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('role')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Max Discount Percentage -->
                <div class="sm:col-span-3">
                    <label for="max_discount_percentage" class="block text-sm font-medium leading-6 text-gray-900">Max
                        Discount Percentage</label>
                    <div class="mt-2">
                        <input type="number" name="max_discount_percentage" id="max_discount_percentage"
                            value="{{ old('max_discount_percentage', 0) }}" min="0" max="100" step="0.01"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('max_discount_percentage') ring-red-300 @enderror">
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Maximum discount this user can apply to estimates.</p>
                    @error('max_discount_percentage')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="sm:col-span-3">
                    <label for="password" class="block text-sm font-medium leading-6 text-gray-900">Password</label>
                    <div class="mt-2">
                        <input type="password" name="password" id="password" required
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 @error('password') ring-red-300 @enderror">
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Minimum 8 characters.</p>
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div class="sm:col-span-3">
                    <label for="password_confirmation" class="block text-sm font-medium leading-6 text-gray-900">Confirm
                        Password</label>
                    <div class="mt-2">
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-x-6">
                <a href="{{ route('users.index') }}" class="text-sm font-semibold leading-6 text-gray-900">Cancel</a>
                <button type="submit"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Create User
                </button>
            </div>
        </form>
    </div>
</x-app-layout>