<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_records', function (Blueprint $table) {
            $table->id();
            $table->string('source', 80);
            $table->string('entity', 80);
            $table->string('legacy_id', 191);
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('status', 40)->default('imported');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->unique(['source', 'entity', 'legacy_id']);
            $table->index(['model_type', 'model_id']);
            $table->index(['entity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_records');
    }
};
