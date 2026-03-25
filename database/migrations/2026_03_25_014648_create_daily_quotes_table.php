<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_quotes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('check_in_id')->unique();
            $table->uuid('user_id');
            $table->text('quote_text');
            $table->string('quote_author');
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->index(['user_id', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_quotes');
    }
};