<?php

namespace Database\Seeders;

use App\Enums\NursingPhase;
use App\Enums\SikiSection;
use App\Enums\SlkiExpectation;
use App\Models\NursingDiagnosis;
use App\Models\SikiIntervention;
use App\Models\SikiItem;
use App\Models\SlkiCriterion;
use Illuminate\Database\Seeder;

class NursingDiagnosisSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPreopDiagnoses();
        $this->seedIntraopDiagnoses();
        $this->seedPostopDiagnoses();
    }

    private function seedPreopDiagnoses(): void
    {
        $diagnoses = [
            [
                'code' => 'D.0080',
                'name' => 'Ansietas',
                'phase' => NursingPhase::Pre,
                'criteria' => [
                    'outcome_code' => 'L.09093',
                    'outcome_name' => 'Tingkat Ansietas',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Verbalisasi kebingungan menurun (5)',
                        'Verbalisasi khawatir akibat kondisi yang dihadapi menurun (5)',
                        'Perilaku gelisah menurun (5)',
                        'Perilaku tegang menurun (5)',
                        'Keluhan pusing menurun (5)',
                        'Diaforesis menurun (5)',
                        'Tremor menurun (5)',
                        'Pucat menurun (5)',
                        'Frekuensi pernapasan membaik (5)',
                        'Frekuensi nadi membaik (5)',
                        'Tekanan darah membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.09314',
                        'name' => 'Reduksi Ansietas',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Identifikasi saat tingkat ansietas berubah (mis. kondisi, waktu, stresor)',
                                'Identifikasi kemampuan mengambil keputusan',
                                'Monitor tanda-tanda ansietas (verbal dan nonverbal)',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Ciptakan suasana terapeutik untuk menumbuhkan kepercayaan',
                                'Temani pasien untuk mengurangi kecemasan, jika memungkinkan',
                                'Pahami situasi yang membuat ansietas',
                                'Dengarkan dengan penuh perhatian',
                                'Gunakan pendekatan yang tenang dan meyakinkan',
                                'Tempatkan barang pribadi yang memberikan kenyamanan',
                                'Motivasi mengidentifikasi situasi yang memicu kecemasan',
                                'Diskusikan perencanaan realistis tentang peristiwa yang akan datang',
                            ],
                            SikiSection::Edukasi->value => [
                                'Jelaskan prosedur, termasuk sensasi yang mungkin dialami',
                                'Informasikan secara faktual mengenai diagnosis, pengobatan, dan prognosis',
                                'Anjurkan keluarga untuk tetap bersama pasien, jika perlu',
                                'Anjurkan mengungkapkan perasaan dan persepsi',
                                'Latih kegiatan pengalihan untuk mengurangi ketegangan',
                                'Latih penggunaan teknik relaksasi (mis. napas dalam, distraksi)',
                            ],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian obat antiansietas, jika perlu',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0111',
                'name' => 'Defisit Pengetahuan',
                'phase' => NursingPhase::Pre,
                'criteria' => [
                    'outcome_code' => 'L.12111',
                    'outcome_name' => 'Tingkat Pengetahuan',
                    'expectation' => SlkiExpectation::Meningkat,
                    'items' => [
                        'Perilaku sesuai anjuran meningkat (5)',
                        'Kemampuan menjelaskan pengetahuan tentang prosedur bedah meningkat (5)',
                        'Pertanyaan tentang masalah yang dihadapi menurun (5)',
                        'Persepsi yang keliru terhadap masalah pembedahan menurun (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.12442',
                        'name' => 'Edukasi Prosedur Pembedahan',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Identifikasi kesiapan dan kemampuan menerima informasi',
                                'Identifikasi kecemasan pasien dan keluarga tentang pembedahan',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Sediakan materi dan media pendidikan kesehatan',
                                'Jadwalkan pendidikan kesehatan sesuai kesepakatan',
                                'Berikan kesempatan untuk bertanya',
                            ],
                            SikiSection::Edukasi->value => [
                                'Jelaskan prosedur, sensasi, dan perkiraan durasi operasi',
                                'Jelaskan pembatasan diet pra-operasi (status puasa)',
                                'Ajarkan teknik napas dalam dan batuk efektif pasca-operasi',
                                'Ajarkan mobilisasi dini bertahap pasca-operasi',
                            ],
                            SikiSection::Kolaborasi->value => [],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0142',
                'name' => 'Risiko Infeksi',
                'phase' => NursingPhase::Pre,
                'criteria' => [
                    'outcome_code' => 'L.14137',
                    'outcome_name' => 'Tingkat Infeksi',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Kebersihan tangan meningkat (5)',
                        'Kebersihan badan meningkat (5)',
                        'Demam menurun (5)',
                        'Kemerahan menurun (5)',
                        'Nyeri menurun (5)',
                        'Bengkak menurun (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.14539',
                        'name' => 'Pencegahan Infeksi',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor tanda dan gejala infeksi lokal dan sistemik',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Batasi jumlah pengunjung',
                                'Cuci tangan sebelum dan sesudah kontak dengan pasien dan lingkungan pasien',
                                'Pertahankan teknik aseptik pada pasien berisiko tinggi',
                            ],
                            SikiSection::Edukasi->value => [
                                'Jelaskan tanda dan gejala infeksi',
                                'Ajarkan cara mencuci tangan dengan benar',
                                'Ajarkan etika batuk',
                                'Anjurkan meningkatkan asupan cairan dan nutrisi',
                            ],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian antibiotik profilaksis, jika perlu',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0077',
                'name' => 'Nyeri Akut',
                'phase' => NursingPhase::Pre,
                'criteria' => [
                    'outcome_code' => 'L.08066',
                    'outcome_name' => 'Tingkat Nyeri',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Keluhan nyeri menurun (5)',
                        'Meringis menurun (5)',
                        'Sikap protektif menurun (5)',
                        'Gelisah menurun (5)',
                        'Kesulitan tidur menurun (5)',
                        'Frekuensi nadi membaik (5)',
                        'Tekanan darah membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.08238',
                        'name' => 'Manajemen Nyeri',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Identifikasi lokasi, karakteristik, durasi, frekuensi, kualitas, intensitas nyeri',
                                'Identifikasi skala nyeri',
                                'Identifikasi respons nyeri nonverbal',
                                'Identifikasi faktor yang memperberat dan memperingan nyeri',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Berikan teknik nonfarmakologis untuk mengurangi rasa nyeri (mis. napas dalam, kompres)',
                                'Fasilitasi istirahat dan tidur yang cukup',
                                'Pertimbangkan jenis dan sumber nyeri dalam pemilihan strategi meredakan nyeri',
                            ],
                            SikiSection::Edukasi->value => [
                                'Jelaskan penyebab, periode, dan pemicu nyeri',
                                'Jelaskan strategi meredakan nyeri',
                                'Ajarkan teknik nonfarmakologis untuk mengurangi rasa nyeri',
                            ],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian analgetik, jika perlu',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->saveDiagnoses($diagnoses);
    }

    private function seedIntraopDiagnoses(): void
    {
        $diagnoses = [
            [
                'code' => 'D.0012',
                'name' => 'Risiko Perdarahan',
                'phase' => NursingPhase::Intra,
                'criteria' => [
                    'outcome_code' => 'L.02017',
                    'outcome_name' => 'Tingkat Perdarahan',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Kelembaban membran mukosa meningkat (5)',
                        'Perdarahan pasca operasi menurun (5)',
                        'Hemoglobin membaik (5)',
                        'Hematokrit membaik (5)',
                        'Tekanan darah membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.02067',
                        'name' => 'Pencegahan Perdarahan',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor tanda dan gejala perdarahan masif selama operasi',
                                'Monitor nilai hematokrit/hemoglobin sebelum dan selama tindakan',
                                'Monitor tanda-tanda vital ortostatik',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Pertahankan akses intravena yang adekuat',
                                'Siapkan produk darah atau cairan koloid jika diperlukan',
                            ],
                            SikiSection::Edukasi->value => [],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian produk darah atau hemostatik, jika perlu',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0142',
                'name' => 'Risiko Infeksi Area Pembedahan',
                'phase' => NursingPhase::Intra,
                'criteria' => [
                    'outcome_code' => 'L.14137',
                    'outcome_name' => 'Tingkat Infeksi',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Integritas barier steril terjaga (5)',
                        'Kebersihan lapangan operasi terjaga (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.14539',
                        'name' => 'Pengendalian Infeksi Intra-Operasi',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Pantau kesterilan alat, instrumen, dan jas operasi sepanjang tindakan',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Pertahankan teknik aseptik ketat di ruang operasi',
                                'Lakukan preparasi kulit dan draping dengan antiseptik sesuai SOP',
                            ],
                            SikiSection::Edukasi->value => [],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian antibiotik profilaksis sebelum insisi',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0136',
                'name' => 'Risiko Cedera Posisi Pembedahan',
                'phase' => NursingPhase::Intra,
                'criteria' => [
                    'outcome_code' => 'L.14125',
                    'outcome_name' => 'Kontrol Risiko',
                    'expectation' => SlkiExpectation::Meningkat,
                    'items' => [
                        'Kemampuan mengidentifikasi faktor risiko cedera meningkat (5)',
                        'Bebas dari cedera penekanan posisi operasi (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.14537',
                        'name' => 'Pengaturan Posisi Pasien',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Identifikasi area penekanan tulang dan risiko neuropati perifer',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Gunakan bantalan proteksi pada tumit, siku, dan kepala',
                                'Pasang sabuk pengaman (safety belt) pada meja operasi',
                            ],
                            SikiSection::Edukasi->value => [],
                            SikiSection::Kolaborasi->value => [],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0141',
                'name' => 'Risiko Hipotermia Perioperatif',
                'phase' => NursingPhase::Intra,
                'criteria' => [
                    'outcome_code' => 'L.14134',
                    'outcome_name' => 'Termoregulasi',
                    'expectation' => SlkiExpectation::Membaik,
                    'items' => [
                        'Suhu tubuh dalam batas normal 36.5 - 37.5 C (5)',
                        'Menggigil menurun (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.14507',
                        'name' => 'Manajemen Hipotermia Perioperatif',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor suhu tubuh pasien secara berkala selama operasi',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Gunakan blanket warmer atau matras penghangat',
                                'Hangatkan cairan infus dan cairan irigasi jika diindikasikan',
                            ],
                            SikiSection::Edukasi->value => [],
                            SikiSection::Kolaborasi->value => [],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0067',
                'name' => 'Risiko Disfungsi Neurovaskuler Perifer',
                'phase' => NursingPhase::Intra,
                'criteria' => [
                    'outcome_code' => 'L.02014',
                    'outcome_name' => 'Sirkulasi Perifer',
                    'expectation' => SlkiExpectation::Membaik,
                    'items' => [
                        'Denyut nadi perifer teraba kuat (5)',
                        'Warna kulit normal tidak pucat/sianosis (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.02079',
                        'name' => 'Pemantauan Sirkulasi Perifer',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor pengisian kapiler (capillary refill time)',
                                'Monitor denyut nadi dan akral ekstremitas',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Hindari fiksasi ekstremitas yang terlalu kencang',
                            ],
                            SikiSection::Edukasi->value => [],
                            SikiSection::Kolaborasi->value => [],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0042',
                'name' => 'Risiko Respons Alergi',
                'phase' => NursingPhase::Intra,
                'criteria' => [
                    'outcome_code' => 'L.04037',
                    'outcome_name' => 'Respons Alergi',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Pruritus menurun (5)',
                        'Kemerahan pada kulit menurun (5)',
                        'Bronkospasme menurun (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.04160',
                        'name' => 'Pencegahan Alergi Intra-Operasi',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor riwayat alergi obat, lateks, dan antiseptik saat Sign In',
                                'Monitor timbulnya ruam, hipotensi mendadak, atau bronkospasme',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Gunakan sarung tangan non-lateks jika dicurigai alergi lateks',
                            ],
                            SikiSection::Edukasi->value => [],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian antihistamin atau epinefrin bila terjadi syok anafilaksis',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->saveDiagnoses($diagnoses);
    }

    private function seedPostopDiagnoses(): void
    {
        $diagnoses = [
            [
                'code' => 'D.0140',
                'name' => 'Risiko Hipotermia',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.14134',
                    'outcome_name' => 'Termoregulasi',
                    'expectation' => SlkiExpectation::Membaik,
                    'items' => [
                        'Menggigil menurun (5)',
                        'Kulit merah menurun (5)',
                        'Pucat menurun (5)',
                        'Dasar kuku sianotik menurun (5)',
                        'Suhu tubuh membaik (5)',
                        'Suhu kulit membaik (5)',
                        'Tekanan darah membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.14507',
                        'name' => 'Manajemen Hipotermia',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor suhu tubuh pasien',
                                'Identifikasi penyebab hipotermia',
                                'Monitor tanda dan gejala akibat hipotermia',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Sediakan lingkungan yang hangat',
                                'Ganti pakaian dan/atau linen yang basah',
                                'Lakukan penghangatan pasif (selimut hangat)',
                                'Lakukan penghangatan aktif eksternal',
                            ],
                            SikiSection::Edukasi->value => [
                                'Anjurkan makan/minum hangat bila sudah diizinkan',
                            ],
                            SikiSection::Kolaborasi->value => [],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0006',
                'name' => 'Risiko Aspirasi',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.01006',
                    'outcome_name' => 'Tingkat Aspirasi',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Tingkat kesadaran meningkat (5)',
                        'Kemampuan menelan meningkat (5)',
                        'Kebersihan mulut meningkat (5)',
                        'Dispnea menurun (5)',
                        'Akumulasi sekret menurun (5)',
                        'Frekuensi napas membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.01011',
                        'name' => 'Manajemen Jalan Napas',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor pola napas (frekuensi, kedalaman, usaha napas)',
                                'Monitor bunyi napas tambahan',
                                'Monitor sputum atau sekret jalan napas',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Pertahankan kepatenan jalan napas',
                                'Posisikan semi-Fowler atau miring sesuai kondisi',
                                'Lakukan penghisapan lendir (suction) jika diperlukan',
                                'Berikan terapi oksigen sesuai instruksi',
                            ],
                            SikiSection::Edukasi->value => [
                                'Ajarkan teknik batuk efektif jika pasien telah sadar penuh',
                            ],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian bronkodilator atau mukolitik sesuai indikasi',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0109',
                'name' => 'Defisit Perawatan Diri',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.11103',
                    'outcome_name' => 'Perawatan Diri',
                    'expectation' => SlkiExpectation::Meningkat,
                    'items' => [
                        'Verbalisasi keinginan melakukan perawatan diri meningkat (5)',
                        'Minat melakukan perawatan diri meningkat (5)',
                        'Mempertahankan kebersihan diri meningkat (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.11348',
                        'name' => 'Dukungan Perawatan Diri',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Identifikasi kebiasaan aktivitas perawatan diri sesuai usia',
                                'Monitor tingkat kemandirian pasien pasca operasi',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Dampingi dalam melakukan perawatan diri sampai mandiri',
                                'Fasilitasi kemandirian, bantu jika belum mampu melakukan sendiri',
                            ],
                            SikiSection::Edukasi->value => [
                                'Anjurkan melakukan perawatan diri secara konsisten sesuai kemampuan',
                            ],
                            SikiSection::Kolaborasi->value => [],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0077',
                'name' => 'Nyeri Akut',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.08066',
                    'outcome_name' => 'Tingkat Nyeri',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Keluhan nyeri menurun (5)',
                        'Meringis menurun (5)',
                        'Sikap protektif menurun (5)',
                        'Gelisah menurun (5)',
                        'Kesulitan tidur menurun (5)',
                        'Frekuensi nadi membaik (5)',
                        'Tekanan darah membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.08238',
                        'name' => 'Manajemen Nyeri',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Identifikasi lokasi, karakteristik, durasi, frekuensi, kualitas, intensitas nyeri',
                                'Identifikasi skala nyeri (NRS/VAS)',
                                'Identifikasi respons nyeri nonverbal',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Berikan teknik nonfarmakologis untuk mengurangi rasa nyeri',
                                'Fasilitasi istirahat dan kenyamanan tidur',
                            ],
                            SikiSection::Edukasi->value => [
                                'Jelaskan penyebab dan periode nyeri pasca operasi',
                                'Ajarkan teknik relaksasi napas dalam',
                            ],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian analgetik pasca operasi',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0142',
                'name' => 'Risiko Infeksi',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.14137',
                    'outcome_name' => 'Tingkat Infeksi',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Kebersihan badan meningkat (5)',
                        'Demam menurun (5)',
                        'Kemerahan pada luka operasi menurun (5)',
                        'Nyeri pada luka menurun (5)',
                        'Bengkak pada area luka menurun (5)',
                        'Drainase luka membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.14539',
                        'name' => 'Pencegahan Infeksi',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor tanda dan gejala infeksi lokal pada luka operasi',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Pertahankan teknik aseptik saat perawatan luka',
                                'Pertahankan balutan luka tetap bersih dan kering',
                            ],
                            SikiSection::Edukasi->value => [
                                'Jelaskan tanda dan gejala infeksi kepada pasien dan keluarga',
                                'Ajarkan prosedur perawatan luka mandiri bila diperlukan',
                            ],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian terapi antibiotik lanjutan',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0129',
                'name' => 'Gangguan Integritas Kulit/Jaringan',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.14125',
                    'outcome_name' => 'Integritas Kulit dan Jaringan',
                    'expectation' => SlkiExpectation::Meningkat,
                    'items' => [
                        'Penyembuhan luka meningkat (5)',
                        'Kerusakan jaringan menurun (5)',
                        'Nyeri pada luka menurun (5)',
                        'Perdarahan menurun (5)',
                        'Kemerahan menurun (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.11353',
                        'name' => 'Perawatan Integritas Kulit',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Identifikasi penyebab gangguan integritas kulit (insisi bedah)',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Ubah posisi setiap 2 jam jika tirah baring lama',
                                'Bersihkan luka dengan cairan fisiologis (NaCl 0.9%)',
                            ],
                            SikiSection::Edukasi->value => [
                                'Anjurkan menggunakan pelembab pada area sekitar luka',
                                'Anjurkan minum air cukup dan meningkatkan asupan protein',
                            ],
                            SikiSection::Kolaborasi->value => [],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0033',
                'name' => 'Risiko Disfungsi Motilitas Gastrointestinal',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.03023',
                    'outcome_name' => 'Motilitas Gastrointestinal',
                    'expectation' => SlkiExpectation::Membaik,
                    'items' => [
                        'Nyeri abdomen menurun (5)',
                        'Distensi abdomen menurun (5)',
                        'Suara peristaltik usus membaik (5)',
                        'Flatus membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.12369',
                        'name' => 'Edukasi Diet Pasca-Operasi',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Auskultasi bising usus pasien pasca anestesi',
                                'Monitor ada/tidaknya mual, muntah, dan flatus',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Fasilitasi puasa bertahap sampai bising usus positif dan flatus',
                            ],
                            SikiSection::Edukasi->value => [
                                'Jelaskan jadwal tahapan makan (minum sedikit demi sedikit, cair, lunak, biasa)',
                            ],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian antiemetik atau prokinetik bila perlu',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0054',
                'name' => 'Gangguan Mobilitas Fisik',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.05042',
                    'outcome_name' => 'Mobilitas Fisik',
                    'expectation' => SlkiExpectation::Meningkat,
                    'items' => [
                        'Pergerakan ekstremitas meningkat (5)',
                        'Kekuatan otot meningkat (5)',
                        'Rentang gerak (ROM) meningkat (5)',
                        'Nyeri menurun (5)',
                        'Kecemasan menurun (5)',
                        'Kaku sendi menurun (5)',
                        'Gerakan tidak terkoordinasi menurun (5)',
                        'Gerakan terbatas menurun (5)',
                        'Kelemahan fisik menurun (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.06171',
                        'name' => 'Dukungan Ambulasi',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Identifikasi adanya nyeri atau keluhan fisik lainnya saat mobilisasi',
                                'Identifikasi toleransi fisik melakukan ambulasi dini',
                                'Monitor frekuensi jantung dan tekanan darah sebelum memulai ambulasi',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Fasilitasi aktivitas ambulasi dengan alat bantu (mis. pegangan tempat tidur)',
                                'Fasilitasi melakukan mobilisasi fisik bertahap (miring kanan-kiri, duduk)',
                                'Libatkan keluarga untuk membantu pasien dalam meningkatkan ambulasi',
                            ],
                            SikiSection::Edukasi->value => [
                                'Jelaskan tujuan dan prosedur ambulasi dini pasca operasi',
                                'Anjurkan melakukan ambulasi dini secara bertahap',
                            ],
                            SikiSection::Kolaborasi->value => [],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0041',
                'name' => 'Inkontinensia Fekal',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.04035',
                    'outcome_name' => 'Kontinensia Fekal',
                    'expectation' => SlkiExpectation::Membaik,
                    'items' => [
                        'Kemampuan mengontrol pengeluaran feses meningkat (5)',
                        'Defekasi tidak terkontrol menurun (5)',
                        'Kondisi kulit perianal membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.04150',
                        'name' => 'Latihan Eliminasi Fekal',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor pola eliminasi dan konsistensi feses',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Jaga kebersihan kulit area perineal dan pertahankan tetap kering',
                            ],
                            SikiSection::Edukasi->value => [
                                'Anjurkan konsumsi makanan tinggi serat bila diet telah dimulai',
                            ],
                            SikiSection::Kolaborasi->value => [],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'D.0012',
                'name' => 'Risiko Perdarahan',
                'phase' => NursingPhase::Post,
                'criteria' => [
                    'outcome_code' => 'L.02017',
                    'outcome_name' => 'Tingkat Perdarahan',
                    'expectation' => SlkiExpectation::Menurun,
                    'items' => [
                        'Perdarahan pasca operasi menurun (5)',
                        'Rembesan pada balutan luka menurun (5)',
                        'Hemoglobin membaik (5)',
                        'Tekanan darah membaik (5)',
                    ],
                ],
                'interventions' => [
                    [
                        'code' => 'I.02067',
                        'name' => 'Pencegahan Perdarahan',
                        'sections' => [
                            SikiSection::Observasi->value => [
                                'Monitor tanda dan gejala perdarahan pada luka atau selang drain',
                                'Monitor nilai hematokrit dan hemoglobin pasca operasi',
                            ],
                            SikiSection::Terapeutik->value => [
                                'Pertahankan kepatenan dan kebersihan area drain',
                                'Ganti balutan bila rembesan darah melebihi batas toleransi',
                            ],
                            SikiSection::Edukasi->value => [
                                'Anjurkan segera melapor bila terasa ada darah mengalir pada luka',
                            ],
                            SikiSection::Kolaborasi->value => [
                                'Kolaborasi pemberian produk darah atau asam traneksamat bila perlu',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->saveDiagnoses($diagnoses);
    }

    /**
     * @param  list<array<string, mixed>>  $diagnoses
     */
    private function saveDiagnoses(array $diagnoses): void
    {
        foreach ($diagnoses as $diagData) {
            $diagnosis = NursingDiagnosis::query()->updateOrCreate(
                [
                    'code' => $diagData['code'],
                    'phase' => $diagData['phase'],
                ],
                [
                    'name' => $diagData['name'],
                    'is_active' => true,
                ],
            );

            if (isset($diagData['criteria'])) {
                $crit = $diagData['criteria'];
                foreach ($crit['items'] as $index => $itemText) {
                    SlkiCriterion::query()->updateOrCreate(
                        [
                            'nursing_diagnosis_id' => $diagnosis->id,
                            'criterion_text' => $itemText,
                        ],
                        [
                            'outcome_code' => $crit['outcome_code'],
                            'outcome_name' => $crit['outcome_name'],
                            'expectation' => $crit['expectation'],
                            'target_score' => 5,
                            'sort' => $index,
                        ],
                    );
                }
            }

            if (isset($diagData['interventions'])) {
                foreach ($diagData['interventions'] as $intIndex => $intData) {
                    $intervention = SikiIntervention::query()->updateOrCreate(
                        [
                            'nursing_diagnosis_id' => $diagnosis->id,
                            'code' => $intData['code'],
                        ],
                        [
                            'name' => $intData['name'],
                            'sort' => $intIndex,
                        ],
                    );

                    $sortCounter = 0;
                    foreach ($intData['sections'] as $sectionName => $items) {
                        foreach ($items as $itemText) {
                            SikiItem::query()->updateOrCreate(
                                [
                                    'siki_intervention_id' => $intervention->id,
                                    'text' => $itemText,
                                ],
                                [
                                    'section' => $sectionName,
                                    'sort' => $sortCounter++,
                                ],
                            );
                        }
                    }
                }
            }
        }
    }
}
