<?php

namespace App\Observers;

use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use App\Services\AuditLogService;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
       AuditLogService::log([
            'action' => 'created_user',
            'target_user_id' => $user->id,
            'payload_diff' => $user->getAttributes(),
        ]);
      if (app()->runningInConsole()) {
            return;
        }
        if ($user->email) {
            // SendWelcomeEmailJob::dispatch($user);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
        $changes = collect($user->getChanges())
            ->except(['updated_at'])
            ->toArray();

        AuditLogService::log([
            'action' => 'updated_user',
            'target_user_id' => $user->id,
            'payload_diff' => $changes
        ]);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
         AuditLogService::log([
            'action' => 'deleted_user',
            'target_user_id' => $user->id,
        ]);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
