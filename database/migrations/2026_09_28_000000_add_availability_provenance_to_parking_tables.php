<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['parking_facilities', 'street_parkings'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('availability_source', 30)
                    ->nullable()
                    ->after('availability_status');

                $blueprint->foreignId('availability_report_id')
                    ->nullable()
                    ->after('availability_source')
                    ->constrained('occupancy_reports')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['parking_facilities', 'street_parkings'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('availability_report_id');
                $blueprint->dropColumn('availability_source');
            });
        }
    }
};
