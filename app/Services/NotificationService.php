<?php

namespace App\Services;

use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notify(int|User $user, string $type, string $title, string $message, array $data = []): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'data'    => $data,
        ]);

        try {
            broadcast(new NotificationSent($userId, $notification))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed: ' . $e->getMessage());
        }

        return $notification;
    }

    public function notifyMany(Collection|array $users, string $type, string $title, string $message, array $data = []): void
    {
        foreach ($users as $user) {
            $userId = $user instanceof User ? $user->id : $user;
            $notification = Notification::create([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'data'    => $data,
            ]);
            try {
                broadcast(new NotificationSent($userId, $notification))->toOthers();
            } catch (\Throwable $e) {
                \Log::warning('Broadcast failed: ' . $e->getMessage());
            }
        }
    }
}
