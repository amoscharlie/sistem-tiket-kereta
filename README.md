# Tiket Kereta

Situs pemesanan tiket kereta. Pelanggan mencari jadwal, memilih kelas dan kursi, lalu bayar. Admin memeriksa bukti bayar dan memindai tiket di pintu. Pengelola mengurus stasiun, kereta, jadwal, voucher, dan laporan.

Ada dua aplikasi baru, plus aplikasi lama yang dibiarkan apa adanya:

| Folder | Isi |
| --- | --- |
| `api/` | REST API Laravel 13 + PostgreSQL. Ini backend yang nanti bisa dipakai web, React Native, atau Flutter. |
| `web/` | Situs Next.js. Hanya tampilan. Semua pemesanan lewat API. |
| root (`app/`, `routes/`, `resources/`) | Aplikasi Blade April 2023. Jangan diubah dan jangan dipakai untuk fitur baru. |

## Menjalankan di komputer sendiri

Yang perlu terpasang: PHP 8.3+, Composer, Node.js 22, PostgreSQL 17.

Database lokal memakai user `tiket` tanpa kata sandi (auth trust). Buat sekali:

```bash
createuser tiket
createdb -O tiket tiket
createdb -O tiket tiket_test
```

API:

```bash
cd api
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Perintah `artisan` harus dijalankan dari dalam `api/`. Dari root repo, `artisan` milik aplikasi Blade lama.

Web, di terminal lain:

```bash
cd web
npm install
npm run dev
```

- Situs: http://127.0.0.1:3000
- API: http://127.0.0.1:8000
- Akar API membalas teks biasa: `{"message":"API is accessible"}`

Akun demo, kata sandi semuanya `password`:

| Email | Peran |
| --- | --- |
| pelanggan@tiket.test | pelanggan |
| admin@tiket.test | verifikasi bayar, lihat pesanan, pintu |
| pengelola@tiket.test | stasiun, kereta, jadwal, voucher, laporan |

Login Google dan Midtrans sandbox butuh kunci di `api/.env` (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`). Tanpa kunci, transfer manual dengan unggah bukti tetap jalan.

Melihat data: `psql -U tiket -d tiket`, lalu `\dt`.

## Aturan pemesanan yang penting

- Umur di bawah 3 tahun gratis dan tidak dapat kursi. Umur 3 tahun ke atas beli satu tiket dan satu kursi, plus NIK 16 digit.
- Satu kartu jadwal = satu kelas (Ekonomi atau Eksekutif).
- Pesanan menunggu bayar bisa dibatalkan. Yang sudah lunas tidak bisa.
- Cetak tiket dan pindai di pintu hanya untuk status lunas.
- Admin menyetujui pembayaran hanya jika pelanggan sudah mengunggah bukti.

## CI/CD

CI (Continuous Integration) artinya setiap kali kode naik ke GitHub, mesin GitHub menjalankan tes sendiri. CD (Continuous Deployment) artinya kalau tes itu lulus, kode otomatis dipasang ke server. Proyek ini sudah punya CI. CD menyusul setelah ada server tujuan.

File pipelinenya: [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

Dua pekerjaan berjalan bersamaan:

1. **api** — menyalakan PostgreSQL 17, `composer install`, lalu `php artisan test` di database `tiket_test`. Database lokal `tiket` tidak tersentuh.
2. **web** — `npm ci`, `npm run lint`, lalu `npm run build`.

### Menyalakannya

Repo ini belum dihubungkan ke GitHub. Sekali saja:

```bash
git init
git add .
git commit -m "Siapkan Tiket Kereta dan pipeline CI"
```

Buat repositori kosong di GitHub, lalu:

```bash
git remote add origin https://github.com/NAMA-KAMU/tiket.git
git push -u origin main
```

Buka tab **Actions** di repositori itu. Push pertama memicu pipeline. Tombol **Run workflow** juga menjalankannya tanpa commit baru (`workflow_dispatch` di file YAML).

Centang hijau = tes API lulus dan situs berhasil dibuild. Centang merah = buka job yang gagal, baca log paling bawah, perbaiki, push lagi.

### Latihan membaca pipeline

Baca `ci.yml` dari atas ke bawah, satu blok:

- `on` — kapan pipeline jalan (push, pull request, atau tombol manual).
- `jobs` — pekerjaan yang boleh jalan bareng. `api` dan `web` tidak saling menunggu.
- `services.postgres` — database sementara, hilang setelah job selesai.
- `steps` — perintah berurutan di dalam satu job. Step yang gagal menghentikan job itu.

Coba ini supaya kelihatan bedanya hijau dan merah: ubah satu assertion di `api/tests/Feature/PesanKursiTest.php` supaya salah, push, lihat Actions merah, kembalikan, push lagi. Itu putaran CI yang akan kamu pakai setiap fitur.

### Kalau nanti mau CD

Tambah job baru di workflow yang sama, dengan `needs: [api, web]`, dan hanya pada branch `main`. Job itu yang mengunggah ke server (SSH, Docker, atau Vercel untuk `web/`). Jangan taruh kata sandi di file YAML. Simpan di **Settings → Secrets and variables → Actions**, lalu baca dengan `${{ secrets.NAMA_SECRET }}`.
