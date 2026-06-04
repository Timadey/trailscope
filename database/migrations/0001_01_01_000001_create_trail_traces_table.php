<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trail_traces', function (Blueprint $table) {
            $table->id();
            $table->string('trace_id')->unique();
            $table->string('method')->nullable()->index();
            $table->string('path')->nullable()->index();
            $table->string('route_name')->nullable()->index();
            $table->string('controller')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('owner_type')->nullable()->index();
            $table->string('owner_id')->nullable()->index();
            $table->string('owner_label')->nullable();
            $table->string('identity_source')->nullable();
            $table->string('identity_confidence')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->json('exception')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_traces');
    }
};
