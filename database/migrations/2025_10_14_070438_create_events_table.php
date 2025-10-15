<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->integer('max_capacity');
            $table->integer('current_attendees')->default(0);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('start_time');
            $table->index(['start_time', 'end_time']);
        });
    }

    /*
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};