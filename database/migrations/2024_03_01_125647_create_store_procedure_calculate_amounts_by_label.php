<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $query = "
        CREATE PROCEDURE CalculateStatsWalletsLabel(
            IN userId INT,
            IN inMonth INT,
            IN inYear INT
        )
        BEGIN
            SELECT
            e.workspace_id,
            MONTH(e.date_time) AS month,
            YEAR(e.date_time) AS year,
            COALESCE(SUM(CASE WHEN e.type IN ('incoming', 'debit') AND e.amount > 0 THEN e.amount ELSE 0 END), 0) AS incoming,
            COALESCE(SUM(CASE WHEN e.type IN ('expenses', 'debit') AND e.amount < 0 THEN e.amount ELSE 0 END), 0) AS expenses,
            GROUP_CONCAT(DISTINCT l.name) AS tags,
            GROUP_CONCAT(DISTINCT l.id) AS tags_id
        FROM
            entries AS e
        LEFT JOIN
            entry_labels AS el ON el.entry_id = e.id
        LEFT JOIN
            labels AS l ON l.id = el.labels_id
        WHERE
            e.deleted_at IS NULL
            AND e.confirmed = 1
            AND e.planned = 0
            AND e.exclude_from_stats = 0
            AND e.workspace_id = userId
            AND MONTH(e.date_time) = inMonth
            AND YEAR(e.date_time) = inYear
        GROUP BY
            e.workspace_id, MONTH(e.date_time), YEAR(e.date_time)
        ORDER BY
            year, month;
        END;
        
        ";
        DB:: statement($query);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB:: statement('DROP PROCEDURE CalculateStatsWalletsLabel;');
    }
};
