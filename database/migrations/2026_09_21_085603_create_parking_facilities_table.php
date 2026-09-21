<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parking_facilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parking_provider_id')
                ->nullable()
                ->constrained('parking_providers')
                ->nullOnDelete();

            $table->foreignId('location_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('type', 30)
                ->default('PUBLIC');

            $table->string('status', 30)
                ->default('ACTIVE');

            $table->unsignedInteger('capacity')->nullable();

            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();

            $table->unsignedInteger('available_spaces')->nullable();

            $table->string('availability_status', 30)
                ->default('UNKNOWN');

            $table->timestampTz('availability_updated_at')->nullable();

            $table->text('description')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('availability_status');
            $table->index('availability_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_facilities');
    }
};
