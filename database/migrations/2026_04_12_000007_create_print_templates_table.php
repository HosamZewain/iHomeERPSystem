<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 120)->unique();
            $table->string('document_type', 40)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'is_active', 'is_default']);
            $table->index(['document_type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_templates');
    }
};
