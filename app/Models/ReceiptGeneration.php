<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptGeneration extends Model
{
    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'metadata' => 'array',
    ];
}
