<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // Every membership must have a role; default to the least
            // privileged so an accidentally-missing role never grants
            // more access than intended.
            $table->string('role')->default('viewer');

            $table->timestamps();

            // Composite unique keyed on user_id first so the
            // canAccessTenant() lookup (where user_id = ?) hits the
            // index directly instead of scanning.
            $table->unique(
                ['user_id', 'parking_provider_id'],
                'provider_memberships_user_provider_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_memberships');
    }
};
