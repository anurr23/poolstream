<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Evaluasi AI PoolStream</title>
    <style>
        @page {
            margin: 16mm 14mm 24mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 9.5px;
            line-height: 1.55;
            color: #1f2937;
            margin: 0;
        }

        .footer {
            position: fixed;
            bottom: -16mm;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            padding: 0;
            font-size: 7.5px;
            color: #9ca3af;
        }

        .footer-table .right {
            text-align: right;
        }

        /* ---------- Header ---------- */
        .brand {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .brand td {
            vertical-align: middle;
            padding: 0;
        }

        .brand-logo {
            width: 56px;
        }

        .brand-logo img {
            height: 42px;
        }

        .brand-text {
            padding-left: 8px;
        }

        .brand-name {
            font-size: 17px;
            font-weight: bold;
            color: #4f46e5;
            letter-spacing: 0.2px;
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
        }

        .brand-meta {
            width: 195px;
            text-align: right;
            font-size: 7.8px;
            color: #6b7280;
            line-height: 1.45;
        }

        .brand-meta .period {
            font-size: 8.6px;
            font-weight: bold;
            color: #111827;
        }

        .rule {
            height: 3px;
            background: #6d28d9;
            margin: 0 0 12px;
        }

        /* ---------- Sections ---------- */
        .section {
            margin-bottom: 13px;
        }

        .section-title {
            font-size: 9.6px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #4c1d95;
            border-bottom: 1.5px solid #6d28d9;
            padding-bottom: 3px;
            margin-bottom: 7px;
        }

        .paragraph {
            text-align: justify;
            color: #374151;
            margin: 0 0 5px;
        }

        /* ---------- Tabel metrik ---------- */
        .kpi {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.4px;
        }

        .kpi th {
            background: #f5f3ff;
            color: #4c1d95;
            text-align: left;
            font-size: 7.2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 4px 6px;
            border-bottom: 1px solid #ddd6fe;
        }

        .kpi td {
            padding: 4px 6px;
            border-bottom: 1px solid #eef0f3;
            vertical-align: top;
            color: #374151;
        }

        .kpi td.metric {
            font-weight: bold;
            color: #111827;
            width: 26%;
        }

        .kpi td.value {
            font-weight: bold;
            white-space: nowrap;
            width: 22%;
        }

        .kpi td.trend {
            white-space: nowrap;
            width: 13%;
            font-size: 7.6px;
            font-weight: bold;
        }

        .trend-up {
            color: #047857;
        }

        .trend-down {
            color: #b91c1c;
        }

        .trend-flat {
            color: #64748b;
        }

        /* ---------- Grafik ---------- */
        .chart {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .chart td {
            padding: 0;
            text-align: center;
        }

        .chart .bar-cell {
            height: 118px;
            vertical-align: bottom;
            border-bottom: 1px solid #cbd5e1;
        }

        .chart .bar {
            width: 52%;
            max-width: 46px;
            margin: 0 auto;
            background: #6366f1;
            border-radius: 2px 2px 0 0;
        }

        .chart .bar-value {
            font-size: 6.6px;
            color: #4b5563;
            padding-bottom: 2px;
        }

        .chart .bar-label {
            font-size: 7px;
            color: #6b7280;
            padding-top: 3px;
            white-space: nowrap;
        }

        .chart-note {
            font-size: 7.8px;
            color: #6b7280;
            margin-top: 5px;
        }

        /* ---------- Analisis & temuan ---------- */
        .analysis-aspect {
            font-size: 7.6px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #6d28d9;
            margin-top: 5px;
        }

        .finding {
            border-left: 3px solid #6366f1;
            background: #fafafa;
            padding: 6px 8px;
            margin-bottom: 5px;
        }

        .finding.high {
            border-left-color: #ef4444;
        }

        .finding.medium {
            border-left-color: #f59e0b;
        }

        .finding.low {
            border-left-color: #10b981;
        }

        .finding-title {
            font-weight: bold;
            font-size: 9px;
            color: #111827;
        }

        .pill {
            font-size: 6.6px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 2px 6px;
            border-radius: 8px;
        }

        .finding-head {
            width: 100%;
            border-collapse: collapse;
        }

        .finding-head td {
            padding: 0;
            vertical-align: top;
        }

        .finding-head .impact {
            text-align: right;
            width: 60px;
        }

        .pill.high {
            background: #fee2e2;
            color: #b91c1c;
        }

        .pill.medium {
            background: #fef3c7;
            color: #b45309;
        }

        .pill.low {
            background: #d1fae5;
            color: #047857;
        }

        .finding-implication {
            color: #374151;
            margin: 2px 0 0;
        }

        .finding-evidence {
            color: #6b7280;
            font-size: 7.6px;
            margin-top: 2px;
        }

        /* ---------- List ---------- */
        .ordered {
            margin: 0;
            padding-left: 15px;
        }

        .ordered li {
            margin-bottom: 6px;
        }

        .ordered .title {
            font-weight: bold;
            color: #111827;
        }

        .ordered .sub {
            color: #4b5563;
        }

        .steps {
            margin: 3px 0 0;
            padding-left: 13px;
            color: #4b5563;
        }

        .steps li {
            margin-bottom: 1px;
        }

        .expected {
            margin-top: 2px;
            font-size: 7.8px;
            color: #047857;
        }

        .risk {
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            padding: 5px 8px;
            margin-bottom: 5px;
        }

        .risk .title {
            font-weight: bold;
            color: #92400e;
            font-size: 8.8px;
        }

        .risk .sub {
            color: #4b5563;
        }

        .conclusion {
            border: 1px solid #ddd6fe;
            background: #f5f3ff;
            padding: 7px 9px;
            color: #374151;
            text-align: justify;
        }

        /* ---------- Data tables ---------- */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .data-table th {
            background: #4f46e5;
            color: #ffffff;
            text-align: left;
            padding: 4px 6px;
            font-size: 7.2px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .data-table td {
            padding: 3px 6px;
            border-bottom: 1px solid #eef0f3;
            color: #374151;
        }

        .text-right {
            text-align: right;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.8px;
            color: #6b7280;
        }

        .meta td {
            padding: 2px 0;
            vertical-align: top;
            width: 50%;
        }

        .meta strong {
            color: #111827;
        }

        .disclaimer {
            margin-top: 7px;
            padding: 6px 9px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 7.6px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    @php
        $presetLabels = [
            'today' => 'Hari ini',
            '7d' => '7 hari terakhir',
            'month' => 'Bulan ini',
            'last_month' => 'Bulan lalu',
            '3m' => '3 bulan terakhir',
            '6m' => '6 bulan terakhir',
            'year' => 'Tahun ini',
            '12m' => '12 bulan terakhir',
            'all' => 'Semua data',
            'custom' => 'Rentang khusus',
        ];
        $trendClass = fn ($trend) => $trend === 'naik' ? 'trend-up' : ($trend === 'turun' ? 'trend-down' : 'trend-flat');
        $impactClass = fn ($impact) => in_array($impact, ['tinggi', 'sedang', 'rendah'], true) ? $impact : 'rendah';
    @endphp

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="left">PoolStream &middot; Laporan Evaluasi AI &middot; Dokumen internal</td>
                <td class="right">{{ $evaluation->start_date->format('d/m/Y') }} &ndash; {{ $evaluation->end_date->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <!-- Header -->
    <table class="brand">
        <tr>
            <td class="brand-logo"><img src="{{ $logo }}" alt="PoolStream"></td>
            <td class="brand-text">
                <div class="brand-name">PoolStream</div>
                <div class="brand-sub">Laporan Evaluasi AI &middot; Analisa performa usaha</div>
            </td>
            <td class="brand-meta">
                <div class="period">Periode {{ $presetLabels[$evaluation->preset] ?? 'Kustom' }}</div>
                <div>{{ $evaluation->start_date->format('d M Y') }} &ndash; {{ $evaluation->end_date->format('d M Y') }}</div>
                <div>Dibuat {{ $generatedAt }}</div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <!-- Ringkasan -->
    @if (! empty($result['ringkasan']))
        <div class="section">
            <div class="section-title">Ringkasan Eksekutif</div>
            <div class="paragraph">{{ $result['ringkasan'] }}</div>
        </div>
    @endif

    <!-- Metrik kunci -->
    @if (! empty($result['metrik_kunci']))
        <div class="section">
            <div class="section-title">Metrik Kunci</div>
            <table class="kpi">
                <thead>
                    <tr>
                        <th>Metrik</th>
                        <th>Nilai</th>
                        <th>Tren</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($result['metrik_kunci'] as $metric)
                        <tr>
                            <td class="metric">{{ $metric['label'] ?? '-' }}</td>
                            <td class="value">{{ $metric['nilai'] ?? '-' }}</td>
                            <td class="trend {{ $trendClass($metric['tren'] ?? 'stabil') }}">{{ ucfirst($metric['tren'] ?? 'stabil') }}</td>
                            <td>{{ $metric['catatan'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Grafik pendapatan -->
    @if (! empty($chart['bars']))
        <div class="section">
            <div class="section-title">Tren Pendapatan</div>
            @php $columnWidth = round(100 / count($chart['bars']), 4); @endphp
            <table class="chart">
                <tr>
                    @foreach ($chart['bars'] as $bar)
                        <td class="bar-cell" style="width: {{ $columnWidth }}%;">
                            @if (count($chart['bars']) <= 10)
                                <div class="bar-value">{{ number_format($bar['value'], 0, ',', '.') }}</div>
                            @endif
                            <div class="bar" style="height: {{ max(4, (int) round($bar['pct'] * 1.05)) }}px;"></div>
                        </td>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($chart['bars'] as $bar)
                        <td class="bar-label">
                            @php
                                $label = $bar['label'];
                                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $label)) {
                                    $label = \Carbon\Carbon::parse($label)->format('d/m');
                                } elseif (preg_match('/^\d{4}-\d{2}$/', $label)) {
                                    $label = \Carbon\Carbon::createFromFormat('Y-m', $label)->translatedFormat('M y');
                                }
                            @endphp
                            {{ $label }}
                        </td>
                    @endforeach
                </tr>
            </table>
            <div class="chart-note">
                Total pendapatan periode: <strong>Rp {{ number_format($chart['total'], 0, ',', '.') }}</strong>
                &middot; {{ count($chart['bars']) }} titik data terakhir
            </div>
        </div>
    @endif

    <!-- Analisis mendalam -->
    @if (! empty($result['analisis']))
        <div class="section">
            <div class="section-title">Analisis Mendalam</div>
            @foreach ($result['analisis'] as $item)
                <div class="analysis-aspect">{{ $item['aspek'] ?? '' }}</div>
                <div class="paragraph">{{ $item['paragraf'] ?? '' }}</div>
            @endforeach
        </div>
    @endif

    <!-- Temuan -->
    @if (! empty($result['temuan']))
        <div class="section">
            <div class="section-title">Temuan</div>
            @foreach ($result['temuan'] as $finding)
                @php $impact = $impactClass(strtolower((string) ($finding['dampak'] ?? 'rendah'))); @endphp
                <div class="finding {{ $impact }}">
                    <table class="finding-head">
                        <tr>
                            <td class="finding-title">{{ $finding['judul'] ?? '-' }}</td>
                            <td class="impact"><span class="pill {{ $impact }}">{{ $impact }}</span></td>
                        </tr>
                    </table>
                    <div class="finding-implication">{{ $finding['detail'] ?? '' }}</div>
                    @if (! empty($finding['implikasi']))
                        <div class="finding-implication"><strong>Implikasi:</strong> {{ $finding['implikasi'] }}</div>
                    @endif
                    @if (! empty($finding['bukti']))
                        <div class="finding-evidence">Bukti: {{ $finding['bukti'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- Rekomendasi -->
    @if (! empty($result['rekomendasi']))
        <div class="section">
            <div class="section-title">Rekomendasi</div>
            <ol class="ordered">
                @foreach ($result['rekomendasi'] as $reco)
                    <li>
                        <div class="title">{{ $reco['aksi'] ?? '-' }}</div>
                        <div class="sub">{{ $reco['alasan'] ?? '' }}</div>
                        @if (! empty($reco['langkah']))
                            <ul class="steps">
                                @foreach ($reco['langkah'] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if (! empty($reco['dampak_diharapkan']))
                            <div class="expected">Target: {{ $reco['dampak_diharapkan'] }}</div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    <!-- Risiko -->
    @if (! empty($result['risiko']))
        <div class="section">
            <div class="section-title">Risiko</div>
            @foreach ($result['risiko'] as $risk)
                <div class="risk">
                    <div class="title">{{ $risk['risiko'] ?? '-' }}</div>
                    <div class="sub">{{ $risk['indikasi'] ?? '' }}</div>
                    @if (! empty($risk['mitigasi']))
                        <div class="expected">Mitigasi: {{ $risk['mitigasi'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- Kesimpulan -->
    @if (! empty($result['kesimpulan']))
        <div class="section">
            <div class="section-title">Kesimpulan</div>
            <div class="conclusion">{{ $result['kesimpulan'] }}</div>
        </div>
    @endif

    <!-- Lampiran: meja -->
    @php $tables = data_get($payload, 'meja.data', []); @endphp
    @if (! empty($tables))
        <div class="section">
            <div class="section-title">Lampiran: Utilisasi Meja</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Meja</th>
                        <th class="text-right">Transaksi</th>
                        <th class="text-right">Jam Pakai</th>
                        <th class="text-right">Utilisasi</th>
                        <th class="text-right">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_slice($tables, 0, 10) as $table)
                        <tr>
                            <td>{{ $table['meja'] ?? '-' }}</td>
                            <td class="text-right">{{ number_format($table['transaksi'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($table['total_jam_pakai'] ?? 0, 1, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($table['estimasi_utilisasi_persen'] ?? 0, 1, ',', '.') }}%</td>
                            <td class="text-right">Rp {{ number_format($table['revenue'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Lampiran: F&B -->
    @php $foods = data_get($payload, 'fnb.data', []); @endphp
    @if (! empty($foods))
        <div class="section">
            <div class="section-title">Lampiran: Penjualan F&amp;B</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Kategori</th>
                        <th class="text-right">Terjual</th>
                        <th class="text-right">Pendapatan</th>
                        <th class="text-right">Kontribusi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_slice($foods, 0, 10) as $food)
                        <tr>
                            <td>{{ $food['item'] ?? '-' }}</td>
                            <td>{{ $food['kategori'] ?? '-' }}</td>
                            <td class="text-right">{{ number_format($food['qty'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($food['revenue'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($food['kontribusi_persen'] ?? 0, 1, ',', '.') }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Meta -->
    <div class="section">
        <div class="section-title">Informasi Laporan</div>
        <table class="meta">
            <tr>
                <td>
                    Data dianalisis: {{ implode(', ', array_map(fn ($key) => $sections[$key] ?? $key, $evaluation->sections ?? [])) }}<br>
                    Diproses oleh: <strong>Sistem AI internal PoolStream</strong>
                </td>
                <td>
                    Dibuat oleh: <strong>{{ $evaluation->user?->name ?? '-' }}</strong><br>
                    Waktu proses: {{ $evaluation->duration_ms ? round($evaluation->duration_ms / 1000, 1).' detik' : '-' }}
                    &middot; ID #{{ $evaluation->id }}
                </td>
            </tr>
        </table>
        <div class="disclaimer">
            Laporan ini dihasilkan otomatis oleh sistem AI internal PoolStream berdasarkan data agregat transaksi pada periode di atas.
            Hasil bersifat saran untuk pengambilan keputusan; verifikasi angka penting dengan data transaksi mentah.
            Dokumen ini bersifat internal dan tidak untuk disebarkan ke pihak luar.
        </div>
    </div>
</body>
</html>
