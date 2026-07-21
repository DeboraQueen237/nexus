<?php

namespace App\Events;

use App\Models\Poll;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PollVoted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Poll $poll)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('poll.' . $this->poll->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'poll.voted';
    }

    public function broadcastWith(): array
    {
        $this->poll->loadMissing('options');
        $total = $this->poll->options->sum('vote_count');

        return [
            'poll_id' => $this->poll->id,
            'total_votes' => $total,
            'options' => $this->poll->options->map(fn ($option) => [
                'id' => $option->id,
                'vote_count' => $option->vote_count,
                'percentage' => $total > 0 ? round(($option->vote_count / $total) * 100, 1) : 0,
            ]),
        ];
    }
}
