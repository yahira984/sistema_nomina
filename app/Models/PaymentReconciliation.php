<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentReconciliation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'results' => 'array',
    ];
}
