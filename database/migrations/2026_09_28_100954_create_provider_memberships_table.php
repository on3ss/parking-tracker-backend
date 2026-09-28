<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parking_provider_id')
                ->constrained('parking_providers')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('role')->nullable();

            $table->timestamps();

            $table->unique([
                'parking_provider_id',
                'user_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_memberships');
    }
};