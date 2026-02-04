@include('pdf_templates.form', [
    'title' => 'Create New PDF Template',
    'action' => route('pdf-templates.store'),
    'submitLabel' => 'Create Template'
])