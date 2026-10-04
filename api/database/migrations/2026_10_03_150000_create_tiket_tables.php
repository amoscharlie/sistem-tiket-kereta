<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skema pemesanan tiket terjadwal.
     *
     * Yang khusus PostgreSQL di file ini:
     * - numeric untuk uang (bukan varchar "200.000")
     * - timestamptz untuk jam keberangkatan
     * - CHECK agar status tidak bisa diisi teks sembarang
     * - unique index sebagian: satu kursi hanya boleh ditahan/terjual sekali
     */
    public function up(): void
    {
        Schema::create('moda', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('stasiun', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 8)->unique();
            $table->string('nama');
            $table->string('kota');
            $table->timestamps();
        });

        Schema::create('kereta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moda_id')->constrained('moda');
            $table->string('kode');
            $table->string('nama');
            $table->timestamps();

            $table->unique(['moda_id', 'kode']);
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kereta_id')->constrained('kereta');
            $table->foreignId('stasiun_asal_id')->constrained('stasiun');
            $table->foreignId('stasiun_tujuan_id')->constrained('stasiun');
            $table->timestampTz('berangkat_at');
            $table->timestampTz('tiba_at');
            $table->timestamps();

            $table->index(['stasiun_asal_id', 'stasiun_tujuan_id', 'berangkat_at']);
        });

        Schema::create('jadwal_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->decimal('harga', 12, 2);
            $table->timestamps();

            $table->unique(['jadwal_id', 'kelas_id']);
        });

        Schema::create('kursi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->string('kode', 8);
            $table->unsignedSmallInteger('baris');
            $table->string('kolom', 2);
            $table->timestamps();

            $table->unique(['jadwal_id', 'kelas_id', 'kode']);
        });

        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 12)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('jadwal_id')->constrained('jadwal');
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->string('status');
            $table->decimal('total', 12, 2);
            $table->timestampTz('batas_bayar_at');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE pesanan ADD CONSTRAINT pesanan_status_check CHECK (status IN ('menunggu_bayar', 'menunggu_verifikasi', 'lunas', 'batal', 'kedaluwarsa'))");

        Schema::create('penumpang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan')->cascadeOnDelete();
            $table->foreignId('kursi_id')->constrained('kursi');
            $table->string('nama');
            $table->string('status');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE penumpang ADD CONSTRAINT penumpang_status_check CHECK (status IN ('ditahan', 'terjual', 'dilepas'))");

        DB::statement("
            CREATE UNIQUE INDEX penumpang_kursi_aktif_unik
            ON penumpang (kursi_id)
            WHERE status IN ('ditahan', 'terjual')
        ");

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan')->cascadeOnDelete();
            $table->string('metode');
            $table->string('status');
            $table->decimal('jumlah', 12, 2);
            $table->string('bukti_path')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT pembayaran_metode_check CHECK (metode IN ('manual', 'midtrans'))");
        DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT pembayaran_status_check CHECK (status IN ('menunggu_bukti', 'menunggu_verifikasi', 'lunas', 'ditolak'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('penumpang');
        Schema::dropIfExists('pesanan');
        Schema::dropIfExists('kursi');
        Schema::dropIfExists('jadwal_kelas');
        Schema::dropIfExists('jadwal');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('kereta');
        Schema::dropIfExists('stasiun');
        Schema::dropIfExists('moda');
    }
};
