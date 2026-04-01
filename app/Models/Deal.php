<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLog;
use App\Models\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use HasFactory, HasTenant, HasActivityLog, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'entity_id',
        'person_id',
        'owner_id',
        'title',
        'value',
        'stage',
        'probability',
        'expected_close_date',
        'notes',
        'proposal_path',
        'proposal_sent_at',
    ];

    protected $casts = [
        'value'               => 'decimal:2',
        'probability'         => 'integer',
        'expected_close_date' => 'date',
        'proposal_sent_at'    => 'datetime',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function dealProducts(): HasMany
    {
        return $this->hasMany(DealProduct::class);
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'deal_products')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    public function followUpAutomation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FollowUpAutomation::class);
    }
}
