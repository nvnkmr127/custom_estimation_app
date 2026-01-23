@props(['estimate', 'potentialFollowers'])

<div x-data="{ showFollowerModal: false }"
    class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-slate-900">Followers</h3>
        @if(auth()->id() === $estimate->created_by || auth()->user()->hasRole(['super_admin', 'admin']))
            <button @click="showFollowerModal = true" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                + Add
            </button>
        @endif
    </div>

    <div class="space-y-3">
        @foreach($estimate->followers as $follower)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @if($follower->avatar)
                        <img src="{{ $follower->avatar }}" class="h-6 w-6 rounded-full" alt="">
                    @else
                        <div
                            class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600">
                            {{ substr($follower->name, 0, 1) }}
                        </div>
                    @endif
                    <div class="text-sm text-slate-700">{{ $follower->name }}</div>
                </div>

                @if(auth()->id() === $estimate->created_by || auth()->user()->hasRole(['super_admin', 'admin']))
                    @if($estimate->created_by !== $follower->id) <!-- Don't remove creator -->
                        <form action="{{ route('estimates.followers.remove', [$estimate, $follower]) }}" method="POST"
                            onsubmit="return confirm('Remove this follower?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-slate-400 hover:text-red-500">&times;</button>
                        </form>
                    @endif
                @endif
            </div>
        @endforeach
    </div>

    <!-- Add Follower Modal (Simplified) -->
    <template x-teleport="body">
        <div x-show="showFollowerModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showFollowerModal" @click.away="showFollowerModal = false"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                        <button type="button" @click="showFollowerModal = false"
                            class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Add Follower
                        </h3>
                        <form action="{{ route('estimates.followers.add', $estimate) }}" method="POST" class="mt-4">
                            @csrf
                            <div class="mb-4">
                                <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                                <select name="user_id" id="user_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach($potentialFollowers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <div class="relative flex items-start">
                                    <div class="flex h-6 items-center">
                                        <input id="permission_edit" aria-describedby="permission_edit-description"
                                            name="permissions[]" value="edit" type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                    </div>
                                    <div class="ml-3 text-sm leading-6">
                                        <label for="permission_edit" class="font-medium text-gray-900">Allow
                                            Editing</label>
                                        <p id="permission_edit-description" class="text-gray-500">User can edit this
                                            estimate (edits will create a new version for approval).</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                <button type="submit"
                                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:col-start-2">Add</button>
                                <button type="button" @click="showFollowerModal = false"
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>