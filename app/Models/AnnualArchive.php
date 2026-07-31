<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualArchive extends Model
{
    protected $guarded = [];

    protected $casts = [
        'summary' => 'array',
        'verified_at' => 'datetime',
    ];
}
