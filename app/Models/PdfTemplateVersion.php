<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfTemplateVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'pdf_template_id',
        'version',
        'html_content',
        'css_content',
        'created_by',
    ];

    public function template()
    {
        return $this->belongsTo(PdfTemplate::class, 'pdf_template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
