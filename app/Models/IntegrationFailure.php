<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationFailure extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}
