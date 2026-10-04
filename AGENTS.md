# Tiket Kereta — panduan agent

Workspace ini berisi tiga hal. Fitur baru hanya di `api/` (Laravel 13) dan `web/` (Next.js 16, JavaScript, Tailwind 4). Root (`app/`, `routes/`, `resources/`, `artisan`) adalah aplikasi Blade April 2023. Jangan diubah, jangan di-migrate, jangan dijadikan target perintah.

## Perintah

Selalu `cd api` dulu sebelum `php artisan`, `composer`, atau `php artisan test`. `artisan` di root adalah aplikasi lama.

```bash
cd api && php artisan serve          # http://127.0.0.1:8000
cd api && php artisan test           # database tiket_test, bukan tiket
cd web && npm run dev                # http://127.0.0.1:3000
```

PostgreSQL lokal: user `tiket`, kata sandi kosong, database `tiket` dan `tiket_test`. Jangan `migrate:fresh` pada `tiket`. Jangan rotasi `APP_KEY`. Jangan commit kecuali diminta. Jangan commit `.env`.

Next.js di `web/` punya aturan versinya sendiri di `web/AGENTS.md`. Baca docs di `web/node_modules/next/dist/docs/` sebelum memakai API Next yang mungkin sudah berubah.

## Kontrak yang jangan dilanggar

- `GET /` pada API membalas `text/plain`: `{"message":"API is accessible"}`. Jangan kembalikan halaman selamat datang Laravel.
- Logika pemesanan ada di Laravel. Jangan taruh booking di route API Next.js.
- Web menyimpan sesi di `localStorage` key `tiket_sesi` (respons login utuh). Base URL: `NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1`.
- Token Sanctum. Alias middleware `peran`. Daftar akun selalu dipaksa `pelanggan`.
- Admin: dasbor, verifikasi pembayaran, daftar pesanan, pintu. Pengelola: dasbor, stasiun, kereta, jadwal, voucher, laporan. Pengelola tidak menyetujui pembayaran.
- Setelah login, staf masuk ke `/admin`. Situs pelanggan dan portal staf terpisah. Header situs tidak tampil di `/admin`.
- Umur di bawah 3 tahun: bayi, gratis, `kursi_id` null, jumlah bayi tidak boleh melebihi jumlah dewasa. Umur 3+ bayar, satu kursi, NIK 16 digit.
- Harga = jumlah penumpang bayar × harga kelas, dua kaki kalau pulang-pergi.
- Setujui pembayaran hanya jika `bukti_path` ada. Tolak mengembalikan pesanan ke `menunggu_bayar` dan menambah batas 2 jam.
- Batal hanya untuk `menunggu_bayar` dan `menunggu_verifikasi`. Kursi `ditahan` dilepas. Lunas tidak bisa dibatal.
- Cetak tiket (`GET /api/v1/tiket/{kode}`) dan pintu (`POST /api/v1/admin/pintu`) hanya untuk lunas. `naik_at` yang sudah terisi menolak pindai kedua.
- Akar respons pencarian: `data`, `pulang`, `rekomendasi`, `rekomendasi_pulang`. Satu kartu UI = satu jadwal + satu kelas.
- Ikon inline SVG di `web/src/components/Ikon.js`. Jangan menambah paket ikon.
- Lencana hydration “1 Issue” dari `bis_skin_checked` atau `data-cursor-ref` bukan bug aplikasi. Jangan “diperbaiki” di React.
- Kode pesanan (`KA` + 6 karakter) tetap, dengan label “Kode pesanan”.

## Akun demo

`pelanggan@tiket.test`, `admin@tiket.test`, `pengelola@tiket.test`. Kata sandi `password`. Akun demo sudah terverifikasi email, HP, dan WhatsApp. Pesan tiket diblokir sampai ketiga cap waktu itu terisi.

## Tes

`api/tests/Feature/PesanKursiTest.php` memakai `RefreshDatabase` ke `tiket_test` (lihat `api/phpunit.xml`). Seed yang dipakai tes: `TiketSeeder`. Jangan mengandalkan data demo di database `tiket` untuk assertion.
