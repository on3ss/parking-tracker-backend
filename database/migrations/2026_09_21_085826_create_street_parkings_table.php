<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('street_parkings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parking_provider_id')
                ->nullable()
                ->constrained('parking_providers')
                ->nullOnDelete();

            $table->foreignId('location_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name')->nullable();

            $table->string('road_name')->nullable();

            $table->string('side', 20)
                ->nullable();

            $table->string('parking_type', 30)
                ->default('CURBSIDE');

            $table->string('status', 30)
                ->default('ACTIVE');

            $table->unsignedInteger('capacity')->nullable();

            $table->unsignedInteger('available_spaces')->nullable();

            $table->string('availability_status', 30)
                ->default('UNKNOWN');

            $table->timestampTz('availability_updated_at')->nullable();

            $table->text('description')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'parking_type']);
            $table->index('availability_status');
            $table->index('availability_updated_at');
        });

        /*
         * A street parking location is potentially a road segment,
         * not just a point.
         */
        DB::statement(
            'ALTER TABLE street_parkings
             ADD COLUMN geometry geometry(LineString, 4326)'
        );

        DB::statement(
            'CREATE INDEX street_parkings_geometry_gist_index
             ON street_parkings USING GIST (geometry)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('street_parkings');
    }
};
