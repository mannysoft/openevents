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
            $table->string('friend_email')
                ->nullable()
                ->after('email_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
