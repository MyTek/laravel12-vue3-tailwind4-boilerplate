<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class InvoiceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('person.'.$this->invoice->person_id);
    }

    public function broadcastAs(): string
    {
        return 'invoice.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'invoice' => [
                'id' => $this->invoice->id,
                'person_id' => $this->invoice->person_id,
                'total_amount' => $this->invoice->total_amount,
                'paid_amount' => $this->invoice->paid_amount,
                'balance' => $this->invoice->balance,
                'settled' => $this->invoice->settled,
            ],
        ];
    }
}
