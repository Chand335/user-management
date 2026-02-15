<?php
namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public static function log(array $data): void
    {
        AuditLog::create([
            'actor_user_id'   => self::actorId(),
            'action'          => $data['action'],
            'target_user_id'  => $data['target_user_id'] ?? null,
            'payload_diff'    => $data['payload_diff'] ?? null,
            'ip_address'      => request()?->ip(),
            'user_agent'      => request()?->userAgent(),
        ]);
    }

    protected static function actorId(): ?int
    {
        return Auth::id();
    }
}
