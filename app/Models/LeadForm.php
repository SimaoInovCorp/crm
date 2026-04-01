<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LeadForm extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'fields',
        'is_active',
        'embed_token',
    ];

    protected $casts = [
        'fields'    => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (LeadForm $form) {
            if (empty($form->embed_token)) {
                $form->embed_token = Str::uuid()->toString();
            }
        });
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LeadFormSubmission::class);
    }
}
