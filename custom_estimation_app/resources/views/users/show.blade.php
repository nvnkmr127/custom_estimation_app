<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">User Profile</h1>
            <p class="mt-2 text-sm text-slate-500">Details for {{ $user->name }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-x-3">
            <a href="{{ route('users.edit', $user) }}"
                class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Edit User
            </a>
            <a href="{{ route('users.index') }}"
                class="block rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                Back
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden max-w-2xl">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center gap-x-4">
                @if($user->avatar)
                    <img class="h-16 w-16 rounded-full bg-slate-100 object-cover" src="{{ $user->avatar }}" alt="">
                @else
                    <div
                        class="h-16 w-16 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xl font-bold uppercase">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h3 class="text-base font-semibold leading-7 text-gray-900">{{ $user->name }}</h3>
                    <p class="text-sm leading-6 text-gray-500">{{ $user->email }}</p>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-100 px-4 py-5 sm:p-0">
            <dl class="divide-y divide-gray-100">
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-900">Role</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        @php
                            $roleStyles = [
                                'super_admin' => 'bg-purple-50 text-purple-700 ring-purple-700/10',
                                'estimator_admin' => 'bg-indigo-50 text-indigo-700 ring-indigo-700/10',
                                'sales_manager' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
                                'sales' => 'bg-slate-100 text-slate-700 ring-slate-600/20',
                            ];
                        @endphp
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $roleStyles[$user->role] ?? 'bg-slate-100 text-slate-600 ring-slate-500/10' }}">
                            {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                        </span>
                    </dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-900">Max Discount Allowed</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        {{ $user->max_discount_percentage }}%</dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-900">Member Since</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        {{ $user->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-app-layout>