<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_method' => substr($this->faker->creditCardType(), 0, 4),
            'payment_amount' => $this->faker->randomFloat(2, 100, 200),
        ];
    }
}
