<x-app-layout>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-semibold mb-6">Create Brand</h2>

                    <form action="{{ route('brands.store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Brand Name</label>
                                <input type="text" name="name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Logo URL</label>
                                <input type="url" name="logo_url"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Primary Color</label>
                                <input type="color" name="primary_color" value="#000000"
                                    class="mt-1 block w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 cursor-pointer">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Secondary Color</label>
                                <input type="color" name="secondary_color" value="#ffffff"
                                    class="mt-1 block w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 cursor-pointer">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_default" value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600">Set as Default Brand</span>
                            </label>
                        </div>

                        <div class="flex justify-end mt-6">
                            <a href="{{ route('brands.index') }}"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded mr-2">Cancel</a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create
                                Brand</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>