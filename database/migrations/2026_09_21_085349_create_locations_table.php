<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();

            $table->string('locality')->nullable();
            $table->string('administrative_area')->nullable();
            $table->string('postal_code', 20)->nullable();

            $table->char('country_code', 2)->default('IN');

            $table->geography(
                'coordinates',
                subtype: 'POINT',
                srid: 4326,
            );

            $table->softDeletes();
            $table->timestamps();

            $table->index('locality');
        });

        DB::statement(
            'CREATE INDEX locations_coordinates_gist_index
            ON locations USING GIST (coordinates)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};