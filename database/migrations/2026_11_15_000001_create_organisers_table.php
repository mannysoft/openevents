<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('about')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency_code', 3)->default('GBP');
            $table->string('timezone')->default('Europe/London');
            $table->string('stripe_account_id')->nullable()->index();
            $table->boolean('stripe_charges_enabled')->default(false);
            $table->boolean('stripe_details_submitted')->default(false);
            $table->timestamp('stripe_onboarded_at')->nullable();
            $table->decimal('platform_fee_percent', 5, 2)->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisers');
    }
};
