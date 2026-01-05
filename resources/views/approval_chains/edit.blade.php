<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Approval Chain</h1>
            <p class="mt-2 text-sm text-slate-500">Modify the approval workflow for estimates.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('approval-chains.index') }}"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to Chains
            </a>
        </div>
    </div>

    <form action="{{ route('approval-chains.update', $approvalChain) }}" method="POST" x-data="approvalChainBuilder()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Chain Details</h3>

                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-900">Chain Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $approvalChain->name) }}"
                                required
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-900">Description</label>
                            <textarea name="description" id="description" rows="3"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">{{ old('description', $approvalChain->description) }}</textarea>
                        </div>


                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="min_amount" class="block text-sm font-medium text-gray-900">Min
                                    Amount</label>
                                <input type="number" name="min_amount" id="min_amount"
                                    value="{{ old('min_amount', $approvalChain->min_amount) }}" step="0.01" min="0"
                                    placeholder="0.00"
                                    class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            </div>

                            <div>
                                <label for="max_amount" class="block text-sm font-medium text-gray-900">Max
                                    Amount</label>
                                <input type="number" name="max_amount" id="max_amount"
                                    value="{{ old('max_amount', $approvalChain->max_amount) }}" step="0.01" min="0"
                                    placeholder="0.00"
                                    class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <label for="min_discount_percentage" class="block text-sm font-medium text-gray-900">Min
                                Discount (%)</label>
                            <input type="number" name="min_discount_percentage" id="min_discount_percentage"
                                value="{{ old('min_discount_percentage', $approvalChain->min_discount_percentage) }}"
                                step="0.01" min="0" max="100" placeholder="0.00"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>

                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-900">Currency</label>
                            <select name="currency" id="currency"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                <option value="INR" {{ old('currency', $approvalChain->currency) === 'INR' ? 'selected' : '' }}>INR</option>
                                <option value="USD" {{ old('currency', $approvalChain->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency', $approvalChain->currency) === 'EUR' ? 'selected' : '' }}>EUR</option>
                            </select>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $approvalChain->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                        </div>
                    </div>
                </div>

                <!-- Approval Steps -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-900">Approval Steps</h3>
                        <button type="button" @click="addStep"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Step
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(step, index) in steps" :key="index">
                            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">
                                    <span x-text="index + 1"></span>
                                </div>

                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-900 mb-2">Approver</label>
                                    <select :name="'steps[' + index + '][user_id]'" x-model="step.user_id" required
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                        <option value="">Select approver...</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}
                                                ({{ ucfirst(str_replace('_', ' ', $user->role)) }})</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" :name="'steps[' + index + '][order]'" :value="index + 1">
                                </div>

                                <button type="button" @click="removeStep(index)"
                                    class="mt-6 text-red-600 hover:text-red-500">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <div x-show="steps.length === 0" class="text-center py-8 text-gray-500">
                            <p class="text-sm">No approval steps added yet. Click "Add Step" to begin.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-x-6">
                    <a href="{{ route('approval-chains.index') }}"
                        class="text-sm font-semibold text-gray-900">Cancel</a>
                    <button type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Update Chain
                    </button>
                </div>
            </div>

            <!-- Preview -->
            <div class="lg:col-span-1">
                <div class="sticky top-4 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Approval Flow Preview</h3>

                    <div x-show="steps.length > 0" class="space-y-3">
                        <template x-for="(step, index) in steps" :key="index">
                            <div>
                                <div
                                    class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg border border-indigo-200">
                                    <span
                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white"
                                        x-text="index + 1"></span>
                                    <span class="text-sm font-medium text-gray-900">Step <span
                                            x-text="index + 1"></span></span>
                                </div>
                                <div x-show="index < steps.length - 1" class="flex justify-center py-2">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="steps.length === 0" class="text-center py-8 text-gray-400">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-sm">No steps yet</p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        function approvalChainBuilder() {
            return {
                steps: @json($approvalChain->steps->map(fn($step) => ['user_id' => $step->user_id])),
                addStep() {
                    this.steps.push({ user_id: '' });
                },
                removeStep(index) {
                    this.steps.splice(index, 1);
                }
            }
        }
    </script>
</x-app-layout>