<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'perfex_id',
        'property_name',
        'property_address',
        'property_notes',
        'property_type',
        'secondary_email',
        'secondary_phone',
    ];

    public function estimates()
    {
        return $this->hasMany(Estimate::class);
    }
}
