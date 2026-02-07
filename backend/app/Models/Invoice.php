<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'person_id',
    ];

    protected $appends = [
        'total_amount',
        'paid_amount',
        'balance',
        'settled',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function invoicePayments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function getTotalAmountAttribute(): string
    {
        $sum = (float) $this->items()->sum('amount');
        return number_format($sum, 2, '.', '');
    }

    public function getPaidAmountAttribute(): string
    {
        $sum = (float) $this->invoicePayments()->sum('amount');
        return number_format($sum, 2, '.', '');
    }

    public function getBalanceAttribute(): string
    {
        $balance = (float) $this->total_amount - (float) $this->paid_amount;
        return number_format($balance, 2, '.', '');
    }

    public function getSettledAttribute(): bool
    {
        return abs((float) $this->balance) < 0.005;
    }
}
