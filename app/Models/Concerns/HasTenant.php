<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTenant
{
    public static function bootHasTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (app()->bound('current.tenant')) {
                $query->where((new static)->getTable() . '.tenant_id', app('current.tenant')->id);
            }
        });

        static::creating(function ($model) {
            if (app()->bound('current.tenant') && empty($model->tenant_id)) {
                $model->tenant_id = app('current.tenant')->id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
