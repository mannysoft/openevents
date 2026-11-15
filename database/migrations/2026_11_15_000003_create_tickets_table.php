<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('quantity_available')->nullable();
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->unsignedSmallInteger('min_per_order')->default(1);
            $table->unsignedSmallInteger('max_per_order')->default(10);
            $table->dateTime('sales_start_at')->nullable();
            $table->dateTime('sales_end_at')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
            $table->index(['event_id', 'is_hidden']);
        });

        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('type', 16)->default('percentage');
            $table->unsignedInteger('value');
            $table->unsignedInteger('quantity_available')->nullable();
            $table->unsignedInteger('quantity_used')->default(0);
            $table->unsignedSmallInteger('min_quantity')->default(1);
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['event_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
        Schema::dropIfExists('tickets');
    }
};
