<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trail_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_trace_id')->constrained('trail_traces')->cascadeOnDelete();
            $table->string('message');
            $table->json('context')->nullable();
            $table->timestamp('recorded_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_steps');
    }
};
