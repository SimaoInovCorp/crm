<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CalendarEventAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'calendar_event_id',
        'attendee_type',
        'attendee_id',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function attendee(): MorphTo
    {
        return $this->morphTo();
    }
}
