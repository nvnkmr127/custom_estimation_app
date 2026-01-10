<x-app-layout>
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Unit Types</h1>
            <p class="mt-2 text-sm text-slate-500">Manage main unit types and their options for estimates (e.g.
                Flooring: sqft, sqm).</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <button type="button" @click="$dispatch('open-modal', 'add-unit-type')"
                class="block rounded-lg bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all">
                Add Unit Type
            </button>
        </div>
    </div>

    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-slate-300 bg-white">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Main
                                    Type</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Units
                                </th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 font-semibold text-slate-900">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($unitTypes as $type)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                        {{ $type->name }}</td>
                                    <td class="px-3 py-4 text-sm text-slate-500">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($type->units as $unit)
                                                <span
                                                    class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{ $unit }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex justify-end gap-3">
                                            <button @click="$dispatch('open-modal', 'edit-unit-type-{{ $type->id }}')"
                                                class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                            <form action="{{ route('unit-types.destroy', $type) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this unit type?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-rose-600 hover:text-rose-900">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <x-modal name="edit-unit-type-{{ $type->id }}" focusable>
                                    <form method="post" action="{{ route('unit-types.update', $type) }}" class="p-6">
                                        @csrf
                                        @method('PUT')
                                        <h2 class="text-lg font-bold text-slate-900">Edit Unit Type</h2>
                                        <div class="mt-6 space-y-4">
                                            <div>
                                                <x-input-label for="edit_name_{{ $type->id }}" value="Main Type Name" />
                                                <x-text-input id="edit_name_{{ $type->id }}" name="name" type="text"
                                                    class="mt-1 block w-full" value="{{ $type->name }}" required />
                                            </div>
                                            <div x-data="{ units: {{ json_encode($type->units) }} }">
                                                <x-input-label value="Units" />
                                                <div class="mt-2 space-y-2">
                                                    <template x-for="(unit, index) in units" :key="index">
                                                        <div class="flex gap-2">
                                                            <x-text-input x-model="units[index]" name="units[]" type="text"
                                                                class="flex-1" required />
                                                            <button type="button" @click="units.splice(index, 1)"
                                                                class="text-rose-600 hover:text-rose-900">
                                                                <svg class="h-5 w-5" viewBox="0 0 20 20"
                                                                    fill="currentColor">
                                                                    <path fill-rule="evenodd"
                                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                                                        clip-rule="evenodd" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </template>
                                                    <button type="button" @click="units.push('')"
                                                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">+
                                                        Add Unit</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-6 flex justify-end gap-3">
                                            <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                            <x-primary-button>Update Unit Type</x-primary-button>
                                        </div>
                                    </form>
                                </x-modal>
                            @endforeach
                            @if($unitTypes->isEmpty())
                                <tr>
                                    <td colspan="3" class="py-10 text-center text-sm text-slate-500">No unit types defined
                                        yet.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <x-modal name="add-unit-type" focusable>
        <form method="post" action="{{ route('unit-types.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-bold text-slate-900">Add New Unit Type</h2>
            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="name" value="Main Type Name (e.g. Flooring)" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                        placeholder="e.g. Flooring" required />
                </div>
                <div x-data="{ units: [''] }">
                    <x-input-label value="Units" />
                    <div class="mt-2 space-y-2">
                        <template x-for="(unit, index) in units" :key="index">
                            <div class="flex gap-2">
                                <x-text-input x-model="units[index]" name="units[]" type="text" class="flex-1"
                                    placeholder="e.g. sqft" required />
                                <button type="button" @click="units.splice(index, 1)" x-show="units.length > 1"
                                    class="text-rose-600 hover:text-rose-900">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="units.push('')"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">+ Add Unit</button>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button>Create Unit Type</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>