<x-app-layout>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold">Edit Template: {{ $emailTemplate->name }}</h2>
                        <button type="button" onclick="previewTemplate()"
                            class="px-3 py-1 bg-green-600 text-white rounded text-sm">Preview</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <form action="{{ route('email-templates.update', $emailTemplate) }}" method="POST" id="editForm">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="name" value="{{ $emailTemplate->name }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Code</label>
                                <input type="text" name="code" value="{{ $emailTemplate->code }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Subject</label>
                                <input type="text" name="subject" value="{{ $emailTemplate->subject }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Body (HTML)</label>
                                <textarea name="body_html" id="body_html" rows="15"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                    required>{{ $emailTemplate->body_html }}</textarea>
                            </div>

                            <div class="flex justify-end">
                                <a href="{{ route('email-templates.index') }}"
                                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded mr-2">Cancel</a>
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update
                                    Template</button>
                            </div>
                        </form>

                        <!-- Preview Column -->
                        <div class="border rounded p-4 bg-gray-50 h-full">
                            <h3 class="font-semibold mb-2 text-sm text-gray-600">Live Preview</h3>
                            <div id="preview-container"
                                class="bg-white border p-4 h-[500px] overflow-y-auto prose max-w-none">
                                <p class="text-gray-400 text-center mt-10">Click Preview to generate...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function previewTemplate() {
            const body = document.getElementById('body_html').value;
            const container = document.getElementById('preview-container');

            container.innerHTML = '<p class="text-gray-400 text-center mt-10">Loading...</p>';

            try {
                const response = await fetch('{{ route("email-templates.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        body_html: body,
                        variables: {
                            name: 'John Doe',
                            estimate_number: 'EST-12345',
                            total_amount: '$1,200.00',
                            view_url: '#',
                            brand_name: 'Acme Corp',
                            brand_color_primary: '#000000'
                        }
                    })
                });

                const data = await response.json();
                container.innerHTML = data.html;
            } catch (error) {
                container.innerHTML = '<p class="text-red-500 text-center mt-10">Error generating preview</p>';
                console.error(error);
            }
        }
    </script>
</x-app-layout>