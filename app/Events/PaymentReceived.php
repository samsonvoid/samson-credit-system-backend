<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;
    public $message;

    public function __construct($payment)
    {
        $this->payment = $payment;
        $this->message = "Malipo mapya ya TZS " . number_format($payment->amount_paid) . " yamepokelewa!";
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('payments'),
        ];
    }

    public function broadcastAs()
    {
        return 'payment.received';
    }
}
