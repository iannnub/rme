<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekam Keperawatan Kamar Operasi - {{ $case->case_number }}</title>
    <style>
        @page {
            margin: 18mm 14mm 18mm 14mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            line-height: 1.35;
            color: #111827;
        }
        .footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #6b7280;
            border-top: 1px solid #d1d5db;
            padding-top: 3px;
        }
        .footer .pagenum:before {
            content: "Halaman " counter(page);
        }
        .header {
            border-bottom: 2px solid #0a2540;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header-title {
            font-size: 13px;
            font-weight: bold;
            color: #0a2540;
        }
        .header-sub {
            font-size: 8.5px;
            color: #4b5563;
        }
        .doc-title {
            text-align: center;
            font-size: 11.5px;
            font-weight: bold;
            margin: 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #0a2540;
        }
        h2 {
            font-size: 10px;
            background: #f1f5f9;
            border-left: 3px solid #0a2540;
            padding: 4px 6px;
            margin: 10px 0 5px 0;
            color: #0a2540;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 3.5px 5px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f9fafb;
            font-weight: bold;
            color: #374151;
        }
        .meta-table td {
            border: none;
            padding: 2px 4px;
        }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            font-size: 8px;
            border-radius: 3px;
            background: #e5e7eb;
            font-weight: bold;
        }
        .badge-ok { background: #d1fae5; color: #065f46; }
        .badge-warn { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .small { font-size: 8.5px; color: #4b5563; }
    </style>
</head>
<body>
    <div class="footer">
        <table class="meta-table" style="margin: 0;">
            <tr>
                <td>RME Keperawatan Kamar Operasi (OKA) • No. Kasus: {{ $case->case_number }}</td>
                <td style="text-align: right;">Dicetak: {{ $printedAt }} • <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    <div class="header">
        <div class="header-title">{{ $hospital['hospital_name'] ?: 'Rumah Sakit' }}</div>
        <div class="header-sub">
            {{ $hospital['hospital_address'] ?: '-' }}
            @if($hospital['hospital_phone']) | Telp: {{ $hospital['hospital_phone'] }} @endif
            @if($hospital['hospital_email']) | Email: {{ $hospital['hospital_email'] }} @endif
        </div>
    </div>

    <div class="doc-title">Rekam Medis Elektronik Keperawatan Kamar Operasi (Lengkap)</div>

    {{-- 1. IDENTITAS PASIEN & KASUS --}}
    @php
        $isNeuroPdf = $surgeryProfile === 'bedah_saraf';
        $pInc = $preopClinical['incident'] ?? [];
        $pNeuro = $preopClinical['neurological'] ?? [];
        $pExt = $preopClinical['extremity_physical'] ?? [];
        $pNv = $preopClinical['neurovascular'] ?? [];
        $pVit = $preopClinical['vital_extra'] ?? [];
        $pSup = $preopClinical['supporting_detail'] ?? [];
        $pPrep = $preopClinical['prep_extra'] ?? [];
        $pAnPre = $preopClinical['anesthesia_pre'] ?? [];
        $pAnExec = $preopClinical['anesthesia_exec'] ?? [];
    @endphp
    <h2>1. Identitas Pasien & Informasi Kasus Operasi</h2>
    <table>
        <tr>
            <th style="width: 18%;">No. Kasus</th>
            <td style="width: 32%;">{{ $case->case_number }}</td>
            <th style="width: 18%;">Status &amp; Urgensi</th>
            <td style="width: 32%;">{{ $case->status->label() }} ({{ $completeness['percentage'] }}%) • {{ $case->urgency_status === 'elektif' ? 'Elektif' : 'Operasi Darurat (Emergency)' }}</td>
        </tr>
        <tr>
            <th>Nama Pasien</th>
            <td>{{ $case->patient?->name ?? '-' }}</td>
            <th>No. Rekam Medis / NIK</th>
            <td>{{ $case->patient?->rm_number ?? '-' }} / {{ $case->patient?->identity_number ?: '-' }}</td>
        </tr>
        <tr>
            <th>Tgl Lahir / Usia</th>
            <td>{{ $case->patient?->birth_date?->format('d M Y') ?? '-' }} ({{ $case->patient?->ageLabel() ?? '-' }})</td>
            <th>Jenis Kelamin &amp; Alamat</th>
            <td>{{ $case->patient?->gender?->label() ?? '-' }} • {{ $case->patient?->address ?: '-' }}</td>
        </tr>
        <tr>
            <th>Keluarga / Wali</th>
            <td>
                {{ $case->patient?->guardian_name ?: '-' }}
                @if($case->patient?->guardian_relation) ({{ $case->patient->guardian_relation }}) @endif
                @if($case->patient?->guardian_phone) • Telp: {{ $case->patient->guardian_phone }} @endif
            </td>
            <th>No. Kunjungan &amp; Masuk IGD</th>
            <td>{{ $case->igd_visit_number ?: '-' }} • {{ $case->igd_admitted_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }} WIB</td>
        </tr>
        <tr>
            <th>Asal Pasien / Kelas</th>
            <td>{{ $case->origin_ward }} {{ $case->origin_class ? "({$case->origin_class})" : '' }}</td>
            <th>Masuk OK / Jadwal</th>
            <td>{{ $case->admitted_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} / {{ $case->scheduled_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
        </tr>
        <tr>
            <th>Diagnosis Pra-Op</th>
            <td>{{ $case->diagnosisDisplay() }}</td>
            <th>Diagnosis Pasca-Op</th>
            <td>{{ $case->postop_diagnosis ?: '-' }}</td>
        </tr>
        <tr>
            <th>Jenis &amp; Lokasi Operasi</th>
            <td>{{ $case->surgeryTypeDisplay() }} - {{ $case->surgery_site ?? '-' }}</td>
            <th>DPJP</th>
            <td>{{ $case->dpjp_name ?: '-' }}</td>
        </tr>
        <tr>
            <th>Kamar Operasi</th>
            <td>{{ $case->room ? "{$case->room->code} - {$case->room->name}" : 'Belum ditetapkan' }}</td>
            <th>Jenis Anestesi</th>
            <td>{{ $case->anesthesiaType ? "{$case->anesthesiaType->code} - {$case->anesthesiaType->name}" : '-' }}</td>
        </tr>
        <tr>
            <th>Tim Dokter</th>
            <td>Operator: {{ $case->surgeon?->name ?? '-' }}<br>Anestesi: {{ $case->anesthesiologist?->name ?? '-' }}</td>
            <th>Tim Perawat</th>
            <td>Sirkuler: {{ $case->circulatingNurse?->name ?? '-' }}<br>Instrumen: {{ $case->scrubNurse?->name ?? '-' }}<br>Anestesi: {{ $case->anesthesiaNurse?->name ?? '-' }}</td>
        </tr>
    </table>

    {{-- 2. PRE-OPERASI --}}
    <h2>2. Pengkajian & Persiapan Pre-Operasi ({{ $isNeuroPdf ? 'Profil Bedah Saraf - EDH' : 'Profil Bedah Ortopedi - Fraktur Femur' }})</h2>
    @if($case->preopAssessment)
        @php $pre = $case->preopAssessment; @endphp
        <table>
            <tr>
                <th style="width: 22%;">Keluhan Utama</th>
                <td>{{ $pre->chief_complaint ?: '-' }}</td>
                <th style="width: 20%;">Status Alergi</th>
                <td>{{ $pre->allergy_status->label() }} {{ $pre->allergy_detail ? "({$pre->allergy_detail})" : '' }}</td>
            </tr>
            <tr>
                <th>Riwayat Penyakit</th>
                <td>
                    @if($isNeuroPdf)
                        {{ $pre->past_illness_history ?: '-' }}
                    @else
                        Sekarang: {{ $pre->current_illness_history ?: '-' }}<br>Dahulu: {{ $pre->past_illness_history ?: '-' }}
                    @endif
                </td>
                <th>Status Puasa</th>
                <td>{{ $pre->fasting_status ? 'Ya, sejak '.($pre->fasting_since?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-') : 'Tidak' }}</td>
            </tr>
            <tr>
                @if(!$isNeuroPdf)
                    <th>Asesmen Psikologis</th>
                    <td>Kecemasan: {{ $pre->anxiety_level->label() }}<br>Kesiapan Mental: {{ $pre->mental_readiness ?: '-' }}</td>
                    <th>Status Kesiapan &amp; GDS</th>
                    <td><strong>{{ $pre->readiness_status->label() }}</strong> • GDS: {{ $pVit['gds'] ?: '-' }}<br><span class="small">{{ $pre->evaluation_notes ?: '-' }}</span></td>
                @else
                    <th>Status Kesiapan</th>
                    <td><strong>{{ $pre->readiness_status->label() }}</strong><br><span class="small">{{ $pre->evaluation_notes ?: '-' }}</span></td>
                    <th>Gula Darah Sewaktu (GDS)</th>
                    <td>{{ $pVit['gds'] ?: '-' }}</td>
                @endif
            </tr>
        </table>

        <table>
            <tr>
                <th style="width: 22%;">Riwayat Kejadian / Trauma</th>
                <td>
                    Penyebab: {{ $pInc['injury_cause'] ?: '-' }} • Waktu: {{ $pInc['incident_datetime'] ?: '-' }} • Lokasi: {{ $pInc['incident_location'] ?: '-' }}<br>
                    Hilang Kesadaran: {{ $pInc['loss_of_consciousness'] ?: '-' }} • Pengencer Darah: {{ $pInc['anticoagulant_use'] ?: '-' }}
                    @if($isNeuroPdf)
                        <br>Sakit Kepala: {{ $pInc['headache_history'] ?: '-' }} • Mual/Muntah: {{ $pInc['nausea_vomiting'] ?: '-' }} • Kejang: {{ $pInc['seizure_history'] ?: '-' }}
                    @else
                        <br>Lokasi Nyeri: {{ $pInc['pain_location'] ?: '-' }}
                    @endif
                </td>
            </tr>
            @if($isNeuroPdf)
                <tr>
                    <th>Pemeriksaan Neurologis</th>
                    <td>
                        Kesadaran: {{ $pNeuro['consciousness_level'] ?: '-' }} (GCS E{{ $pNeuro['gcs_e'] ?: '-' }} V{{ $pNeuro['gcs_v'] ?: '-' }} M{{ $pNeuro['gcs_m'] ?: '-' }})<br>
                        Pupil Ka/Ki: {{ $pNeuro['pupil_size_right'] ?: '-' }} / {{ $pNeuro['pupil_size_left'] ?: '-' }} (Cahaya: {{ $pNeuro['pupil_light_reaction'] ?: '-' }})<br>
                        Motorik Ka/Ki: {{ $pNeuro['motor_right'] ?: '-' }} / {{ $pNeuro['motor_left'] ?: '-' }} • Sensorik: {{ $pNeuro['sensory_response'] ?: '-' }}<br>
                        Kejang: {{ $pNeuro['seizure_status'] ?: '-' }} • Perubahan Pemantauan: {{ $pNeuro['neuro_change_during_monitoring'] ?: '-' }}
                    </td>
                </tr>
                <tr>
                    <th>Hasil CT Scan Kepala</th>
                    <td>
                        {{ $pSup['ct_scan_result'] ?: '-' }}<br>
                        Lokasi/Sisi: {{ $pSup['hematoma_location'] ?: '-' }} ({{ $pSup['hematoma_side'] ?: '-' }}) • Ketebalan: {{ $pSup['hematoma_thickness'] ?: '-' }} • Midline Shift: {{ $pSup['midline_shift'] ?: '-' }}<br>
                        Fraktur Tengkorak: {{ $pSup['skull_fracture'] ?: '-' }} • Perdarahan Penyerta: {{ $pSup['associated_bleeding'] ?: '-' }}
                    </td>
                </tr>
            @else
                <tr>
                    <th>Status Lokalis Fraktur Femur</th>
                    <td>
                        Lokasi/Sisi: {{ $pExt['fracture_location'] ?: '-' }} ({{ $pExt['fracture_side'] ?: '-' }}) • Jenis/Pola: {{ $pExt['fracture_type'] ?: '-' }} / {{ $pExt['fracture_pattern'] ?: '-' }}<br>
                        Kulit/Luka Terbuka: {{ $pExt['skin_condition'] ?: '-' }} / {{ $pExt['open_wound'] ?: '-' }} • Perdarahan: {{ $pExt['bleeding'] ?: '-' }} • Bengkak: {{ $pExt['swelling'] ?: '-' }}<br>
                        Deformitas: {{ $pExt['deformity'] ?: '-' }} • Nyeri NRS: {{ $pExt['pain_nrs'] ?: '-' }} • Gerakan: {{ $pExt['extremity_movement'] ?: '-' }}
                    </td>
                </tr>
                <tr>
                    <th>Neurovaskular Distal (5P)</th>
                    <td>
                        Warna/Suhu Kaki: {{ $pNv['foot_skin_color'] ?: '-' }} / {{ $pNv['foot_skin_temp'] ?: '-' }} • CRT: {{ $pNv['crt'] ?: '-' }} • Nadi: {{ $pNv['peripheral_pulse'] ?: '-' }}<br>
                        Sensasi/Gerakan Jari: {{ $pNv['foot_sensation'] ?: '-' }} / {{ $pNv['toe_movement'] ?: '-' }} • Kesemutan/Baal: {{ $pNv['tingling'] ?: '-' }} / {{ $pNv['numbness'] ?: '-' }}
                    </td>
                </tr>
                <tr>
                    <th>Hasil Rontgen Femur</th>
                    <td>
                        {{ $pSup['xray_femur_result'] ?: '-' }} • Lokasi/Sisi: {{ $pSup['xray_location_side'] ?: '-' }}<br>
                        Pola/Pergeseran: {{ $pSup['xray_pattern'] ?: '-' }} • CT Scan Tambahan: {{ $pSup['ct_scan_note'] ?: '-' }}
                    </td>
                </tr>
            @endif
            <tr>
                <th>Laboratorium Pra-Op</th>
                <td>
                    Hb: {{ $pSup['hb'] ?: '-' }} • Ht: {{ $pSup['ht'] ?: '-' }} • Leukosit: {{ $pSup['leukosit'] ?: '-' }} • Trombosit: {{ $pSup['trombosit'] ?: '-' }} • Gol. Darah: {{ $pSup['blood_type'] ?: '-' }}<br>
                    @if(!$isNeuroPdf)PT/INR: {{ $pSup['pt_inr'] ?: '-' }} • aPTT: {{ $pSup['aptt'] ?: '-' }} • @endif
                    Elektrolit: {{ $pSup['electrolytes'] ?: '-' }} • Gula Darah: {{ $pSup['blood_glucose'] ?: '-' }} • Ginjal: {{ $pSup['renal_function'] ?: '-' }}
                </td>
            </tr>
            <tr>
                <th>Persiapan &amp; Persetujuan Khusus</th>
                <td>
                    Keputusan Operasi: {{ $pPrep['decision_time'] ?: '-' }} • Cairan Infus: {{ $pPrep['iv_fluid_detail'] ?: '-' }} • Persiapan Darah: {{ $pPrep['blood_prep'] ?: '-' }}<br>
                    Antibiotik: {{ $pPrep['antibiotic_instruction'] ?: '-' }} • Penanda Tangan Consent: {{ $pPrep['consent_signer'] ?: '-' }} ({{ $pPrep['consent_relation'] ?: '-' }}) • Darurat: {{ $pPrep['emergency_consent_doc'] ?: '-' }}
                    @if(!$isNeuroPdf)<br>Persiapan Alat &amp; Implan ORIF: {{ $pPrep['implant_instrument_prep'] ?: '-' }}@endif
                </td>
            </tr>
            <tr>
                <th>Pra-Anestesi &amp; Pelaksanaan</th>
                <td>
                    Jalan Napas: {{ $pAnPre['airway_exam'] ?: '-' }} • Kardiopulmonal: {{ $pAnPre['cardiopulmonary'] ?: '-' }} • ASA: {{ $pAnPre['asa_class'] ?: '-' }} • Rencana: {{ $pAnPre['planned_anesthesia'] ?: '-' }}<br>
                    Metode: {{ $pAnExec['anesthesia_method'] ?: '-' }} ({{ $pAnExec['anesthesia_start_time'] ?: '-' }} - {{ $pAnExec['anesthesia_end_time'] ?: '-' }}) • ETT: {{ $pAnExec['ett_type_size'] ?: '-' }} ({{ $pAnExec['ett_depth_cm'] ?: '-' }} cm)@if(!$isNeuroPdf) / {{ $pAnExec['airway_aid'] ?: '-' }}@endif<br>
                    Ventilator: {{ $pAnExec['ventilator_setting'] ?: '-' }} • Obat/Dosis: {{ $pAnExec['medications_summary'] ?: '-' }} ({{ $pAnExec['medication_dose'] ?: '-' }} @ {{ $pAnExec['medication_time'] ?: '-' }}) • Cairan: {{ $pAnExec['fluids_given'] ?: '-' }} • Komplikasi: {{ $pAnExec['complications'] ?: '-' }}
                </td>
            </tr>
        </table>
    @else
        <p class="small">Belum ada data asesmen pra-operasi.</p>
    @endif

    @if($preVitals->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>TTV Pre-Op (Waktu)</th>
                    <th>TD (mmHg)</th>
                    <th>Nadi</th>
                    <th>Suhu</th>
                    <th>RR</th>
                    <th>SpO2</th>
                    <th>BB/TB</th>
                    <th>Kesadaran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($preVitals as $v)
                    <tr>
                        <td>{{ $v->recorded_at->timezone('Asia/Jakarta')->format('d/m H:i') }}</td>
                        <td>{{ $v->bp_systolic ?? '-' }}/{{ $v->bp_diastolic ?? '-' }}</td>
                        <td>{{ $v->pulse ?? '-' }}</td>
                        <td>{{ $v->temperature ?? '-' }}°C</td>
                        <td>{{ $v->respiratory_rate ?? '-' }}</td>
                        <td>{{ $v->spo2 ?? '-' }}%</td>
                        <td>{{ $v->weight_kg ?? '-' }} kg / {{ $v->height_cm ?? '-' }} cm</td>
                        <td>{{ $v->consciousness->label() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($case->supportingExams->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Pemeriksaan Penunjang</th>
                    <th>Hasil</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($case->supportingExams as $exam)
                    <tr>
                        <td>{{ $exam->exam_type->label() }}</td>
                        <td>{{ $exam->result }}</td>
                        <td>{{ $exam->exam_date?->format('d M Y') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- 3. PERSIAPAN & CEK ALAT --}}
    <h2>3. Cek Ketersediaan & Kesterilan Alat Operasi</h2>
    @if($case->instrumentChecks->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Nama Alat</th>
                    <th>Tersedia</th>
                    <th>Steril</th>
                    <th>Berfungsi</th>
                    <th>Berkarat</th>
                    <th>Catatan Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($case->instrumentChecks as $chk)
                    <tr>
                        <td>{{ $chk->instrument?->name ?? '-' }}</td>
                        <td>{{ $chk->is_available ? 'Ya' : 'Tidak' }}</td>
                        <td>{{ $chk->is_sterile ? 'Ya' : 'Tidak' }}</td>
                        <td>{{ $chk->is_functional ? 'Ya' : 'Tidak' }}</td>
                        <td>{{ $chk->is_rusty ? 'Ya' : 'Tidak' }}</td>
                        <td>{{ $chk->note ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="small">Belum ada data cek alat operasi.</p>
    @endif

    {{-- 4. SURGICAL SAFETY CHECKLIST (SSC) --}}
    <h2>4. Surgical Safety Checklist (Sign In, Time Out, Sign Out)</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Tahap SSC</th>
                <th style="width: 18%;">Status</th>
                <th style="width: 22%;">Waktu & Petugas</th>
                <th>Ringkasan & Catatan Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Sign In</strong></td>
                <td>{{ $case->sscSignIn?->status?->label() ?? 'Belum Dimulai' }}</td>
                <td>{{ $case->sscSignIn?->signed_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }}<br><span class="small">{{ $case->sscSignIn?->filler?->name ?? '-' }}</span></td>
                <td>
                    @if($case->sscSignIn)
                        Identitas: {{ $case->sscSignIn->identity_confirmed?->label() ?? '-' }},
                        Prosedur: {{ $case->sscSignIn->procedure_confirmed?->label() ?? '-' }},
                        Alergi: {{ $case->sscSignIn->allergy_status?->label() ?? '-' }},
                        Risiko Jalan Napas: {{ $case->sscSignIn->difficult_airway_risk?->label() ?? '-' }},
                        Risiko Perdarahan: {{ $case->sscSignIn->bleeding_risk?->label() ?? '-' }}.
                        @if($case->sscSignIn->notes_followup)<br>Tindak lanjut: {{ $case->sscSignIn->notes_followup }}@endif
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Time Out</strong></td>
                <td>{{ $case->sscTimeOut?->status?->label() ?? 'Belum Dimulai' }}</td>
                <td>{{ $case->sscTimeOut?->signed_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }}<br><span class="small">{{ $case->sscTimeOut?->filler?->name ?? '-' }}</span></td>
                <td>
                    @if($case->sscTimeOut)
                        Antibiotik: {{ $case->sscTimeOut->antibiotic_prophylaxis ?? '-' }} {{ $case->sscTimeOut->antibiotic_name ? "({$case->sscTimeOut->antibiotic_name})" : '' }};
                        Estimasi Durasi: {{ $case->sscTimeOut->surgeon_estimated_duration_min ?? '-' }} mnt;
                        Sterilitas: {{ $case->sscTimeOut->nurse_sterility_confirmed ? 'Dikonfirmasi' : 'Belum' }}.
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Sign Out</strong></td>
                <td>{{ $case->sscSignOut?->status?->label() ?? 'Draf' }}</td>
                <td>{{ $case->sscSignOut?->signed_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }}<br><span class="small">{{ $case->sscSignOut?->filler?->name ?? '-' }}</span></td>
                <td>
                    @if($case->sscSignOut)
                        Prosedur: {{ $case->sscSignOut->q1_procedure_confirmed ? 'Ya' : 'Belum' }},
                        Hitungan: {{ $case->sscSignOut->q2_counts_correct ? 'Sesuai' : 'Tidak/Belum' }},
                        Spesimen: {{ $case->sscSignOut->q3_specimen_labeled ?? '-' }}.
                        @if($case->sscSignOut->recovery_notes)<br>Pemulihan: {{ $case->sscSignOut->recovery_notes }}@endif
                        @if($case->sscSignOut->issues_and_actions)<br>Masalah/Tindakan: {{ $case->sscSignOut->issues_and_actions }}@endif
                    @else
                        -
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    {{-- 5. INTRA-OPERASI --}}
    <h2>5. Dokumentasi Intra-Operasi</h2>
    <table>
        <tr>
            <th>Masuk OK</th>
            <th>Mulai Anestesi</th>
            <th>Mulai Insisi</th>
            <th>Selesai Operasi</th>
            @if(!$isNeuroPdf)<th>Keluar OK</th>@endif
            <th>Selesai Anestesi</th>
        </tr>
        <tr>
            <td>{{ $case->entered_or_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
            <td>{{ $case->anesthesia_start_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
            <td>{{ $case->incision_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
            <td>{{ $case->surgery_end_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
            @if(!$isNeuroPdf)<td>{{ $case->left_or_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>@endif
            <td>{{ $case->anesthesia_end_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
        </tr>
    </table>

    @if($case->intraopRecord)
        @php
            $ir = $case->intraopRecord;
            $ip = $intraopProcedure ?? [];
        @endphp
        <table>
            @if($isNeuroPdf)
                <tr>
                    <th style="width: 22%;">Posisi Pembedahan</th>
                    <td>{{ $ir->position?->label() ?? '-' }}</td>
                    <th style="width: 22%;">Kondisi Kulit (Pre / Post)</th>
                    <td>Pre: {{ $ir->skin_condition_pre?->label() ?? '-' }}, Post: {{ $ir->skin_condition_post?->label() ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Persiapan Area (Draping)</th>
                    <td>{{ $ir->draping_done ? 'Draping Steril Baik' : 'Belum / Tidak' }}</td>
                    <th>Komplikasi Intra-Op</th>
                    <td>{{ $ir->has_complication ? "{$ir->complication_description} (Penanganan: {$ir->complication_handling})" : 'Tidak ada' }}</td>
                </tr>
            @else
                <tr>
                    <th style="width: 22%;">Posisi Pembedahan</th>
                    <td>{{ $ir->position?->label() ?? '-' }}</td>
                    <th style="width: 22%;">Grounding ESU</th>
                    <td>{{ $ir->grounding_plate_site ?: '-' }} (Pre: {{ $ir->skin_condition_pre?->label() ?? '-' }}, Post: {{ $ir->skin_condition_post?->label() ?? '-' }})</td>
                </tr>
                <tr>
                    <th>Suhu Kamar / Draping</th>
                    <td>{{ $ir->room_temperature_c ? "{$ir->room_temperature_c}°C" : '-' }} / Draping: {{ $ir->draping_done ? 'Ya' : 'Tidak' }}</td>
                    <th>Komplikasi Intra-Op</th>
                    <td>{{ $ir->has_complication ? "{$ir->complication_description} (Penanganan: {$ir->complication_handling})" : 'Tidak ada' }}</td>
                </tr>
            @endif
            <tr>
                <th>Tindakan Aktual &amp; Lokasi Insisi</th>
                <td>{{ $case->operation_plan_actual ?: '-' }}<br><span class="small">Insisi: {{ $ip['incision_location_side'] ?: '-' }}</span></td>
                <th>Penutupan &amp; Kondisi Luka</th>
                <td>
                    Drain: {{ $ip['drain_placement'] ?: '-' }} • Jahitan: {{ $ip['wound_closure'] ?: '-' }}<br>
                    Luka Pasca-Op: {{ $ip['postop_wound_condition'] ?: '-' }}
                </td>
            </tr>
            @if($isNeuroPdf)
                <tr>
                    <th>Detail Kraniotomi &amp; EDH</th>
                    <td colspan="3">
                        Lokasi Kraniotomi: {{ $ip['craniotomy_location'] ?: '-' }} • Tulang/Jaringan: {{ $ip['bone_tissue_condition'] ?: '-' }} • Hematoma: {{ $ip['hematoma_volume_condition'] ?: '-' }}<br>
                        Pembukaan Tulang: {{ $ip['craniotomy_opening_process'] ?: '-' }} • Evakuasi: {{ $ip['hematoma_evacuation_process'] ?: '-' }}<br>
                        Hemostasis: {{ $ip['hemostasis_action'] ?: '-' }} • Kondisi Otak: {{ $ip['brain_tissue_condition'] ?: '-' }} • Bone Flap: {{ $ip['bone_flap_management'] ?: '-' }}
                    </td>
                </tr>
            @else
                <tr>
                    <th>Detail Reduksi &amp; Fiksasi ORIF</th>
                    <td colspan="3">
                        Lokasi Fraktur: {{ $ip['fracture_location'] ?: '-' }} • Tulang/Jaringan: {{ $ip['bone_tissue_condition'] ?: '-' }}<br>
                        Reduksi ({{ $ip['reduction_type'] ?: '-' }}): {{ $ip['reduction_process'] ?: '-' }} • Fiksasi: {{ $ip['fixation_method'] ?: '-' }}<br>
                        Catatan Implan: {{ $ip['implant_placement_notes'] ?: '-' }} • C-Arm/Fluoroskopi: {{ $ip['fluoroscopy_check'] ?: '-' }}
                    </td>
                </tr>
            @endif
            <tr>
                <th>Transfusi, Kauter &amp; Alat</th>
                <td colspan="3">
                    Transfusi: {{ !empty($ip['blood_transfusion_given']) ? 'Ya (' . ($ip['blood_product_type_amount'] ?: '-') . ')' : 'Tidak' }} •
                    Kauter: {{ $ip['cautery_usage'] ?: '-' }} • Alat/BHP: {{ $ip['equipment_usage_notes'] ?: '-' }}
                </td>
            </tr>
        </table>

        @if(!empty($intraopImplants))
            <table>
                <thead>
                    <tr>
                        <th>Jenis Implan</th>
                        <th>Ukuran</th>
                        <th>Merk</th>
                        <th>No. Seri / Lot</th>
                        <th>Lokasi Pasang</th>
                        <th>Siap / Terpakai</th>
                        <th>Tidak Terpakai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($intraopImplants as $imp)
                        <tr>
                            <td>{{ $imp['implant_type'] ?? '-' }}</td>
                            <td>{{ $imp['size'] ?? '-' }}</td>
                            <td>{{ $imp['brand'] ?? '-' }}</td>
                            <td>{{ $imp['serial_lot'] ?? '-' }}</td>
                            <td>{{ $imp['placement_site'] ?? '-' }}</td>
                            <td>{{ $imp['planned_qty'] ?? '-' }} / {{ $imp['used_qty'] ?? '-' }}</td>
                            <td>{{ $imp['unused_doc'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    @if($intraVitals->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>TTV Intra-Op</th>
                    <th>TD</th>
                    <th>Nadi</th>
                    <th>Suhu</th>
                    <th>RR</th>
                    <th>SpO2</th>
                    <th>Pola Napas & Oksigen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($intraVitals as $v)
                    <tr>
                        <td>{{ $v->recorded_at->timezone('Asia/Jakarta')->format('H:i') }}</td>
                        <td>{{ $v->bp_systolic ?? '-' }}/{{ $v->bp_diastolic ?? '-' }}</td>
                        <td>{{ $v->pulse ?? '-' }}</td>
                        <td>{{ $v->temperature ?? '-' }}°C</td>
                        <td>{{ $v->respiratory_rate ?? '-' }}</td>
                        <td>{{ $v->spo2 ?? '-' }}%</td>
                        <td>{{ $v->breathing_pattern?->label() ?? '-' }} / {{ $v->oxygen_device?->label() ?? '-' }} {{ $v->oxygen_flow_lpm ? "({$v->oxygen_flow_lpm} LPM)" : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table>
        <tr>
            <th style="width: 25%;">Total Cairan Intra-Op</th>
            <td>Intake: <strong>{{ $intraTotalIntake }} ml</strong> | Output: <strong>{{ $intraTotalOutput }} ml</strong> | Balans: <strong>{{ $intraTotalIntake - $intraTotalOutput }} ml</strong></td>
            <th style="width: 20%;">Obat Intra-Op</th>
            <td>
                @if($case->medications->isNotEmpty())
                    {{ $case->medications->map(fn ($m) => "{$m->name} ({$m->dose})")->implode(', ') }}
                @else
                    -
                @endif
            </td>
        </tr>
    </table>

    @if($case->instrumentCounts->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Hitung Instrumen / Kasa / Jarum</th>
                    <th>Awal</th>
                    <th>Tambahan</th>
                    <th>Akhir</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($case->instrumentCounts as $cnt)
                    <tr>
                        <td>{{ $cnt->item->label() }}</td>
                        <td>{{ $cnt->initial_count }}</td>
                        <td>{{ $cnt->additional_count }}</td>
                        <td>{{ $cnt->final_count ?? '-' }}</td>
                        <td><strong>{{ $cnt->status?->value ?? '-' }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="small">
            Verifikasi Akhir: <strong>{{ $case->intraopRecord?->count_verification?->label() ?? '-' }}</strong>
            @if($case->intraopRecord?->count_discrepancy_actions)
                • Tindak Lanjut: {{ implode(', ', $case->intraopRecord->count_discrepancy_actions) }}
            @endif
        </p>
    @endif

    {{-- 6. PASCA-OPERASI --}}
    <h2>6. Observasi Pasca-Operasi & Kondisi Sebelum Dipindahkan</h2>
    @if($case->postopObservations->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>GCS & Kondisi</th>
                    <th>Jalan Napas</th>
                    <th>Nyeri NRS</th>
                    <th>Luka & Balutan</th>
                    <th>Pemulihan &amp; Detail Profil</th>
                </tr>
            </thead>
            <tbody>
                @foreach($case->postopObservations->sortBy('recorded_at') as $obs)
                    @php $oc = is_array($obs->clinical_details) ? $obs->clinical_details : []; @endphp
                    <tr>
                        <td>{{ $obs->recorded_at->timezone('Asia/Jakarta')->format('H:i') }}</td>
                        <td>GCS {{ $obs->gcsTotal() }} (E{{ $obs->gcs_eye }}V{{ $obs->gcs_verbal }}M{{ $obs->gcs_motor }}), {{ $obs->general_condition->label() }} / {{ $obs->response->label() }}</td>
                        <td>{{ $obs->airway_patency->label() }}</td>
                        <td>{{ $obs->pain_nrs }}/10</td>
                        <td>{{ $obs->wound_location ?: '-' }} ({{ $obs->dressing?->label() ?? '-' }}, {{ $obs->suture_condition?->label() ?? '-' }})</td>
                        <td>
                            {{ $obs->anesthesia_recovery?->label() ?? '-' }}{{ $obs->nausea_vomiting ? ', Mual/Muntah' : '' }}{{ $obs->shivering_hypothermia ? ', Menggigil' : '' }}
                            @if(!empty($oc['anesthesia_recovery_score']))<br>Skor: {{ $oc['anesthesia_recovery_score'] }}@endif
                            @if($isNeuroPdf)
                                @if(!empty($oc['pupil_size_right']) || !empty($oc['extremity_motor']))
                                    <br>Pupil: {{ $oc['pupil_size_right'] ?? '-' }}/{{ $oc['pupil_size_left'] ?? '-' }} ({{ $oc['pupil_light_reaction'] ?? '-' }}) • Motorik: {{ $oc['extremity_motor'] ?? '-' }}
                                @endif
                            @else
                                @if(!empty($oc['crt']) || !empty($oc['peripheral_pulse']))
                                    <br>Kaki: {{ $oc['foot_skin_color'] ?? '-' }}, CRT {{ $oc['crt'] ?? '-' }}, Nadi {{ $oc['peripheral_pulse'] ?? '-' }}
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="small">Belum ada observasi pasca-operasi.</p>
    @endif

    @if($case->postopSummary)
        @php $pf = $postopFollowup ?? []; @endphp
        <table>
            <tr>
                <th style="width: 22%;">Kelayakan Pindah</th>
                <td><strong>{{ $case->postopSummary->ready_to_transfer ? 'Layak Dipindahkan' : 'Belum Layak' }}</strong> - {{ $case->postopSummary->pre_transfer_condition_notes ?: '-' }}</td>
            </tr>
            <tr>
                <th>Obat @if(!$isNeuroPdf), Cairan @endif&amp; Instruksi</th>
                <td>
                    Obat: {{ $pf['medications_given'] ?: '-' }}@if(!$isNeuroPdf) • Cairan: {{ $pf['iv_fluid_plan'] ?: '-' }}@endif<br>
                    Instruksi Dokter: {{ $pf['doctor_postop_instructions'] ?: '-' }}
                    @if($isNeuroPdf)
                        <br>Pemantauan Neurologis: {{ $pf['neuro_followup_monitoring'] ?: '-' }}
                    @else
                        <br>Mobilisasi: {{ $pf['mobilization_instruction'] ?: '-' }} • Beban: {{ $pf['weight_bearing_instruction'] ?: '-' }} • Bidai: {{ $pf['splint_immobilization'] ?: '-' }} • Radiologi: {{ $pf['radiology_plan'] ?: '-' }}
                    @endif
                </td>
            </tr>
        </table>
    @endif

    {{-- 7. SERAH TERIMA --}}
    <h2>7. Ringkasan Serah Terima Pasien (12 Item SKPL V.E)</h2>
    <table>
        <tbody>
            @foreach($handoverLabels as $key => $label)
                <tr>
                    <th style="width: 32%;">{{ $label }}</th>
                    <td>{{ $handoverSummary[$key] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($case->handover)
        <table>
            <tr>
                <th style="width: 22%;">Tujuan Pemindahan</th>
                <td>{{ $case->handover->destination->label() }} {{ $case->handover->destination_detail ? "({$case->handover->destination_detail})" : '' }}</td>
                <th style="width: 22%;">Waktu Serah Terima</th>
                <td>{{ $case->handover->handed_over_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
            </tr>
            <tr>
                <th>Petugas Penyerah</th>
                <td>{{ $case->handover->handedBy?->name ?? '-' }}</td>
                <th>Petugas Penerima</th>
                <td>{{ $case->handover->received_by_name }}</td>
            </tr>
        </table>
    @endif

    {{-- 8. PENGESAHAN & VERIFIKASI --}}
    <h2>8. Pengesahan & Verifikasi Petugas</h2>
    @if($activeSignoffs->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Bagian</th>
                    <th>Jenis</th>
                    <th>Peran</th>
                    <th>Nama Petugas</th>
                    <th>NIP / SIP</th>
                    <th>Waktu (WIB)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activeSignoffs as $s)
                    <tr>
                        <td>{{ $s->section->label() }}</td>
                        <td>{{ $s->kind->label() }}</td>
                        <td>{{ $s->role_label }}</td>
                        <td>{{ $s->user?->name ?? '-' }}</td>
                        <td>{{ $s->sip_snapshot ?: ($s->nip_snapshot ?: '-') }}</td>
                        <td>{{ $s->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="small">Belum ada pengesahan atau verifikasi yang tercatat.</p>
    @endif
</body>
</html>
