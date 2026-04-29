<?php

namespace Tests\Feature;

use App\Enums\ExpensePaymentStatus;
use App\Enums\ExpenseRecurringFrequency;
use App\Enums\ExpenseType;
use App\Livewire\Expenses\ExpenseCategoryList;
use App\Livewire\Expenses\ExpenseList;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_categories_can_be_created_and_edited(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ExpenseCategoryList::class)
            ->call('create')
            ->set('name', 'إيجار المعرض')
            ->set('is_active', true)
            ->set('notes', 'مصروف تشغيلي شهري')
            ->call('save')
            ->assertHasNoErrors();

        $category = ExpenseCategory::query()->firstOrFail();

        $this->assertSame('إيجار المعرض', $category->name);
        $this->assertTrue($category->is_active);

        Livewire::actingAs($user)
            ->test(ExpenseCategoryList::class)
            ->call('edit', $category->id)
            ->set('name', 'إيجار وصيانة')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('إيجار وصيانة', $category->refresh()->name);
    }

    public function test_expenses_can_be_created_and_recurring_occurrence_can_be_generated(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = ExpenseCategory::factory()->create(['name' => 'رواتب']);

        Livewire::actingAs($user)
            ->test(ExpenseList::class)
            ->call('create')
            ->set('expense_category_id', (string) $category->id)
            ->set('title', 'رواتب شهر أبريل')
            ->set('amount', '15000')
            ->set('expense_date', '2026-04-01')
            ->set('expense_type', ExpenseType::Recurring->value)
            ->set('recurring_frequency', ExpenseRecurringFrequency::Monthly->value)
            ->set('paid_amount', '5000')
            ->set('vendor_name', 'طاقم المعرض')
            ->call('save')
            ->assertHasNoErrors();

        $expense = Expense::query()->firstOrFail();

        $this->assertSame(ExpensePaymentStatus::PartiallyPaid, $expense->payment_status);
        $this->assertEquals(10000.0, (float) $expense->remaining_amount);

        Livewire::actingAs($user)
            ->test(ExpenseList::class)
            ->call('generateNextOccurrence', $expense->id)
            ->assertHasNoErrors();

        $nextExpense = Expense::query()->whereKeyNot($expense->id)->firstOrFail();

        $this->assertSame('2026-05-01', $nextExpense->expense_date->toDateString());
        $this->assertSame($expense->id, $nextExpense->generated_from_expense_id);
        $this->assertSame(ExpensePaymentStatus::Unpaid, $nextExpense->payment_status);
    }
}
