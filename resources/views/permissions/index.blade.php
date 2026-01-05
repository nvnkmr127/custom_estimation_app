<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Roles & Permissions</h1>
            <p class="mt-2 text-sm text-slate-500">View role definitions and their assigned permissions.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('users.index') }}"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to Users
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach($roles as $roleKey => $roleInfo)
            @php
                $permissions = $permissionsForRoles[$roleKey] ?? [];
                $colorClasses = [
                    'purple' => 'bg-purple-50 border-purple-200',
                    'indigo' => 'bg-indigo-50 border-indigo-200',
                    'blue' => 'bg-blue-50 border-blue-200',
                    'gray' => 'bg-gray-50 border-gray-200',
                ];
                $badgeClasses = [
                    'purple' => 'bg-purple-100 text-purple-700 ring-purple-700/10',
                    'indigo' => 'bg-indigo-100 text-indigo-700 ring-indigo-700/10',
                    'blue' => 'bg-blue-100 text-blue-700 ring-blue-700/10',
                    'gray' => 'bg-gray-100 text-gray-700 ring-gray-700/10',
                ];
            @endphp

            <div
                class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl border-t-4 {{ $colorClasses[$roleInfo['color']] ?? 'border-gray-200' }}">
                <div class="px-6 py-5 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $roleInfo['name'] }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $roleInfo['description'] }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeClasses[$roleInfo['color']] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ count($permissions) }} permissions
                            </span>
                            <a href="{{ route('permissions.edit', $roleKey) }}"
                                class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                                Edit
                            </a>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4">
                    @foreach($permissionsByCategory as $category => $categoryPermissions)
                        @php
                            $roleHasAnyInCategory = !empty(array_intersect($categoryPermissions, $permissions));
                        @endphp

                        @if($roleHasAnyInCategory)
                            <div class="mb-4 last:mb-0">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $category }}</h4>
                                <ul class="space-y-1">
                                    @foreach($categoryPermissions as $permission)
                                        @if(in_array($permission, $permissions))
                                            <li class="flex items-start gap-2 text-sm">
                                                <svg class="h-5 w-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                <span
                                                    class="text-gray-700">{{ \App\Services\PermissionService::getPermissionDescription($permission) }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <!-- Permission Reference Table -->
    <div class="mt-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl">
        <div class="px-6 py-5 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">All Permissions Reference</h3>
            <p class="mt-1 text-sm text-gray-500">Complete list of all available permissions in the system.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Permission</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Roles</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach(\App\Services\PermissionService::PERMISSIONS as $permission => $description)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                <code class="rounded bg-gray-100 px-2 py-1 text-xs">{{ $permission }}</code>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $description }}</td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($roles as $roleKey => $roleInfo)
                                        @if(\App\Services\PermissionService::roleHasPermission($roleKey, $permission))
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badgeClasses[$roleInfo['color']] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $roleInfo['name'] }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>