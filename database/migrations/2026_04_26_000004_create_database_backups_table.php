<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('original_file_name')->nullable();
            $table->string('file_path')->unique();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('source_type', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('restored_at')->nullable();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};
