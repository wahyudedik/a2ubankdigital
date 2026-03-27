<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
