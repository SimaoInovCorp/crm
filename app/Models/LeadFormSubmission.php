<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFormSubmission extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'lead_form_id',
        'tenant_id',
        'data',
        'ip',
        'origin',
        'processed',
        'deal_id',
    ];

    protected $casts = [
        'data'      => 'array',
        'processed' => 'boolean',
    ];

    public function leadForm(): BelongsTo
    {
        return $this->belongsTo(LeadForm::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
