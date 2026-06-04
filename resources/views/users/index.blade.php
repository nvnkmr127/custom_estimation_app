<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Users</h1>
            <p class="mt-2 text-sm text-slate-500">Manage user accounts, roles, and permissions.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-3">
            <a href="{{ route('permissions.index') }}"
                class="block rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all duration-200">
                Roles & Permissions
            </a>
            <a href="{{ route('users.trash') }}"
                class="block rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all duration-200">
                View Trash
            </a>
            <a href="{{ route('users.create') }}"
                class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all duration-200">
                Add User
            </a>
        </div>
    </div>

    <div class="mt-6 bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
        <form action="{{ route('users.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="relative w-full sm:w-80">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." class="block w-full pl-10 pr-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50">
            </div>
            <div class="flex gap-4 items-center w-full sm:w-auto">
                <select name="role" class="block w-full sm:w-48 text-sm border border-slate-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50">
                    <option value="">All Roles</option>
                    @foreach($roles as $value => $label)
                        <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>
                            {{ explode(' - ', $label)[0] }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition duration-150">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'role']))
                    <a href="{{ route('users.index') }}" class="px-4 py-2 text-slate-600 hover:text-slate-800 text-sm font-semibold transition duration-150">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mt-4 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm ring-1 ring-black ring-opacity-5 sm:rounded-xl bg-white">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:pl-6">
                                    Name</th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Email</th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Role</th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Max Discount</th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Created</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse ($users as $user)
                                <tr class="hover:bg-slate-50/50 transition-colors duration-150">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                        {{ $user->name }}
                                        @if($user->id === auth()->id())
                                            <span
                                                class="ml-2 inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">You</span>
                                        @endif
                                        @if($user->source === 'sso')
                                            <span
                                                class="ml-2 inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-700/10 uppercase tracking-wider">SSO</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                                        {{ $user->email }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $user->role_badge_class }}">
                                            {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ $user->max_discount_percentage }}%
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('users.show', $user) }}"
                                                class="text-gray-600 hover:text-gray-900 font-medium">View</a>
                                            <a href="{{ route('users.edit', $user) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</a>
                                            @if($user->id !== auth()->id())
                                                <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                    class="inline-block"
                                                    onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-rose-600 hover:text-rose-900 font-medium bg-transparent border-0 cursor-pointer p-0">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-10 text-center text-sm text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="rounded-full bg-slate-100 p-3 mb-4">
                                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                            </div>
                                            <h3 class="text-sm font-semibold text-slate-900">No users found</h3>
                                            <p class="mt-1 text-sm text-slate-500">Get started by adding a new user.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-6">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>