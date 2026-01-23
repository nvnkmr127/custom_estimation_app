<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Header & Search -->
        <div
            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <div class="flex-1 w-full sm:w-auto">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out"
                        placeholder="Search brands...">
                </div>
            </div>
            <div class="w-full sm:w-auto">
                <button wire:click="create"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4 mr-2" fill="none" class="w-6 h-6" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Brand
                </button>
            </div>
        </div>

        <!-- Brands Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse ($brands as $brand)
                <div
                    class="group bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-slate-200 overflow-hidden relative flex flex-col h-full">
                    <!-- Default Badge -->
                    @if ($brand->is_default)
                        <div class="absolute top-3 right-3 z-10">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 shadow-sm border border-emerald-200">
                                Default
                            </span>
                        </div>
                    @endif

                    <!-- Brand Header / Color Preview -->
                    <div class="h-24 w-full relative"
                        style="background: linear-gradient(135deg, {{ $brand->primary_color }} 0%, {{ $brand->primary_color }} 50%, {{ $brand->secondary_color ?? $brand->primary_color }} 100%);">
                        <div class="absolute -bottom-8 left-6">
                            @if ($brand->logo_url)
                                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}"
                                    class="h-16 w-16 rounded-xl object-contain bg-white border-4 border-white shadow-md">
                            @else
                                <div
                                    class="h-16 w-16 rounded-xl flex items-center justify-center bg-white border-4 border-white shadow-md text-slate-500 font-bold text-xl uppercase">
                                    {{ substr($brand->name, 0, 2) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Brand Content -->
                    <div class="pt-10 px-6 pb-6 flex-1 flex flex-col">
                        <h3
                            class="text-lg font-bold text-slate-900 group-hover:text-indigo-600 transition-colors mb-1 truncate">
                            {{ $brand->name }}
                        </h3>
                        <div class="text-xs text-slate-500 mb-4 flex gap-2">
                            @if($brand->website_url)
                                <a href="{{ $brand->website_url }}" target="_blank"
                                    class="hover:underline flex items-center gap-1 hover:text-indigo-500">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Website
                                </a>
                            @endif
                        </div>

                        <div class="mt-auto pt-4 flex justify-end gap-2 border-t border-slate-100">
                            <button wire:click="edit({{ $brand->id }})"
                                class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-slate-500 bg-slate-50 hover:bg-white hover:text-indigo-600 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            @if(!$brand->is_default) {{-- Prevent deleting default brand if desired, logic also in backend
                                --}}
                                <button wire:click="confirmDelete({{ $brand->id }})"
                                    class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-slate-500 bg-slate-50 hover:bg-white hover:text-rose-600 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-1 md:col-span-2 lg:col-span-3 xl:col-span-4 text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900">No brands found</h3>
                    <p class="mt-1 text-sm text-slate-500">Get started by creating a new brand.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $brands->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <x-modal name="brand-modal" :show="$showModal" maxWidth="md" focusable>
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $isEditing ? __('Edit Brand') : __('Create New Brand') }}
            </h2>

            <div class="space-y-4">
                <!-- Name -->
                <div>
                    <x-input-label for="name" :value="__('Brand Name')" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text"
                        placeholder="e.g. Acme Corp" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Logo URL -->
                <div>
                    <x-input-label for="logo_url" :value="__('Logo URL')" />
                    <x-text-input wire:model="logo_url" id="logo_url" class="block mt-1 w-full" type="url"
                        placeholder="https://example.com/logo.png" />
                    <x-input-error :messages="$errors->get('logo_url')" class="mt-2" />
                </div>

                <!-- Colors -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="primary_color" :value="__('Primary Color')" />
                        <div class="flex items-center gap-2 mt-1">
                            <input wire:model.live="primary_color" type="color"
                                class="h-9 w-9 p-1 rounded border border-slate-300 cursor-pointer">
                            <x-text-input wire:model.live="primary_color" id="primary_color"
                                class="block w-full uppercase" type="text" maxlength="7" />
                        </div>
                        <x-input-error :messages="$errors->get('primary_color')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="secondary_color" :value="__('Secondary Color')" />
                        <div class="flex items-center gap-2 mt-1">
                            <input wire:model.live="secondary_color" type="color"
                                class="h-9 w-9 p-1 rounded border border-slate-300 cursor-pointer">
                            <x-text-input wire:model.live="secondary_color" id="secondary_color"
                                class="block w-full uppercase" type="text" maxlength="7" />
                        </div>
                        <x-input-error :messages="$errors->get('secondary_color')" class="mt-2" />
                    </div>
                </div>

                <!-- Additional Info -->
                <div>
                    <x-input-label for="footer_text" :value="__('Footer Text')" />
                    <textarea wire:model="footer_text" id="footer_text"
                        class="block mt-1 w-full border-slate-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows="2" placeholder="Copyright © 2024..."></textarea>
                    <x-input-error :messages="$errors->get('footer_text')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="support_email" :value="__('Support Email')" />
                        <x-text-input wire:model="support_email" id="support_email" class="block mt-1 w-full"
                            type="email" />
                        <x-input-error :messages="$errors->get('support_email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="website_url" :value="__('Website URL')" />
                        <x-text-input wire:model="website_url" id="website_url" class="block mt-1 w-full" type="url" />
                        <x-input-error :messages="$errors->get('website_url')" class="mt-2" />
                    </div>
                </div>

                <!-- Default Checkbox -->
                <div class="flex items-center mt-2">
                    <input wire:model="is_default" id="is_default" type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded">
                    <label for="is_default" class="ml-2 block text-sm text-slate-900">
                        {{ __('Set as default brand') }}
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="closeModal">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button wire:click="save">
                    {{ $isEditing ? __('Update Brand') : __('Create Brand') }}
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal name="delete-modal" :show="$confirmingDeletion" maxWidth="sm">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">
                {{ __('Delete Brand') }}
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                {{ __('Are you sure you want to delete this brand? This action cannot be undone.') }}
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="$set('confirmingDeletion', false)">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button wire:click="delete">
                    {{ __('Delete Brand') }}
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>