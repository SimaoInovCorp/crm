<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'url',
        'events',
        'secret',
        'is_active',
    ];

    protected $casts = [
        'events'    => 'array',
        'is_active' => 'boolean',
    ];
}
