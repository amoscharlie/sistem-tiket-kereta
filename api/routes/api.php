<?php

use App\Http\Controllers\AdminPembayaranController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\PengelolaController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\VerifikasiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('daftar', [AuthController::class, 'daftar']);
    Route::post('masuk', [AuthController::class, 'masuk']);
    Route::post('midtrans/notifikasi', [PesananController::class, 'notifikasiMidtrans']);

    Route::get('tiket/{kode}', [PesananController::class, 'tiket']);
    Route::get('stasiun', [JadwalController::class, 'stasiun']);
    Route::get('jadwal', [JadwalController::class, 'index']);
    Route::get('jadwal/{jadwal}', [JadwalController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('keluar', [AuthController::class, 'keluar']);
        Route::get('saya', [AuthController::class, 'saya']);
        Route::post('verifikasi/kirim', [VerifikasiController::class, 'kirim']);
        Route::post('verifikasi/konfirmasi', [VerifikasiController::class, 'konfirmasi']);

        Route::get('pesanan', [PesananController::class, 'index']);
        Route::post('pesanan', [PesananController::class, 'simpan']);
        Route::get('pesanan/{pesanan}', [PesananController::class, 'show']);
        Route::post('pesanan/{pesanan}/batal', [PesananController::class, 'batal']);
        Route::post('pesanan/{pesanan}/bukti', [PesananController::class, 'bukti']);
        Route::post('pesanan/{pesanan}/midtrans', [PesananController::class, 'midtrans']);
        Route::post('pesanan/{pesanan}/midtrans/cek', [PesananController::class, 'midtransCek']);

        Route::middleware('peran:admin')->prefix('admin')->group(function () {
            Route::get('ringkas', [AdminPembayaranController::class, 'ringkas']);
            Route::get('pesanan', [AdminPembayaranController::class, 'pesanan']);
            Route::get('pembayaran', [AdminPembayaranController::class, 'index']);
            Route::post('pembayaran/{pembayaran}/setujui', [AdminPembayaranController::class, 'setujui']);
            Route::post('pembayaran/{pembayaran}/tolak', [AdminPembayaranController::class, 'tolak']);
            Route::post('pintu', [AdminPembayaranController::class, 'periksa']);
        });

        Route::middleware('peran:pengelola')->prefix('pengelola')->group(function () {
            Route::get('ringkas', [PengelolaController::class, 'ringkas']);
            Route::get('laporan/perjalanan', [PengelolaController::class, 'laporanPerjalanan']);
            Route::get('laporan/pembayaran', [PengelolaController::class, 'laporanPembayaran']);
            Route::get('master', [PengelolaController::class, 'master']);
            Route::post('stasiun', [PengelolaController::class, 'simpanStasiun']);
            Route::put('stasiun/{stasiun}', [PengelolaController::class, 'ubahStasiun']);
            Route::delete('stasiun/{stasiun}', [PengelolaController::class, 'hapusStasiun']);
            Route::post('kereta', [PengelolaController::class, 'simpanKereta']);
            Route::put('kereta/{kereta}', [PengelolaController::class, 'ubahKereta']);
            Route::delete('kereta/{kereta}', [PengelolaController::class, 'hapusKereta']);
            Route::get('jadwal', [PengelolaController::class, 'jadwal']);
            Route::post('jadwal', [PengelolaController::class, 'simpanJadwal']);
            Route::put('jadwal/{jadwal}', [PengelolaController::class, 'ubahJadwal']);
            Route::delete('jadwal/{jadwal}', [PengelolaController::class, 'hapusJadwal']);
            Route::get('voucher', [PengelolaController::class, 'voucher']);
            Route::post('voucher', [PengelolaController::class, 'simpanVoucher']);
            Route::put('voucher/{voucher}', [PengelolaController::class, 'ubahVoucher']);
            Route::delete('voucher/{voucher}', [PengelolaController::class, 'hapusVoucher']);
        });
    });
});
