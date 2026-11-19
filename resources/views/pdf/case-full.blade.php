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
            border-bottom: 2px solid #0f766e;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f766e;
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
        }
        h2 {
            font-size: 10px;
            background: #f0fdfa;
            border-left: 3px solid #0f766e;
            padding: 4px 6px;
            margin: 10px 0 5px 0;
            color: #134e4a;
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
    <h2>1. Identitas Pasien & Informasi Kasus Operasi</h2>
    <table>
        <tr>
            <th style="width: 18%;">No. Kasus</th>
            <td style="width: 32%;">{{ $case->case_number }}</td>
            <th style="width: 18%;">Status Kasus</th>
            <td style="width: 32%;">{{ $case->status->label() }} (Kelengkapan: {{ $completeness['percentage'] }}%)</td>
        </tr>
        <tr>
            <th>Nama Pasien</th>
            <td>{{ $case->patient?->name ?? '-' }}</td>
            <th>No. Rekam Medis</th>
            <td>{{ $case->patient?->rm_number ?? '-' }}</td>
        </tr>
        <tr>
            <th>Tgl Lahir / Usia</th>
            <td>{{ $case->patient?->birth_date?->format('d M Y') ?? '-' }} ({{ $case->patient?->ageLabel() ?? '-' }})</td>
            <th>Jenis Kelamin</th>
            <td>{{ $case->patient?->gender?->label() ?? '-' }}</td>
        </tr>
        <tr>
            <th>Ruang Asal / Kelas</th>
            <td>{{ $case->origin_ward }} {{ $case->origin_class ? "({$case->origin_class})" : '' }}</td>
            <th>Jadwal Operasi</th>
            <td>{{ $case->scheduled_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
        </tr>
        <tr>
            <th>Diagnosis Medis</th>
            <td>{{ $case->diagnosisDisplay() }}</td>
            <th>Jenis & Lokasi Operasi</th>
            <td>{{ $case->surgeryTypeDisplay() }} - {{ $case->surgery_site ?? '-' }}</td>
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
    <h2>2. Pengkajian & Persiapan Pre-Operasi</h2>
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
                <td>Sekarang: {{ $pre->current_illness_history ?: '-' }}<br>Dahulu: {{ $pre->past_illness_history ?: '-' }}</td>
                <th>Status Puasa</th>
                <td>{{ $pre->fasting_status ? 'Ya, sejak '.($pre->fasting_since?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-') : 'Tidak' }}</td>
            </tr>
            <tr>
                <th>Asesmen Psikologis</th>
                <td>Kecemasan: {{ $pre->anxiety_level->label() }}<br>Kesiapan Mental: {{ $pre->mental_readiness ?: '-' }}</td>
                <th>Status Kesiapan</th>
                <td><strong>{{ $pre->readiness_status->label() }}</strong><br><span class="small">{{ $pre->evaluation_notes ?: '-' }}</span></td>
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
            <th>Keluar OK</th>
        </tr>
        <tr>
            <td>{{ $case->entered_or_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
            <td>{{ $case->anesthesia_start_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
            <td>{{ $case->incision_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
            <td>{{ $case->surgery_end_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
            <td>{{ $case->left_or_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB</td>
        </tr>
    </table>

    @if($case->intraopRecord)
        @php $ir = $case->intraopRecord; @endphp
        <table>
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
        </table>
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
                    <th>Efek Anestesi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($case->postopObservations->sortBy('recorded_at') as $obs)
                    <tr>
                        <td>{{ $obs->recorded_at->timezone('Asia/Jakarta')->format('H:i') }}</td>
                        <td>GCS {{ $obs->gcsTotal() }} (E{{ $obs->gcs_eye }}V{{ $obs->gcs_verbal }}M{{ $obs->gcs_motor }}), {{ $obs->general_condition->label() }} / {{ $obs->response->label() }}</td>
                        <td>{{ $obs->airway_patency->label() }}</td>
                        <td>{{ $obs->pain_nrs }}/10</td>
                        <td>{{ $obs->wound_location ?: '-' }} ({{ $obs->dressing?->label() ?? '-' }}, {{ $obs->suture_condition?->label() ?? '-' }})</td>
                        <td>{{ $obs->anesthesia_recovery?->label() ?? '-' }}{{ $obs->nausea_vomiting ? ', Mual/Muntah' : '' }}{{ $obs->shivering_hypothermia ? ', Menggigil' : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="small">Belum ada observasi pasca-operasi.</p>
    @endif

    @if($case->postopSummary)
        <p class="small">
            <strong>Kelayakan Pindah:</strong> {{ $case->postopSummary->ready_to_transfer ? 'Layak Dipindahkan' : 'Belum Layak' }} -
            {{ $case->postopSummary->pre_transfer_condition_notes ?: '-' }}
        </p>
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
