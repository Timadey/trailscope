<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trail_signed_links', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash')->unique();
            $table->string('scope')->default('dashboard');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_signed_links');
    }
};
