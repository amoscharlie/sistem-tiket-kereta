<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\pesanController;
use App\Http\Controllers\jadwalController;
use App\Http\Controllers\nominalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('/auth.login');
});

Route::get('dashboard', function () {
    return view('dashboard');
})->name('dashboard')->middleware('auth');

Route::controller(jadwalController::class)->prefix('jadwal')->group((function (){
   Route::get('', 'index')->name('jadwal');
   Route::get('tambah', 'tambah')->name('jadwal.tambah');
   Route::post('tambah', 'simpan')->name('jadwal.tambah.simpan');
   Route::post('sunting/{id}', 'update')->name('jadwal.tambah.update');
   Route::get('sunting/{id}', 'sunting')->name('jadwal.sunting');
   Route::get('hapus/{id}', 'hapus')->name('jadwal.hapus');
}))->middleware('auth');

Route::controller(pesanController::class)->prefix('pesan')->group((function (){
   Route::get('', 'index')->name('pesan');
   Route::get('tambah', 'tambah')->name('pesan.tambah');
   Route::post('tambah', 'simpan')->name('pesan.tambah.simpan');
   Route::post('sunting/{id}', 'update')->name('pesan.tambah.update');
   Route::get('sunting/{id}', 'sunting')->name('pesan.sunting');
   Route::get('hapus/{id}', 'hapus')->name('pesan.hapus');
}))->middleware('auth');

Route::controller(nominalController::class)->prefix('nominal')->group((function (){
    Route::get('', 'index')->name('nominal');
    Route::get('tambah', 'tambah')->name('nominal.tambah');
    Route::post('tambah', 'simpan')->name('nominal.tambah.simpan');
    Route::post('sunting/{id}', 'update')->name('nominal.tambah.update');
    Route::get('sunting/{id}', 'sunting')->name('nominal.sunting');
    Route::get('hapus/{id}', 'hapus')->name('nominal.hapus');
}))->middleware('auth');

Route::get('user', function () {
    return view('user');
})->name('user')->middleware('auth');

Route::get('/logout', function() {
    Auth::logout();
    return view('/login');
});
Auth::routes();


Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
