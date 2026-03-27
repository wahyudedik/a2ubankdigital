<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs';

    protected $fillable = [
        'level',
        'message',
        'context',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime'
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
        });
    }
}
