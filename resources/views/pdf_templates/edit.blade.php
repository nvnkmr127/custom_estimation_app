@include('pdf_templates.form', [
    'title' => 'Edit PDF Template: ' . $pdfTemplate->name,
    'action' => route('pdf-templates.update', $pdfTemplate),
    'submitLabel' => 'Update Template',
    'method' => 'PUT',
    'header_actions' => view('pdf_templates.partials.edit-header-actions')->render()
])