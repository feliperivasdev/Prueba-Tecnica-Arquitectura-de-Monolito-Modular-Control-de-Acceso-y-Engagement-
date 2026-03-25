<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_checkin_view', function (Blueprint $table): void {
            $table->uuid('check_in_id')->primary();
            $table->uuid('user_id');
            $table->uuid('gym_id');
            $table->timestamp('checked_in_at');
            $table->text('quote_text');
            $table->string('quote_author');
            $table->timestamp('quote_assigned_at');
            $table->timestamps();

            $table->index(['user_id', 'checked_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_checkin_view');
    }
};