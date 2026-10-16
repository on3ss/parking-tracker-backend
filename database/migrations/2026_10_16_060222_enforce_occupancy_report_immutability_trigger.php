<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        DROP TRIGGER IF EXISTS occupancy_reports_immutable
        ON occupancy_reports;

        DROP FUNCTION IF EXISTS prevent_occupancy_report_mutation();

        CREATE FUNCTION prevent_occupancy_report_mutation()
        RETURNS trigger AS $$
        BEGIN
            RAISE EXCEPTION
                'Occupancy reports are immutable observations';
        END;
        $$ LANGUAGE plpgsql;

        CREATE TRIGGER occupancy_reports_immutable
        BEFORE UPDATE OR DELETE ON occupancy_reports
        FOR EACH ROW
        EXECUTE FUNCTION prevent_occupancy_report_mutation();
    SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
        DROP TRIGGER IF EXISTS occupancy_reports_immutable
        ON occupancy_reports;

        DROP FUNCTION IF EXISTS prevent_occupancy_report_mutation();
    SQL);
    }

};
