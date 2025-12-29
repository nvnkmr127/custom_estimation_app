<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Estimate;
use App\Models\User;

class EstimateApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimate_id',
        'user_id',
        'status',
        'comments',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
