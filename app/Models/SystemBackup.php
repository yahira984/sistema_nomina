<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemBackup extends Model
{
    protected $guarded = [];

    protected $appends = ['file_name'];

    protected $casts = [
        'size_bytes' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function getFileNameAttribute(): string
    {
        return basename((string) $this->path);
    }
}
