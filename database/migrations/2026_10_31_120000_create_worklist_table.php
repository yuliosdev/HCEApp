<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worklist', function (Blueprint $table): void {
            $table->id();
            $table->string('accession_number', 20)->unique();
            $table->string('link', 100);
            $table->timestamps();
        });

        DB::unprepared(<<<'SQL'
DROP PROCEDURE IF EXISTS sp_worklist_upsert_link
SQL
        );

        DB::unprepared(<<<'WORKLIST_SQL'
CREATE PROCEDURE sp_worklist_upsert_link(
    IN p_accession VARCHAR(20),
    IN p_link VARCHAR(100)
) SQL SECURITY DEFINER
MODIFIES SQL DATA
BEGIN
    IF p_accession IS NULL OR TRIM(p_accession) = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'AccessionNumber vacío';
    END IF;

    INSERT INTO worklist (accession_number, link)
    VALUES (TRIM(p_accession), p_link)
    ON DUPLICATE KEY UPDATE
        link = VALUES(link);

    SELECT ROW_COUNT() AS resultado;
END
WORKLIST_SQL
        );
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP PROCEDURE IF EXISTS sp_worklist_upsert_link
SQL
        );

        Schema::dropIfExists('worklist');
    }
};
