<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PaymentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('person.'.$this->payment->person_id);
    }

    public function broadcastAs(): string
    {
        return 'payment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'person_id' => $this->payment->person_id,
        ];
    }
}
