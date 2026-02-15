<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    //
    protected $fillable = [
        'actor_user_id',
        'action',
        'target_user_id',
        'payload_diff',
        'ip_address',
        'user_agent',
    ];
    protected $casts = [
        'payload_diff' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
