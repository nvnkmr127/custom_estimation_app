<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookMapper extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'uuid',
        'webhook_inbound_endpoint_id',
        'name',
        'trigger_rules',
        'action_type',
        'action_config',
        'is_active',
    ];

    protected $casts = [
        'trigger_rules' => 'array',
        'action_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function endpoint()
    {
        return $this->belongsTo(WebhookInboundEndpoint::class, 'webhook_inbound_endpoint_id');
    }
}
