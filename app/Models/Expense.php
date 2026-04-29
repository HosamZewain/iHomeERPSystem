<?php

namespace App\Models;

use App\Enums\ExpensePaymentStatus;
use App\Enums\ExpenseRecurringFrequency;
use App\Enums\ExpenseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expense_category_id',
        'generated_from_expense_id',
        'title',
        'amount',
        'expense_date',
        'expense_type',
        'recurring_frequency',
        'payment_status',
        'paid_amount',
        'remaining_amount',
        'vendor_name',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'expense_type' => ExpenseType::class,
            'recurring_frequency' => ExpenseRecurringFrequency::class,
            'payment_status' => ExpensePaymentStatus::class,
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            $expense->normalizePaymentFields();
        });

        static::updating(function (Expense $expense) {
            $expense->normalizePaymentFields();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'generated_from_expense_id');
    }

    public function generatedOccurrences(): HasMany
    {
        return $this->hasMany(self::class, 'generated_from_expense_id');
    }

    public function normalizePaymentFields(): void
    {
        $amount = round(max((float) $this->amount, 0), 2);
        $paidAmount = round(max((float) $this->paid_amount, 0), 2);
        $paidAmount = min($paidAmount, $amount);

        $this->paid_amount = $paidAmount;
        $this->remaining_amount = round(max($amount - $paidAmount, 0), 2);
        $this->payment_status = ExpensePaymentStatus::fromAmounts($paidAmount, $amount);
    }

    public function canGenerateNextOccurrence(): bool
    {
        return $this->expense_type === ExpenseType::Recurring
            && $this->recurring_frequency !== null;
    }

    public function nextOccurrenceDate(): ?Carbon
    {
        if (! $this->canGenerateNextOccurrence()) {
            return null;
        }

        $date = $this->expense_date instanceof Carbon
            ? $this->expense_date->copy()
            : Carbon::parse($this->expense_date);

        return match ($this->recurring_frequency) {
            ExpenseRecurringFrequency::Monthly => $date->addMonth(),
            ExpenseRecurringFrequency::Quarterly => $date->addMonths(3),
            ExpenseRecurringFrequency::Yearly => $date->addYear(),
            default => null,
        };
    }

    public function generateNextOccurrence(?User $user = null): self
    {
        if (! $this->canGenerateNextOccurrence()) {
            throw ValidationException::withMessages([
                'expense' => 'لا يمكن توليد فترة جديدة إلا للمصروفات المتكررة.',
            ]);
        }

        $nextDate = $this->nextOccurrenceDate();

        if (! $nextDate) {
            throw ValidationException::withMessages([
                'expense' => 'تعذر تحديد تاريخ الفترة التالية لهذا المصروف.',
            ]);
        }

        $alreadyGenerated = $this->generatedOccurrences()
            ->whereDate('expense_date', $nextDate->toDateString())
            ->exists();

        if ($alreadyGenerated) {
            throw ValidationException::withMessages([
                'expense' => 'تم توليد الفترة التالية لهذا المصروف بالفعل.',
            ]);
        }

        return self::create([
            'expense_category_id' => $this->expense_category_id,
            'generated_from_expense_id' => $this->id,
            'title' => $this->title,
            'amount' => $this->amount,
            'expense_date' => $nextDate->toDateString(),
            'expense_type' => $this->expense_type,
            'recurring_frequency' => $this->recurring_frequency,
            'payment_status' => ExpensePaymentStatus::Unpaid,
            'paid_amount' => 0,
            'remaining_amount' => $this->amount,
            'vendor_name' => $this->vendor_name,
            'notes' => $this->notes,
            'created_by' => $user?->id,
        ]);
    }

    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query
            ->whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate);
    }

    public static function totalForPeriod(string $startDate, string $endDate): float
    {
        return (float) self::query()->betweenDates($startDate, $endDate)->sum('amount');
    }
}
