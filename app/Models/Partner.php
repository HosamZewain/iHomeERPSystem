<?php

namespace App\Models;

use App\Enums\CommissionType;
use App\Enums\PartnerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'contact_person',
        'phone',
        'email',
        'address',
        'default_commission_type',
        'default_commission_value',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => PartnerType::class,
            'default_commission_type' => CommissionType::class,
            'default_commission_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(PartnerSettlement::class);
    }

    public function canDelete(): bool
    {
        return ! $this->hasLinkedRecords('sales_invoices', 'partner_id')
            && ! $this->hasLinkedRecords('partner_settlements', 'partner_id');
    }

    private function hasLinkedRecords(string $table, string $column): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, $column)
            && DB::table($table)->where($column, $this->getKey())->exists();
    }
}
