<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param string $action  created|updated|deleted|reordered
     * @param array  $payload task resource (created/updated), {id,status} (deleted) or {tasks:[...]} (reordered)
     */
    public function __construct(
        public readonly int    $projectId,
        public readonly string $action,
        public readonly array  $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("project.{$this->projectId}")];
    }

    public function broadcastAs(): string
    {
        return 'task.changed';
    }

    public function broadcastWith(): array
    {
        return ['action' => $this->action, 'payload' => $this->payload];
    }
}
