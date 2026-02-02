<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookInboundEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'is_active',
        'secret',
        'signature_header',
        'ip_whitelist',
    ];

    public function mappers()
    {
        return $this->hasMany(WebhookMapper::class);
    }
}
