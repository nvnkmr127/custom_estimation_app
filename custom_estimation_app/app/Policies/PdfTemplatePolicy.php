<?php

namespace App\Policies;

use App\Models\PdfTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PdfTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasPermission('view_pdf_templates');
    }

    public function view(User $user, PdfTemplate $pdfTemplate)
    {
        return $user->hasPermission('view_pdf_templates');
    }

    public function create(User $user)
    {
        return $user->hasPermission('manage_pdf_templates');
    }

    public function update(User $user, PdfTemplate $pdfTemplate)
    {
        if (! $user->hasPermission('manage_pdf_templates')) {
            return false;
        }

        // If locked, only Super Admin can edit
        if ($pdfTemplate->is_locked && $user->role !== 'super_admin') {
            return false;
        }

        return true;
    }

    public function delete(User $user, PdfTemplate $pdfTemplate)
    {
        if (! $user->hasPermission('manage_pdf_templates')) {
            return false;
        }

        // If locked, only Super Admin can delete
        if ($pdfTemplate->is_locked && $user->role !== 'super_admin') {
            return false;
        }

        return true;
    }
}
