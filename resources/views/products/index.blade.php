<x-app-layout>
    @push('scripts')
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
        <style>
            .ql-editor {
                min-height: 150px;
            }
        </style>
    @endpush
    <livewire:product-index />
</x-app-layout>