<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Serah Terima Pasien - {{ $case->case_number }}</title>
    <style>
        @page {
            margin: 18mm 15mm 18mm 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.4;
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
            font-size: 14px;
            font-weight: bold;
            color: #0f766e;
        }
        .header-sub {
            font-size: 8.5px;
            color: #4b5563;
        }
        .doc-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f0fdfa;
            color: #134e4a;
            font-weight: bold;
        }
        .meta-table td {
            border: none;
            padding: 2px 4px;
        }
        .sig-table td {
            border: 1px solid #d1d5db;
            width: 50%;
            text-align: center;
            padding: 8px;
        }
        .sig-space {
            height: 45px;
        }
    </style>
</head>
<body>
    <div class="footer">
        <table class="meta-table" style="margin: 0;">
            <tr>
                <td>Lembar Serah Terima Pasien OKA • No. Kasus: {{ $case->case_number }}</td>
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

    <div class="doc-title">Lembar Serah Terima Pasien Kamar Operasi</div>

    <table>
        <tr>
            <th style="width: 24%;">No. Kasus</th>
            <td style="width: 26%;">{{ $case->case_number }}</td>
            <th style="width: 24%;">Kamar Operasi</th>
            <td style="width: 26%;">{{ $case->room ? "{$case->room->code} - {$case->room->name}" : '-' }}</td>
        </tr>
        <tr>
            <th>Tujuan Pemindahan</th>
            <td>
                @if($handover)
                    {{ $handover->destination->label() }}
                    {{ $handover->destination_detail ? "({$handover->destination_detail})" : '' }}
                @else
                    Belum diisi
                @endif
            </td>
            <th>Waktu Serah Terima</th>
            <td>{{ $handover ? $handover->handed_over_at->timezone('Asia/Jakarta')->format('d M Y H:i').' WIB' : '-' }}</td>
        </tr>
        <tr>
            <th>Kelengkapan Dokumen</th>
            <td colspan="3">{{ $handover ? ($handover->documents_complete ? 'Lengkap (Rekam medis & hasil penunjang disertakan)' : 'Belum lengkap') : '-' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 34%;">Komponen Serah Terima (12 Item SKPL V.E)</th>
                <th>Ringkasan Data Klinis</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summaryLabels as $key => $label)
                <tr>
                    <td><strong>{{ $label }}</strong></td>
                    <td>{{ $summaryItems[$key] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sig-table">
        <tr>
            <td>
                <strong>Perawat yang Menyerahkan</strong>
                <div class="sig-space"></div>
                <div><strong>{{ $handover?->handedBy?->name ?? '............................................' }}</strong></div>
                <div style="font-size: 8.5px; color: #4b5563;">NIP: {{ $handover?->handedBy?->nip ?? '-' }}</div>
            </td>
            <td>
                <strong>Petugas yang Menerima</strong>
                <div class="sig-space"></div>
                <div><strong>{{ $handover?->received_by_name ?? '............................................' }}</strong></div>
                <div style="font-size: 8.5px; color: #4b5563;">Unit Tujuan: {{ $handover?->destination?->label() ?? '-' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
