<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Permissions: {{ $roleInfo['name'] }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $roleInfo['description'] }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('permissions.index') }}"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to Permissions
            </a>
        </div>
    </div>

    <form action="{{ route('permissions.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
            <div class="mb-6">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Select Permissions</h3>
                <p class="text-sm text-gray-500">Choose which permissions this role should have.</p>
            </div>

            @foreach($permissionsByCategory as $category => $categoryPermissions)
                <div class="mb-6 last:mb-0">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-200">{{ $category }}</h4>
                    <div class="space-y-2">
                        @foreach($categoryPermissions as $permission)
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                        id="permission_{{ $permission }}" {{ in_array($permission, $currentPermissions) ? 'checked' : '' }}
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="permission_{{ $permission }}" class="font-medium text-gray-700 cursor-pointer">
                                        {{ \App\Services\PermissionService::getPermissionDescription($permission) }}
                                    </label>
                                    <p class="text-gray-500">
                                        <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ $permission }}</code>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="mt-8 pt-6 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    <span id="selected-count">{{ count($currentPermissions) }}</span> permissions selected
                </div>
                <div class="flex items-center gap-x-3">
                    <a href="{{ route('permissions.index') }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                    <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Save Permissions
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        // Update count when checkboxes change
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const count = document.querySelectorAll('input[type="checkbox"]:checked').length;
                document.getElementById('selected-count').textContent = count;
            });
        });
    </script>
</x-app-layout>