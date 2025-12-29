<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfTemplate extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'name',
        'html_content',
        'css_content',
        'paper_size',
        'orientation',
        'primary_color',
        'secondary_color',
        'font_family',
        'is_active',
        'is_locked',
        'watermark_text',
        'watermark_opacity',
        'is_password_protected',
        'is_default',
    ];

    public function versions()
    {
        return $this->hasMany(PdfTemplateVersion::class)->orderBy('version', 'desc');
    }
}
