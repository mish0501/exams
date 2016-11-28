<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StudentConnected
{
    use InteractsWithSockets, SerializesModels;

    public $code;
    public $name;
    public $lastname;
    public $number;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($data)
    {
      $this->code = $data['code'];
      $this->name = $data['name'];
      $this->lastname = $data['lastname'];
      $this->number = $data['number'];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('testroom.' + $this->code);
    }
}
