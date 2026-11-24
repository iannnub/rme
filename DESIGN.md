# DESIGN.md - RME Keperawatan Kamar Operasi (OKA)

Spesifikasi sistem desain resmi aplikasi **RME Keperawatan Kamar Operasi (OKA)** dengan arah visual **Apple Liquid Glass - Clinical Navy & Pure White (Zero Gradient)**.

---

## 1. Theme & Atmosphere
- **Karakter Visual:** Presisi klinis, bersih, tenang, dan otoritatif. Menggabungkan keterbacaan tinggi rekam medis rumah sakit pada permukaan konten putih solid dengan elemen navigasi, header, dan modal bergaya **Apple Liquid Glass** yang mengambang (*floating*), transparan-membiaskan, dan memiliki animasi *morphing* cair yang halus.
- **Tema Default:** **Light Mode** (`Pure White #FFFFFF` & `Clinical Ice #F4F7FB` dengan tinta & aksen `Clinical Navy #0A2540`).
- **Aturan Ketat:**
  1. **Tanpa Gradasi (Zero Gradient):** Dilarang menggunakan `linear-gradient` atau `radial-gradient` pada latar halaman, kartu, tombol, badge, maupun teks.
  2. **Batas Efek Kaca (Strict Glass Dose Cap):** Material *Liquid Glass* (`backdrop-filter: blur(...) saturate(...)`) **hanya** diterapkan pada **Header Mengambang, Navigasi (Floating Sidebar, Floating Mobile Bottom Nav, Floating Case Stepper/Tabs), dan Modal/Dialog**. Seluruh kartu formulir klinis, tabel, dan input tetap menggunakan latar **Putih Solid (`#FFFFFF`)** demi ketajaman baca dan kontras WCAG AAA.

---

## 2. Color Palette & Roles

| Role | Token CSS | Hex | HSL | Rasio Kontras (vs `#FFFFFF`) | Penggunaan |
|---|---|---|---|---|---|
| **Primary (Clinical Navy)** | `--primary` | `#0A2540` | `210 73% 15%` | **15.54 : 1** (AAA) | Tombol utama, pill navigasi aktif, badge status utama, fokus ring |
| **Primary Hover** | `--primary-hover` | `#0F345B` | `211 72% 21%` | **12.40 : 1** (AAA) | State hover/active pada elemen Navy |
| **Canvas Background** | `--background` | `#F4F7FB` | `214 45% 97%` | - | Latar kanvas utama di belakang kartu putih & elemen kaca mengambang |
| **Surface / Card** | `--card` | `#FFFFFF` | `0 0% 100%` | - | Seluruh kartu form klinis, tabel data, panel asesmen (putih solid) |
| **Primary Ink** | `--foreground` | `#0A2540` | `210 73% 15%` | **15.54 : 1** (AAA) | Judul, label form, isi data klinis utama |
| **Secondary / Muted Ink** | `--muted-foreground` | `#334E68` | `209 34% 30%` | **8.64 : 1** (AAA) | Keterangan bantu, timestamp, sub-label |
| **Secondary Surface** | `--secondary` | `#E8EFF7` | `212 48% 94%` | - | Latar chip sekunder, hover baris tabel, pill non-aktif |
| **Border Hairline** | `--border` | `#D5E0EC` | `211 35% 88%` | - | Garis pemisah kartu, tabel, dan input |
| **Input Border** | `--input` | `#788796` | `211 13% 53%` | **3.68 : 1** (vs `#FFFFFF`) | Tepian kolom input form klinis |
| **Destructive (Clinical Alert)** | `--destructive` | `#B91C1C` | `0 74% 42%` | **6.45 : 1** (AA+) | Peringatan alergi, selisih hitungan instrumen (`TS`), pesan galat |

---

