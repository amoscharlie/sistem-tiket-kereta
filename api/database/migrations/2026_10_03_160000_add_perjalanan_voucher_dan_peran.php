<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('pelanggan', 'admin', 'pengelola'))");
        DB::statement('ALTER TABLE users ALTER COLUMN password DROP NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->string('telepon')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->timestampTz('hp_verified_at')->nullable();
            $table->timestampTz('whatsapp_verified_at')->nullable();
        });

        Schema::create('kode_verifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kanal');
            $table->string('kode', 6);
            $table->timestampTz('kedaluwarsa_at');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE kode_verifikasi ADD CONSTRAINT kode_verifikasi_kanal_check CHECK (kanal IN ('email', 'hp', 'whatsapp'))");

        Schema::create('voucher', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('jenis');
            $table->decimal('nilai', 12, 2);
            $table->unsignedInteger('kuota');
            $table->unsignedInteger('terpakai')->default(0);
            $table->date('berlaku_sampai')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE voucher ADD CONSTRAINT voucher_jenis_check CHECK (jenis IN ('potongan', 'persen'))");

        Schema::table('pesanan', function (Blueprint $table) {
            $table->foreignId('jadwal_pulang_id')->nullable()->constrained('jadwal');
            $table->foreignId('kelas_pulang_id')->nullable()->constrained('kelas');
            $table->foreignId('voucher_id')->nullable()->constrained('voucher');
            $table->decimal('potongan', 12, 2)->default(0);
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('midtrans_order_id')->nullable();
            $table->text('snap_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn(['midtrans_order_id', 'snap_token']);
        });

        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jadwal_pulang_id');
            $table->dropConstrainedForeignId('kelas_pulang_id');
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn('potongan');
        });

        Schema::dropIfExists('voucher');
        Schema::dropIfExists('kode_verifikasi');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telepon', 'google_id', 'hp_verified_at', 'whatsapp_verified_at']);
        });
    }
};
