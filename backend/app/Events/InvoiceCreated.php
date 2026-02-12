<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class InvoiceCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('person.'.$this->invoice->person_id);
    }

    public function broadcastAs(): string
    {
        return 'invoice.created';
    }

    public function broadcastWith(): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'person_id' => $this->invoice->person_id,
        ];
    }
}
