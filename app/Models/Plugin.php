<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'description',
        'version',
        'is_active',
        'config_schema',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config_schema' => 'array',
        'config' => 'array',
    ];

    public function modules(): HasMany
    {
        return $this->hasMany(PluginModule::class);
    }
}
