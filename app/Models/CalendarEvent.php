<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CalendarEvent extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'owner_id',
        'entity_id',
        'person_id',
        'deal_id',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'all_day',
        'notify_person',
        'eventable_type',
        'eventable_id',
    ];

    protected $casts = [
        'start_at'      => 'datetime',
        'end_at'        => 'datetime',
        'all_day'       => 'boolean',
        'notify_person' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(CalendarEventAttendee::class);
    }

    public function calendarEventProducts(): HasMany
    {
        return $this->hasMany(CalendarEventProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'calendar_event_products')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }
}
