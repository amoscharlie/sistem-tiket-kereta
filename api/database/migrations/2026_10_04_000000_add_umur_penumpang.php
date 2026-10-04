<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE penumpang ALTER COLUMN kursi_id DROP NOT NULL');
        DB::statement('ALTER TABLE penumpang ADD COLUMN umur smallint NULL');
        DB::statement('ALTER TABLE penumpang ADD CONSTRAINT penumpang_umur_check CHECK (umur IS NULL OR (umur >= 0 AND umur <= 120))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE penumpang DROP CONSTRAINT IF EXISTS penumpang_umur_check');
        DB::statement('ALTER TABLE penumpang DROP COLUMN IF EXISTS umur');
    }
};
