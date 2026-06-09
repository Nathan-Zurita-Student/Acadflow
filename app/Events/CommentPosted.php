<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentPosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int   $taskId,
        public readonly int   $projectId,
        public readonly array $comment,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("task.{$this->taskId}")];
    }

    public function broadcastAs(): string
    {
        return 'comment.posted';
    }

    public function broadcastWith(): array
    {
        return $this->comment;
    }
}
