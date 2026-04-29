<?php

namespace Database\Factories;

use App\Enums\ExpenseRecurringFrequency;
use App\Enums\ExpenseType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100, 5000);
        $paidAmount = fake()->randomElement([0, $amount / 2, $amount]);

        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'generated_from_expense_id' => null,
            'title' => fake()->sentence(3),
            'amount' => $amount,
            'expense_date' => now()->toDateString(),
            'expense_type' => fake()->randomElement([ExpenseType::OneTime->value, ExpenseType::Recurring->value]),
            'recurring_frequency' => fake()->randomElement([null, ExpenseRecurringFrequency::Monthly->value]),
            'payment_status' => null,
            'paid_amount' => $paidAmount,
            'remaining_amount' => round(max($amount - $paidAmount, 0), 2),
            'vendor_name' => fake()->optional()->company(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
