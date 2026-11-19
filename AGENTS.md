# AGENTS.md — Aturan untuk agen pengembang

Proyek: **RME Keperawatan Kamar Operasi (OKA)**. Baca `README.md` lalu seluruh `docs/*.md` sebelum menulis kode.

## Aturan utama
1. **Sumber kebenaran** isi form dan alur: `docs/01..05` dan `docs/referensi/`. Jangan mengarang field medis. Jika ada konflik, urutan prioritas: `docs/` (keputusan terbaru) > RME OKA > SKPL.
2. **Jangan menambah fitur di luar scope** (`docs/00-PRD.md` bagian "Di luar scope"). Jika menurut Anda ada yang kurang, tulis di `docs/10-catatan-agen.md` dan tanyakan ke pengguna, jangan langsung dibuat.
3. **Kerjakan hanya fase yang diminta** dari `docs/08-rencana-implementasi.md`. Setelah selesai, centang "Definisi selesai", jalankan test, lalu berhenti.
4. **Selalu gunakan versi stabil terbaru** dari Laravel, Inertia, React, Tailwind, Pest, dan paket lain, serta ikuti dokumentasi resmi terbaru dan best practice saat ini. Sebelum memakai API sebuah paket, baca dokumentasi resminya, jangan mengandalkan ingatan. Catat versi yang dipakai di `docs/07-arsitektur-tech-stack.md`.
5. **Bahasa:** UI, label, pesan validasi, dan seed data dalam **Bahasa Indonesia**. Kode (nama class, variabel, tabel, kolom) dalam **Bahasa Inggris**.
6. **Keamanan data klinis:** semua route di balik autentikasi; otorisasi lewat Policy (bukan hanya menyembunyikan menu); validasi lewat Form Request; tidak ada data pasien asli di repo/seed; jangan log data pasien ke file log.
7. **Audit:** setiap create/update/delete pada data klinis wajib tercatat (siapa, kapan, nilai lama, nilai baru). Lihat `docs/06-non-fungsional.md`.
8. **Test:** setiap fitur baru disertai test Pest (feature test untuk hak akses dan alur utama). Jangan menyatakan selesai jika test gagal.
9. **Hosting berbayar (shared/VPS):** jangan bergantung pada proses background yang selalu hidup. Queue memakai driver `sync` atau `database` + cron. Build aset front-end dilakukan lokal/CI, bukan di server.
10. **Ketika persyaratan tidak jelas, berhenti dan tanya pengguna** (beri 2–4 pilihan jawaban). Jangan menebak.

## Perintah standar (sesuaikan dengan versi terbaru)
- Install: `composer install && npm install`
- Dev: `composer run dev`
- Test: `php artisan test` (Pest)
- Format: `./vendor/bin/pint` dan lint/format front-end sesuai starter kit
- Migrasi + seed: `php artisan migrate:fresh --seed`

## Gaya kode
- Laravel: Form Request, Policy, Resource/DTO untuk data ke Inertia, Action/Service class untuk logika alur status, Enum PHP untuk status/peran.
- React: TypeScript ketat, komponen form dipecah per bagian, validasi error dari server ditampilkan di bawah field.
- Tidak ada logika bisnis di komponen React; aturan transisi status ada di server.
