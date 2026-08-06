<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sidebar_collapsed' => 'boolean',
        'saved_filters' => 'array',
        'quick_access' => 'array',
        'onboarding_completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
