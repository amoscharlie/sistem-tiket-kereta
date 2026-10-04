<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE penumpang ADD COLUMN nik varchar(16) NULL');
        DB::statement('ALTER TABLE pesanan ADD COLUMN naik_at timestamp with time zone NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE penumpang DROP COLUMN IF EXISTS nik');
        DB::statement('ALTER TABLE pesanan DROP COLUMN IF EXISTS naik_at');
    }
};
