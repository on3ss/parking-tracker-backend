<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('occupancy_reports', function (Blueprint $table) {
            $table->dropForeign(['parking_facility_id']);
            $table->dropForeign(['street_parking_id']);

            $table->foreign('parking_facility_id')
                ->references('id')
                ->on('parking_facilities')
                ->restrictOnDelete();

            $table->foreign('street_parking_id')
                ->references('id')
                ->on('street_parkings')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('occupancy_reports', function (Blueprint $table) {
            $table->dropForeign(['parking_facility_id']);
            $table->dropForeign(['street_parking_id']);

            $table->foreign('parking_facility_id')
                ->references('id')
                ->on('parking_facilities')
                ->cascadeOnDelete();

            $table->foreign('street_parking_id')
                ->references('id')
                ->on('street_parkings')
                ->cascadeOnDelete();
        });
    }
};
