<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Add Client</h1>
            <p class="mt-2 text-sm text-slate-500">Create a new client record.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('clients.index') }}"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to Clients
            </a>
        </div>
    </div>

    <form action="{{ route('clients.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Client Information</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-900">Name *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm @error('name') ring-red-300 @enderror">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="company" class="block text-sm font-medium text-gray-900">Company</label>
                            <input type="text" name="company" id="company" value="{{ old('company') }}"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-900">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm @error('email') ring-red-300 @enderror">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="secondary_email" class="block text-sm font-medium text-gray-900">Alternative
                                Email</label>
                            <input type="email" name="secondary_email" id="secondary_email"
                                value="{{ old('secondary_email') }}"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-900">Phone</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>

                        <div>
                            <label for="secondary_phone" class="block text-sm font-medium text-gray-900">Alternative
                                Phone</label>
                            <input type="text" name="secondary_phone" id="secondary_phone"
                                value="{{ old('secondary_phone') }}"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Property Details & Address</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        <div>
                            <label for="property_name" class="block text-sm font-medium text-gray-900">Property
                                Name</label>
                            <input type="text" name="property_name" id="property_name"
                                value="{{ old('property_name') }}"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>

                        <div>
                            <label for="property_type" class="block text-sm font-medium text-gray-900">Property
                                Type</label>
                            <input type="text" name="property_type" id="property_type"
                                value="{{ old('property_type') }}" placeholder="e.g. 2BHK, Villa"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-900">Street Address</label>
                            <textarea name="address" id="address" rows="2"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">{{ old('address') }}</textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="property_address" class="block text-sm font-medium text-gray-900">Property
                                Address (if different from above)</label>
                            <textarea name="property_address" id="property_address" rows="2"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">{{ old('property_address') }}</textarea>
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-900">City</label>
                            <input type="text" name="city" id="city" value="{{ old('city') }}"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>
                    </div>
                </div>

                <!-- Notes Section Removed as requested -->

                <div class="flex items-center justify-end gap-x-6">
                    <a href="{{ route('clients.index') }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                    <button type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Create Client
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>