## 3. Typography Rules
- **Font Family:** `'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', sans-serif`.
- **Skala & Hierarki:**
  - Judul Halaman (`h1`): `1.25rem`–`1.5rem` (`20px`–`24px`), `font-weight: 700`, `letter-spacing: -0.02em`, `line-height: 1.25`.
  - Judul Kartu/Bagian (`h2` / `CardTitle`): `1rem`–`1.125rem` (`16px`–`18px`), `font-weight: 600`, `letter-spacing: -0.01em`.
  - Isi & Input Form: `0.875rem`–`1rem` (`14px`–`16px`), `line-height: 1.5`.
  - Angka Klinis (TTV, RM, Hitungan Instrumen): `font-variant-numeric: tabular-nums` agar sejajar vertikal di dalam tabel.

---

## 4. Concentric Radii (Sudut Konsentris)
Mengikuti geometri perangkat keras dan antarmuka Apple, setiap kontainer luar dan elemen di dalamnya menggunakan radius konsentris (`R_outer = R_inner + padding`):
- **Outer Floating Shell (Modal, Floating Sidebar, Floating Header Container):** `24px` (`1.5rem` / `rounded-3xl`) dengan padding internal `8px`–`12px`.
- **Card & Inner Panel:** `16px` (`1rem` / `rounded-2xl`) dengan padding `16px`–`24px`.
- **Interactive Controls (Button, Input, Select, Nav Pill):** `12px` (`0.75rem` / `rounded-xl`).
- **Compact Status Badges:** `8px` (`0.5rem` / `rounded-lg`).

---

## 5. Liquid Glass & Morphing Motion Specification
- **Hanya untuk Header, Navigasi, dan Modal:**
  - `.liquid-glass-header`: `rgba(255, 255, 255, 0.78)`, `backdrop-filter: blur(22px) saturate(180%)`, border spekular `1px solid rgba(10, 37, 64, 0.10)`, highlight dalam `inset 0 1px 0 rgba(255, 255, 255, 0.92)`.
  - `.liquid-glass-nav`: `rgba(255, 255, 255, 0.76)`, `backdrop-filter: blur(24px) saturate(185%)`, border `1px solid rgba(10, 37, 64, 0.11)`, shadow mengambang `0 12px 32px -8px rgba(10, 37, 64, 0.12)`.
  - `.liquid-glass-modal`: `rgba(255, 255, 255, 0.88)`, `backdrop-filter: blur(28px) saturate(190%)`, border `1px solid rgba(10, 37, 64, 0.14)`, shadow `0 24px 56px -12px rgba(10, 37, 64, 0.24)`.
- **Fluid Morphing Animation:**
  - Kurva pegas cairan Apple: `cubic-bezier(0.22, 1, 0.36, 1)` dengan durasi `280ms`–`380ms` pada perpindahan pill navigasi, pembukaan modal, ekspansi tab, dan umpan balik sentuh (`active:scale-[0.98]`).
  - Menghormati `@media (prefers-reduced-motion: reduce)` dengan mematikan transformasi spasial bagi pengguna yang sensitif gerakan.

---

## 6. Layout, Touch Targets & Responsive Reflow
- **Minimum Touch Target:** Seluruh tombol, item navigasi, tab kasus, trigger sidebar, dan tombol tutup modal memiliki area sentuh minimal **`44px × 44px`** (`min-h-11`).
- **Desktop (`>= 768px`):** Floating Liquid Glass Sidebar di kiri + Floating Liquid Glass Top Header + area kerja kartu putih solid.
- **Mobile & Tablet (`< 768px`):** Floating Liquid Glass Bottom Navigation Bar mengambang di bawah layar dengan ruang aman `pb-24` pada konten utama sehingga tidak ada tombol/form yang tertutup.

---

## 7. Do's and Don'ts
- **DO:** Gunakan kombinasi **Clinical Navy (`#0A2540`)** dan **Putih (`#FFFFFF`)** secara konsisten.
- **DO:** Batasi efek kaca (`backdrop-blur`) hanya pada Header, Navigasi, dan Modal.
- **DON'T:** Jangan pernah menambahkan `bg-gradient-*`, `from-*`, `to-*`, atau warna ungu/cyan/neon.
- **DON'T:** Jangan menaruh efek blur kaca pada kartu form klinis atau tabel data.
