# RME Keperawatan Kamar Operasi (OKA)

Aplikasi web **Rekam Medis Elektronik (RME) Keperawatan Kamar Operasi** untuk pencatatan asuhan keperawatan perioperatif terstruktur dari tahap **Pra-Operasi, Persiapan & Cek Alat, Surgical Safety Checklist (SSC) WHO, Intra-Operasi, Pasca-Operasi, Serah Terima Pasien, Audit Log Global, Rekap Laporan Bulanan, hingga Ekspor PDF**.

Dikembangkan berdasarkan spesifikasi **SKPL KEL 8 (Prodi Fikes)** dan **Form RME OKA RSU Universitas Muhammadiyah Jember** dengan seluruh antarmuka pengguna, pesan validasi, dan dokumen cetak dalam **Bahasa Indonesia**, sementara seluruh kode sumber mengikuti konvensi penamaan **Bahasa Inggris**.

---

## Daftar Isi

1. [Ringkasan Fitur Utama](#ringkasan-fitur-utama)
2. [Aktor & Matriks Hak Akses (RBAC)](#aktor--matriks-hak-akses-rbac)
3. [Alur Status Kasus Perioperatif](#alur-status-kasus-perioperatif)
4. [Arsitektur, Tech Stack & Versi Paket](#arsitektur-tech-stack--versi-paket)
5. [Sistem Desain UI (Apple Liquid Glass: Clinical Navy + Pure White)](#sistem-desain-ui-apple-liquid-glass-clinical-navy--pure-white)
6. [Keamanan, Optimistic Locking & SEO/GEO](#keamanan-optimistic-locking--seogeo)
7. [Struktur Direktori Proyek](#struktur-direktori-proyek)
8. [Instalasi & Menjalankan Secara Lokal](#instalasi--menjalankan-secara-lokal)
9. [Akun Login & Data Demo (Non-Produksi)](#akun-login--data-demo-non-produksi)
10. [Daftar Route Utama Aplikasi](#daftar-route-utama-aplikasi)
11. [Pengujian, Linter & Kualitas Kode](#pengujian-linter--kualitas-kode)
12. [Ringkasan Panduan Deploy Produksi](#ringkasan-panduan-deploy-produksi)

---

## Ringkasan Fitur Utama

| Modul | Cakupan Fitur |
|---|---|
| **Autentikasi & Sesi** | Login berbasis `username` atau `email` + kata sandi (Laravel Fortify, tanpa registrasi publik), kebijakan sandi minimal 10 karakter kombinasi huruf dan angka, pembatasan percobaan login (5x/menit), serta peringatan dini sebelum sesi 30 menit berakhir (`POST /keep-alive`). |
| **Manajemen Pasien & Kasus** | Pencarian dan pendaftaran pasien baru/lama (`DEMO-001` dst., pencegahan duplikat lintas huruf, penghitungan usia tahun/bulan/hari otomatis), pembuatan kasus operasi dengan penomoran otomatis (`OK-YYYYMMDD-XXX`), dan ringkasan kelengkapan 10 bagian wajib. |
| **Pre-Operasi** | Asesmen awal (keluhan utama, alergi wajib detail jika *Ada*, status puasa wajib waktu mulai jika aktif, tingkat kecemasan), tanda vital pra-op dengan peringatan rentang wajar, pemeriksaan penunjang, 10 checklist kesiapan (status *Siap* terkunci otomatis bila belum lengkap), diagnosis keperawatan **SDKI + SLKI + SIKI** (20 diagnosis master), pengesahan perawat, dan verifikasi dokter (otomatis ter-reset bila data pra-op diubah). |
| **Persiapan & Cek Alat** | Penetapan ruang operasi (OK 1-3) serta tim 5 petugas (Dokter Operator, Dokter Anestesi, Perawat Sirkuler, Perawat Instrumen, Perawat Anestesi) dengan peringatan non-blokir bila ruang bentrok kasus `intra_op`; checklist instrumen (tersedia, steril, berfungsi, berkarat) yang mewajibkan catatan tindak lanjut bila ditemukan alat bermasalah. |
| **Surgical Safety Checklist (SSC WHO)** | **Sign In** (bagian A-G + kolaborasi penilaian risiko dan verifikasi Dokter Anestesi), **Time Out** (blok terpisah Perawat, Dokter Operator, dan Dokter Anestesi), dan **Sign Out** (5 pertanyaan WHO + 8 pertanyaan kondisional serta validasi silang hitungan instrumen, spesimen, dan kendala peralatan). |
| **Intra-Operasi** | Kronologi 5 waktu operasi dengan kunci keamanan (Mulai Anestesi terkunci sebelum Sign In selesai; Mulai Insisi terkunci sebelum Time Out selesai) + tombol *Catat sekarang*; posisi & area operasi; pemantauan TTV berkala + **grafik tren SVG**; pencatatan obat; balans cairan masuk/keluar otomatis; tindakan keperawatan + 7 template standar; **hitung instrumen 5 item** (klem, pinset, pisau, jarum, kasa) dengan status `S` (Sesuai) / `TS` (Tidak Sesuai) otomatis & langkah tindak lanjut SOP; pengelolaan spesimen & kejadian khusus; diagnosis intra-op; serta **pengesahan 4 pihak** sesuai tim kasus. |
| **Pasca-Operasi & Serah Terima** | Observasi pasca-op berulang (skor GCS total otomatis, TTV + LPM oksigen, skala nyeri NRS, luka/balutan/jahitan, cairan/drain, pemulihan anestesi, komplikasi) + grafik tren TTV; diagnosis pasca-op SDKI/SLKI/SIKI; penilaian kondisi layak pindah; **ringkasan serah terima otomatis 12 item** yang disimpan sebagai snapshot permanen; penguncian edit perawat saat status `selesai`; serta **koreksi medis dokter** dengan alasan wajib tercatat di audit log. |
| **Dashboard, Laporan & Audit Log** | Dashboard adaptif tiap peran, agregasi laporan bulanan & tren 6 bulan dalam 1 query tunggal untuk Kepala Ruangan, serta **Audit Log Global** read-only yang merekam aktivitas login/logout/failed, perubahan nilai lama -> baru, alamat IP, dan alasan perubahan. |
| **Ekspor PDF** | Generasi dokumen PDF berkop Rumah Sakit menggunakan **DomPDF** untuk **Rekam Keperawatan Kamar Operasi Lengkap** (`/kasus/{id}/pdf`) dan **Lembar Serah Terima Pasien** (`/kasus/{id}/pdf/serah-terima`) dengan rate limit `30/menit`. |
| **Administrasi & Pengaturan** | Manajemen pengguna & reset kata sandi, pengelolaan 5 master data klinis (Ruang Operasi, Alat/Instrumen, Jenis Operasi, Jenis Anestesi, Diagnosis Medis) dengan toggle aktif/non-aktif tanpa menghapus riwayat kasus lama, serta pengaturan profil rumah sakit (Admin) dan profil/sandi/tampilan akun. |

---

## Aktor & Matriks Hak Akses (RBAC)

Seluruh otorisasi ditegakkan di backend melalui Laravel Policy dan middleware `role`:

| Peran | Akses Utama | Pembatasan Ketat |
|---|---|---|
| **Admin** (`admin`) | Kelola pengguna (`/admin/pengguna`), kelola 5 master data (`/admin/master/*`), dan pengaturan rumah sakit (`/pengaturan/rumah-sakit`). | Diblokir total (`HTTP 403`) dari seluruh route klinis (`/pasien`, `/kasus`, `/laporan`, `/audit`). |
| **Perawat** (`perawat`: Sirkuler, Instrumen, Anestesi) | Baca/kelola pasien, buat & ubah kasus, isi form Pre-Op, Persiapan & Cek Alat, SSC Sign In/Time Out/Sign Out, Intra-Op, Pasca-Op, Serah Terima, pengesahan perawat, dan ekspor PDF. | Tidak dapat melakukan verifikasi/pengesahan dokter atau koreksi dokter setelah selesai; terkunci edit saat kasus berstatus `selesai`. |
| **Dokter Operator** (`dokter_operator`) | Lihat kasus & pasien, verifikasi Pre-Op, isi blok Operator pada SSC Time Out, sahkan bagian Operator pada Intra-Op, verifikasi Sign Out & Pasca-Op, dan lakukan koreksi medis dokter setelah `selesai`. | Terbatas pada kasus di mana ia ditugaskan sebagai tim dokter kasus; tidak mengubah form keperawatan perawat. |
| **Dokter Anestesi** (`dokter_anestesi`) | Lihat kasus & pasien, verifikasi Pre-Op & SSC Sign In, isi penilaian risiko anestesi, catat obat intra-op, isi blok Anestesi pada SSC Time Out, sahkan bagian Anestesi pada Intra-Op, verifikasi Sign Out & Pasca-Op, serta koreksi medis dokter. | Terbatas pada kasus di mana ia ditugaskan sebagai tim dokter anestesi kasus. |
| **Kepala Ruangan** (`kepala_ruangan`) | Akses baca pasien & seluruh kasus operasi, **Laporan & Rekap Bulanan** (`/laporan`), **Audit Log Global** (`/audit`), dan ekspor PDF. | Bersifat pengawas/read-only pada data klinis (tidak dapat mengubah form keperawatan maupun mengesahkan tindakan). |

---

## Alur Status Kasus Perioperatif

Setiap kasus bergerak secara berurutan (tanpa lompat tahap atau mundur) melalui satu gerbang layanan tunggal `AdvanceCaseStatus`:

```text
pre_op  --->  siap_operasi  --->  intra_op  --->  pasca_op  --->  serah_terima  --->  selesai
```

1. **`pre_op` -> `siap_operasi`**: Asesmen pra-op berstatus *Siap* + pengesahan perawat pra-op + ruang operasi & tim 5 petugas lengkap + cek seluruh instrumen selesai + SSC Sign In berstatus *Selesai*.
2. **`siap_operasi` -> `intra_op`**: SSC Time Out berstatus *Selesai*.
3. **`intra_op` -> `pasca_op`**: Kronologi waktu selesai operasi & keluar OK terisi menaik + hitungan akhir 5 item instrumen tercatat + SSC Sign Out berstatus *Terverifikasi* + pengesahan 4 pihak Intra-Op lengkap.
4. **`pasca_op` -> `serah_terima`**: Minimal 1 observasi pasca-op tercatat + ringkasan kondisi sebelum pindah diisi dengan status *Layak Pindah*.
5. **`serah_terima` -> `selesai`**: Diselesaikan otomatis ketika form **Serah Terima Pasien** (`HandoverController::store`) disimpan.

---

## Arsitektur, Tech Stack & Versi Paket

### Backend
- **PHP**: `^8.2` (diuji pada PHP `8.2.12`)
- **Laravel Framework**: `^12.0`
- **Laravel Fortify**: `^1.43` (autentikasi berbasis session tanpa registrasi publik)
- **Inertia.js Laravel**: `^2.0` & **Tightenco Ziggy**: `^2.4`
- **Spatie Laravel Activitylog**: `^4.12` (jejak audit klinis & autentikasi)
- **Barryvdh Laravel DomPDF**: `^3.1` (ekspor PDF berkop RS)
- **Database**: MySQL 8+ / MariaDB 10.6+

### Frontend
- **React**: `^19.0.0` & **Inertia.js React**: `^2.0.0`
- **TypeScript**: `^5.7.2` (strict mode, zero `tsc --noEmit` errors)
- **Tailwind CSS**: `^4.0.0` (`@tailwindcss/vite` `^4.0.6`)
- **Radix UI & shadcn/ui**: `@radix-ui/react-dialog`, `@radix-ui/react-select`, `@radix-ui/react-dropdown-menu`, `@radix-ui/react-navigation-menu`, `@radix-ui/react-tooltip`, `@radix-ui/react-checkbox`, `@radix-ui/react-avatar`, `lucide-react`, `sonner`
- **Build Tool**: **Vite** `^6.0`

### Kualitas Kode & Pengujian
- **Pest PHP**: `^3.8` (`pestphp/pest-plugin-laravel` `^3.2`) + **PHPUnit** `^11.5.3`
- **Larastan / PHPStan**: `^3.15` (Level 5)
- **Laravel Pint**: `^1.18`
- **ESLint**: `^9.17.0` + **Prettier**: `^3.4.2`

---

## Sistem Desain UI (Apple Liquid Glass: Clinical Navy + Pure White)

Spesifikasi desain terdokumentasi di [DESIGN.md](file:///d:/iann%20kuliah/project/rme-oka-docs/DESIGN.md) dengan prinsip utama:

- **Palet Warna & Kontras WCAG AAA:** Default **Light Mode** menggunakan kanvas **Clinical Ice (`#F4F7FB`)**, permukaan form **Pure White (`#FFFFFF`)**, serta tinta & aksen **Clinical Navy (`#0A2540`)** dengan rasio kontras **`15.54 : 1`** dan teks sekunder `#334E68` (**`8.64 : 1`**).
- **Tanpa Gradasi (Zero Gradient):** Seluruh antarmuka bebas dari `linear-gradient` maupun `radial-gradient`.
- **Material Liquid Glass Terkendali:** Efek kaca membiaskan (`backdrop-filter: blur(...) saturate(...)`) pada `.liquid-glass-header`, `.liquid-glass-nav`, dan `.liquid-glass-modal` **hanya** diterapkan pada **Header, Navigasi (Sidebar mengambang desktop, Bottom Navigation Bar mobile `< 768px`, Case Stepper/Pill Tabs), dan Modal/Dialog**. Seluruh kartu formulir klinis dan tabel tetap **Putih Solid (`#FFFFFF`)**.
- **Sudut Konsentris & Fluid Morphing:** Geometri radius konsentris (`rounded-3xl` -> `rounded-2xl` -> `rounded-xl` -> `rounded-lg`), transisi pegas `cubic-bezier(0.22, 1, 0.36, 1)`, penghormatan `@media (prefers-reduced-motion: reduce)`, serta area sentuh minimal **`44px x 44px`** (`min-h-11`).
- **Responsivitas Multi-Device:** Diuji pada viewport Desktop (`1280x800`), Tablet/Mobile (`390x844`), dan Layar Kecil (`360x640`) dengan `0px` horizontal overflow dan modal scrollable (`max-h-[calc(100dvh-2rem)]`).

---

## Keamanan, Optimistic Locking & SEO/GEO

1. **HTTP Security Headers ([AddSecurityHeaders.php](file:///d:/iann%20kuliah/project/rme-oka-docs/app/Http/Middleware/AddSecurityHeaders.php)):**
   - `X-Frame-Options: SAMEORIGIN` & `CSP frame-ancestors 'self'` (mencegah clickjacking eksternal sekaligus aman untuk preview same-origin)
   - `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, `Strict-Transport-Security` (HTTPS / produksi)
   - `Content-Security-Policy` lengkap (`default-src 'self'`, `script-src`, `script-src-elem`, `style-src`, `style-src-elem`, `object-src 'none'`)
   - `X-Robots-Tag: noindex, nofollow` pada seluruh route klinis & autentikasi internal.
2. **Proteksi Data & Concurrency:**
   - `$fillable` eksplisit di 31 model, `Model::preventLazyLoading(! app()->isProduction())`, `Model::preventSilentlyDiscardingAttributes(! app()->isProduction())`.
   - **Optimistic Locking** (`expected_updated_at` via [OptimisticLock.php](file:///d:/iann%20kuliah/project/rme-oka-docs/app/Support/OptimisticLock.php) + `<ConcurrencyAlert />`) pada 5 form besar (Pre-Op, SSC Sign In, SSC Time Out, SSC Sign Out, Intra-Op).
3. **SEO, Open Graph & GEO:**
   - Halaman publik `/login` dilengkapi metadata SEO (`id_ID`), Open Graph `1200x630` (`public/og-image.png`), Twitter Card, Schema.org JSON-LD (`WebApplication` + `MedicalOrganization`), `public/robots.txt`, `public/sitemap.xml`, dan `public/llms.txt`.

---

## Struktur Direktori Proyek

```text
rme-oka-docs/
├── .github/workflows/ci.yml        # Workflow CI GitHub Actions
├── app/
│   ├── Actions/Cases/              # Logika bisnis & transisi status kasus
│   ├── Enums/                      # Enum status, peran, fase, dan checklist
│   ├── Http/
│   │   ├── Controllers/            # Controller Inertia, Admin, PDF, Audit, Laporan
│   │   ├── Middleware/             # AddSecurityHeaders, RoleMiddleware, EnsureUserIsActive
│   │   └── Requests/               # Validasi FormRequest tiap tahap klinis
│   ├── Models/                     # 31 Eloquent Model + relasi & audit trail
│   ├── Policies/                   # Policy RBAC per model
│   ├── Providers/                  # AppServiceProvider (Gate & Auth Audit), FortifyServiceProvider
│   ├── Services/                   # HandoverSummaryBuilder, CaseCompletenessCalculator
│   └── Support/                    # OptimisticLock, AuditContext
├── config/                         # Konfigurasi Laravel, Fortify, DomPDF, Activitylog
├── database/
│   ├── factories/                  # Factory data uji
│   ├── migrations/                 # 27+ migrasi skema RME OKA
│   └── seeders/                    # MasterSeeder, NursingDiagnosisSeeder (20 SDKI), DemoSeeder
├── docs/referensi/                 # Teks referensi RME OKA & SLKI/SIKI (.txt)
├── lang/                           # Lokalisasi Bahasa Indonesia
├── public/                         # Entrypoint, build Vite (public/build/), og-image.png, robots.txt, sitemap.xml, llms.txt
├── resources/
│   ├── css/app.css                 # Token Navy + Putih & Apple Liquid Glass CSS
│   ├── js/
│   │   ├── components/             # UI shadcn/ui, grafik TTV SVG, mobile bottom nav, concurrency alert
│   │   ├── hooks/                  # use-appearance (default light), use-mobile
│   │   ├── layouts/                # AppSidebarLayout, CaseLayout, SettingsLayout, AuthLayout
│   │   └── pages/                  # Halaman Inertia React + TypeScript
│   └── views/                      # app.blade.php & template cetak PDF Blade
├── routes/                         # web.php, admin.php, settings.php, console.php
├── tests/                          # Unit & Feature test (Pest)
├── DESIGN.md                       # Spesifikasi desain resmi Apple Liquid Glass Navy + Putih
├── AGENTS.md                       # Konvensi agen & standar pengkodean proyek
├── composer.json                   # Dependensi PHP & script composer run dev
└── package.json                    # Dependensi Frontend & script npm build/lint/format
```

---

## Instalasi & Menjalankan Secara Lokal

### Prasyarat
- **PHP >= 8.2** dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `xml`, `dom`, `gd`, `fileinfo`
- **Composer 2.x**
- **Node.js >= 20** & **npm**
- **MySQL 8+** atau **MariaDB 10.6+**

### Langkah Setup
```bash
# 1. Pasang dependensi PHP dan JavaScript (atau gunakan folder vendor & node_modules yang sudah tersedia)
composer install
npm install

# 2. Konfigurasi lingkungan
cp .env.example .env
php artisan key:generate

# Pastikan koneksi MySQL & DEMO_PASSWORD=Password123! sudah sesuai pada .env

# 3. Migrasi & seed data master + 20 SDKI + 8 akun & 4 kasus demo
php artisan migrate:fresh --seed

# 4. Bangun aset produksi atau jalankan server pengembangan
npm run build
composer run dev
```

Atau jalankan server Laravel dan Vite secara terpisah:
```bash
php artisan serve --host=127.0.0.1 --port=8188
npm run dev
```

> **Catatan Hosting / Shared Hosting (VPS & cPanel):** Build aset front-end (`npm run build`) dilakukan lokal sehingga hasil kompilasi di `public/build/` siap pakai tanpa Node.js di server. Queue menggunakan `sync` atau `database` + cron tanpa bergantung pada daemon yang selalu menyala.

---

## Akun Login & Data Demo (Non-Produksi)

Saat `php artisan migrate:fresh --seed` dijalankan di lingkungan non-produksi, `DemoSeeder` membuat 8 akun fiktif dengan kata sandi bawaan dari `.env` (`DEMO_PASSWORD=Password123!`):

| Username | Email | Peran | Nama Fiktif |
|---|---|---|---|
| `admin` | `admin@rme-oka.test` | Admin | Administrator Sistem OKA |
| `kepala` | `kepala@rme-oka.test` | Kepala Ruangan | Ns. Ratna Puspita, S.Kep |
| `dr.operator1` | `operator1@rme-oka.test` | Dokter Operator | dr. Bagus Prasetyo, Sp.B |
| `dr.anestesi1` | `anestesi1@rme-oka.test` | Dokter Anestesi | dr. Hendra Wijaya, Sp.An |
| `ns.sirkuler1` | `sirkuler1@rme-oka.test` | Perawat (Sirkuler) | Ns. Sari Rahmawati, S.Kep |
| `ns.instrumen1` | `instrumen1@rme-oka.test` | Perawat (Instrumen) | Ns. Dimas Saputra, S.Kep |
| `ns.anestesi1` | `perawat.anestesi1@rme-oka.test` | Perawat (Anestesi) | Ns. Dewi Kartika, S.Kep |
| `ns.sirkuler2` | `sirkuler2@rme-oka.test` | Perawat (Sirkuler) | Ns. Fajar Nugroho, S.Kep |

Selain itu tersedia **4 kasus demo fiktif**:
1. **`OK-20261119-001`** (`DEMO-001` - Pasien Contoh A): tahap **Pre-Operasi** awal.
2. **`OK-20261119-002`** (`DEMO-002` - Pasien Contoh B): tahap **Siap Operasi** (Pre-Op, Cek Alat, dan SSC Sign In lengkap).
3. **`OK-20261119-003`** (`DEMO-003` - Pasien Contoh C): tahap **Selesai** lengkap dari Pre-Op hingga Serah Terima 12 item, 4 pengesahan Intra-Op, verifikasi dokter, dan siap ekspor PDF lengkap.
4. **`OK-20261119-004`** (`DEMO-004` - Pasien Contoh D): tahap **Intra-Operasi** berjalan dengan contoh selisih hitungan kasa (`TS`) dan Sign Out berstatus *Perlu Tindak Lanjut*.

---

## Daftar Route Utama Aplikasi

- **Publik:** `GET /login`
- **Dashboard & Pasien:** `GET /dashboard`, `GET /pasien`, `POST /pasien`, `PUT /pasien/{patient}`
- **Kasus Operasi & 8 Tab Rekam Klinis:**
  - `GET /kasus`, `GET /kasus/buat`, `POST /kasus`
  - `GET /kasus/{id}` (Ringkasan), `GET /kasus/{id}/pre-op`, `GET /kasus/{id}/persiapan`
  - `GET /kasus/{id}/ssc/sign-in`, `GET /kasus/{id}/ssc/time-out`, `GET /kasus/{id}/ssc/sign-out`
  - `GET /kasus/{id}/intra-op`, `GET /kasus/{id}/pasca-op`, `GET /kasus/{id}/serah-terima`, `GET /kasus/{id}/riwayat`
  - `GET /kasus/{id}/pdf`, `GET /kasus/{id}/pdf/serah-terima`
- **Kepala Ruangan:** `GET /laporan`, `GET /audit`
- **Admin:** `GET /admin/pengguna`, `GET /admin/master/ruang`, `GET /admin/master/alat`, `GET /admin/master/jenis-operasi`, `GET /admin/master/jenis-anestesi`, `GET /admin/master/diagnosis-medis`, `GET /pengaturan/rumah-sakit`
- **Pengaturan Akun:** `GET /pengaturan`, `GET /pengaturan/sandi`, `GET /pengaturan/tampilan`

---

## Pengujian, Linter & Kualitas Kode

```bash
# 1. Jalankan seluruh pengujian otomatis (Pest: 86 passed, 1368 assertions)
php artisan test

# 2. Periksa format kode PHP (Laravel Pint)
./vendor/bin/pint --test

# 3. Analisis statis PHP (Larastan / PHPStan Level 5)
./vendor/bin/phpstan analyse --memory-limit=512M

# 4. Pemeriksaan TypeScript strict, ESLint, Prettier, dan build produksi Vite
npx tsc --noEmit
npm run lint
npm run format:check
npm run build

# 5. Audit keamanan dependensi
composer audit
npm audit --omit=dev
```

---

## Ringkasan Panduan Deploy Produksi

1. Setel `.env` produksi (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda.id`, `SESSION_SECURE_COOKIE=true`, database & `QUEUE_CONNECTION=database`).
2. Pastikan aset front-end telah dikompilasi lokal (`npm run build`) dan folder `public/build/` ikut diunggah ke server.
3. Arahkan *Document Root* domain ke direktori `public/` (atau struktur `public_html` sesuai panduan hosting).
4. Jalankan migrasi & seeder master produksi:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=MasterSeeder --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. Jangan jalankan `DemoSeeder` pada lingkungan `production` (seeder otomatis melewatkan data demo bila `APP_ENV=production`).
6. Buat akun Admin awal atau ganti segera kata sandi akun bawaan sebelum layanan diakses pengguna rumah sakit.
