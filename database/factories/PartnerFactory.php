<?php

namespace Database\Factories;

use App\Enums\CommissionType;
use App\Enums\PartnerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partner>
 */
class PartnerFactory extends Factory
{
    public function definition(): array
    {
        $commissionType = fake()->randomElement(CommissionType::cases());

        return [
            'name' => fake()->unique()->company(),
            'type' => fake()->randomElement(PartnerType::cases())->value,
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'address' => fake()->optional()->address(),
            'default_commission_type' => $commissionType->value,
            'default_commission_value' => $commissionType === CommissionType::Percentage
                ? fake()->randomFloat(2, 1, 15)
                : fake()->randomFloat(2, 500, 5000),
            'notes' => fake()->optional()->sentence(),
            'is_active' => fake()->boolean(85),
        ];
    }
}
