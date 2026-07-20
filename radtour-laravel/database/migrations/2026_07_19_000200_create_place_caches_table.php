<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_caches', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('provider_place_id');
            $table->json('payload');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->unique(['provider', 'provider_place_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_caches');
    }
};
