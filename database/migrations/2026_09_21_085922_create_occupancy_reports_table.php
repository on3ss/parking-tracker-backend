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
        Schema::create('occupancy_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parking_facility_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('street_parking_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('source', 30);

            $table->unsignedInteger('occupied_spaces')->nullable();
            $table->unsignedInteger('available_spaces')->nullable();

            $table->decimal('confidence', 5, 4)->nullable();

            $table->timestampTz('reported_at');

            $table->timestamps();

            $table->index([
                'parking_facility_id',
                'reported_at',
            ]);

            $table->index([
                'street_parking_id',
                'reported_at',
            ]);

            $table->index([
                'source',
                'reported_at',
            ]);
        });

        /*
         * Exactly one parking target must be specified.
         */
        DB::statement(
            'ALTER TABLE occupancy_reports
             ADD CONSTRAINT occupancy_reports_single_target_check
             CHECK (
                 (parking_facility_id IS NOT NULL AND street_parking_id IS NULL)
                 OR
                 (parking_facility_id IS NULL AND street_parking_id IS NOT NULL)
             )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occupancy_reports');
    }
};
