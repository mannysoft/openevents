<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Wo_Clickfunnel_Event_Purchase', function (Blueprint $table) {
            $table->string('referal_first_name')->nullable()->after('referal_email');
            $table->string('referal_last_name')->nullable()->after('referal_first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Wo_Clickfunnel_Event_Purchase', function (Blueprint $table) {
            $table->dropColumn(['referal_first_name', 'referal_last_name']);
        });
    }
};
