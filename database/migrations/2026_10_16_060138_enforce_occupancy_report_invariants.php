<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE occupancy_reports
            ADD CONSTRAINT occupancy_reports_counts_required_check
            CHECK (
                occupied_spaces IS NOT NULL
                AND available_spaces IS NOT NULL
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE occupancy_reports
            DROP CONSTRAINT occupancy_reports_counts_required_check
        SQL);
    }
};
