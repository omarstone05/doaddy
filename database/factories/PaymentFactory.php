<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Organization;
use App\Models\Customer;
use App\Models\MoneyAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'organization_id' => Organization::factory(),
            'customer_id' => Customer::factory(),
            'payment_number' => 'PAY-' . now()->format('Ymd') . '-' . str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'ZMW',
            'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'payment_method' => $this->faker->randomElement(['cash', 'mobile_money', 'card', 'bank_transfer', 'cheque', 'other']),
            'payment_reference' => 'REF' . $this->faker->numberBetween(100000, 999999),
            'money_account_id' => MoneyAccount::factory(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(function (array $attributes) use ($organization) {
            return [
                'organization_id' => $organization->id,
            ];
        });
    }

    public function forCustomer(Customer $customer): self
    {
        return $this->state(function (array $attributes) use ($customer) {
            return [
                'customer_id' => $customer->id,
                'organization_id' => $customer->organization_id,
            ];
        });
    }

    public function thisMonth(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_date' => $this->faker->dateTimeBetween(now()->startOfMonth(), now()->endOfMonth()),
            ];
        });
    }

    public function lastMonth(): self
    {
        return $this->state(function (array $attributes) {
            $lastMonth = now()->subMonth();
            return [
                'payment_date' => $this->faker->dateTimeBetween($lastMonth->startOfMonth(), $lastMonth->endOfMonth()),
            ];
        });
    }
}
