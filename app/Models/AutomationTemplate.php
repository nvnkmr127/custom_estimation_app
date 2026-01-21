<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AutomationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'is_system',
        'structure', // Stores JSON string of the automation blueprint
        'created_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'structure' => 'array', // Automatically cast JSON to array
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
