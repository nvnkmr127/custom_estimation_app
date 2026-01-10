<x-app-layout>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-semibold mb-6">Create Email Template</h2>

                    <form action="{{ route('email-templates.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Code (Unique Identifier)</label>
                            <input type="text" name="code"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required placeholder="e.g. emails.welcome">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Subject</label>
                            <input type="text" name="subject"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Body (HTML)</label>
                            <textarea name="body_html" rows="10"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                required></textarea>
                            <p class="text-sm text-gray-500 mt-1">Use Blade syntax for variables, e.g. @{{ $name }}</p>
                        </div>

                        <div class="flex justify-end">
                            <a href="{{ route('email-templates.index') }}"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded mr-2">Cancel</a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create
                                Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>