<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sales_invoices')
            ->select(['id', 'status', 'gross_total'])
            ->orderBy('id')
            ->chunkById(100, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $paidAmount = round((float) DB::table('sales_invoice_payments')
                        ->where('sales_invoice_id', $invoice->id)
                        ->sum('amount'), 2);

                    $remainingAmount = in_array($invoice->status, ['cancelled', 'returned'], true)
                        ? 0.0
                        : round(max((float) $invoice->gross_total - $paidAmount, 0), 2);

                    $paymentStatus = $paidAmount <= 0
                        ? 'unpaid'
                        : ($paidAmount >= round(max((float) $invoice->gross_total, 0), 2) && (float) $invoice->gross_total > 0
                            ? 'paid'
                            : 'partially_paid');

                    DB::table('sales_invoices')
                        ->where('id', $invoice->id)
                        ->update([
                            'paid_amount' => $paidAmount,
                            'remaining_amount' => $remainingAmount,
                            'payment_status' => $paymentStatus,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No-op: this migration normalizes stored payment summary fields.
    }
};
