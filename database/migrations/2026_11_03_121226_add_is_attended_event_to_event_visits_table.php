<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add the 'is_attended_event' column to the event visits table.
     */
    public function up(): void
    {
        Schema::table('Wo_Clickfunnel_Event_visit_logs', function (Blueprint $table) {
            $table->boolean('is_attended_event')
                ->nullable()
                ->after('app_user_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Wo_Clickfunnel_Event_visit_logs', function (Blueprint $table) {
            $table->dropColumn('is_attended_event');
        });
    }
};
