<?php

namespace App\Events;

use App\OdinErrorLogging;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EmailDropped
{
    use InteractsWithSockets, SerializesModels;
    public $appErrors;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(OdinErrorLogging $appErrors)
    {
        $this->appErrors = $appErrors;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
