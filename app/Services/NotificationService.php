<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notify(int|User $user, string $type, string $title, string $message, array $data = []): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    public function notifyMany(Collection|array $users, string $type, string $title, string $message, array $data = []): void
    {
        foreach ($users as $user) {
            $userId = $user instanceof User ? $user->id : $user;
            Notification::create([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'data'    => $data,
            ]);
        }
    }
}
