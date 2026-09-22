<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('favorable_type');
            $table->unsignedBigInteger('favorable_id');

            $table->timestamps();

            $table->unique([
                'user_id',
                'favorable_type',
                'favorable_id',
            ]);

            $table->index([
                'favorable_type',
                'favorable_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};