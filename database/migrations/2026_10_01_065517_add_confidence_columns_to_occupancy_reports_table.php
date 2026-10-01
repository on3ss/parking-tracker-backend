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
        Schema::table('occupancy_reports', function (Blueprint $table) {
            $table->dropColumn('confidence');
            $table->decimal('reported_confidence', 5, 4)->nullable()->after('confidence');
            $table->decimal('computed_confidence', 5, 4)->nullable()->after('reported_confidence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('occupancy_reports', function (Blueprint $table) {
            $table->dropColumn('reported_confidence');
            $table->dropColumn('computed_confidence');
            $table->decimal('confidence', 5, 4)->nullable();
        });
    }
};
