<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param string $status delivered|read
     */
    public function __construct(
        public readonly int    $taskId,
        public readonly int    $commentId,
        public readonly string $status,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("task.{$this->taskId}")];
    }

    public function broadcastAs(): string
    {
        return 'comment.status';
    }

    public function broadcastWith(): array
    {
        return ['comment_id' => $this->commentId, 'status' => $this->status];
    }
}
