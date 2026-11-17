<?php

namespace App\Services;

use App\Models\FnbItem;
use App\Models\Package;
use App\Models\Table;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun "fact pack" agregat untuk fitur Evaluasi AI.
 *
 * Semua angka dihitung di sisi server (bukan oleh AI) supaya akurat.
 * AI hanya menerima data ringkas ini untuk ditafsirkan.
 */
class ReportDataService
{
    /** Seksi yang bisa dipilih admin. */
    public const SECTIONS = [
        'ringkasan' => 'Ringkasan KPI',
        'pendapatan' => 'Tren Pendapatan',
        'meja' => 'Utilisasi Meja',
        'fnb' => 'Penjualan F&B',
        'paket' => 'Performa Paket',
        'jam_sibuk' => 'Jam Sibuk',
        'pembatalan' => 'Pembatalan & Booking Hangus',
        'perbandingan' => 'Perbandingan Periode',
    ];

    /** Asumsi jam operasional per hari untuk estimasi utilisasi. */
    private const OPERATING_HOURS_PER_DAY = 12;

    public function bucketFor(Carbon $start, Carbon $end): string
    {
        $days = $start->diffInDays($end) + 1;

        if ($days <= 31) {
            return 'day';
        }

        if ($days <= 180) {
            return 'week';
        }

        return 'month';
    }

    /**
     * @param  array<int, string>  $sections
     * @return array<string, mixed>
     */
    public function build(Carbon $start, Carbon $end, array $sections, string $bucket, bool $includeSample = false): array
    {
        $sections = array_values(array_intersect($sections, array_keys(self::SECTIONS)));
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();
        $range = [$start->toDateString(), $end->toDateString()];

        $payload = [
            'periode' => [
                'mulai' => $start->toDateString(),
                'selesai' => $end->toDateString(),
                'jumlah_hari' => $start->diffInDays($end) + 1,
                'bucket' => $bucket,
                'dasar_tanggal' => 'created_at transaksi (hanya transaksi berstatus completed)',
                'mata_uang' => 'IDR',
            ],
        ];

        $rows = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween(DB::raw('date(created_at)'), $range)
            ->orderBy('created_at')
            ->limit(200000)
            ->get([
                'created_at',
                'start_time',
                'end_time',
                'type',
                'table_id',
                'package_id',
                'billiard_cost',
                'fnb_cost',
                'total_cost',
            ]);

        if (in_array('ringkasan', $sections, true)) {
            $payload['ringkasan'] = $this->summary($rows, $start, $end);
        }

        if (in_array('pendapatan', $sections, true)) {
            $payload['pendapatan'] = $this->revenueSeries($rows, $bucket);
        }

        if (in_array('meja', $sections, true)) {
            $payload['meja'] = $this->tableUtilization($rows, $start, $end);
        }

        if (in_array('fnb', $sections, true)) {
            $payload['fnb'] = $this->fnbSales($range);
        }

        if (in_array('paket', $sections, true)) {
            $payload['paket'] = $this->packageStats($rows);
        }

        if (in_array('jam_sibuk', $sections, true)) {
            $payload['jam_sibuk'] = $this->busyHours($rows);
        }

        if (in_array('pembatalan', $sections, true)) {
            $payload['pembatalan'] = $this->cancellations($range);
        }

        if (in_array('perbandingan', $sections, true)) {
            $payload['perbandingan'] = $this->comparison($start, $end);
        }

        if ($includeSample) {
            $payload['sampel_transaksi'] = $this->sample($range);
        }

        return $payload;
    }

    private function durationMinutes(object $row): float
    {
        if (! $row->start_time) {
            return 0;
        }

        $end = $row->end_time ?: $row->created_at;

        if (! $end) {
            return 0;
        }

        return max(0, Carbon::parse($row->start_time)->diffInMinutes(Carbon::parse($end)));
    }

    private function summary($rows, Carbon $start, Carbon $end): array
    {
        $count = $rows->count();
        $billiard = (float) $rows->sum('billiard_cost');
        $fnb = (float) $rows->sum('fnb_cost');
        $total = (float) $rows->sum('total_cost');
        $durations = $rows->filter(fn ($row) => $row->end_time)->map(fn ($row) => $this->durationMinutes($row));

        return [
            'total_transaksi' => $count,
            'transaksi_biliar' => $rows->where('type', 'billiard')->count(),
            'transaksi_fnb_saja' => $rows->where('type', 'fnb_only')->count(),
            'revenue_biliar' => round($billiard, 2),
            'revenue_fnb' => round($fnb, 2),
            'revenue_total' => round($total, 2),
            'rata_rata_nilai_transaksi' => $count ? round($total / $count, 2) : 0,
            'rata_rata_durasi_menit' => $durations->isNotEmpty() ? round($durations->avg(), 1) : 0,
            'meja_terpakai' => $rows->pluck('table_id')->filter()->unique()->count(),
            'rata_rata_transaksi_per_hari' => $start->diffInDays($end) + 1 > 0
                ? round($count / ($start->diffInDays($end) + 1), 2)
                : 0,
            'rata_rata_revenue_per_hari' => $start->diffInDays($end) + 1 > 0
                ? round($total / ($start->diffInDays($end) + 1), 2)
                : 0,
        ];
    }

    private function revenueSeries($rows, string $bucket): array
    {
        $grouped = $rows->groupBy(function ($row) use ($bucket) {
            $date = Carbon::parse($row->created_at);

            return match ($bucket) {
                'week' => $date->copy()->startOfWeek()->toDateString(),
                'month' => $date->format('Y-m'),
                default => $date->toDateString(),
            };
        })->sortKeys();

        return [
            'bucket' => $bucket,
            'deret' => $grouped->map(function ($group, $label) {
                return [
                    'label' => $label,
                    'transaksi' => $group->count(),
                    'revenue_biliar' => round((float) $group->sum('billiard_cost'), 2),
                    'revenue_fnb' => round((float) $group->sum('fnb_cost'), 2),
                    'revenue_total' => round((float) $group->sum('total_cost'), 2),
                ];
            })->values()->all(),
        ];
    }

    private function tableUtilization($rows, Carbon $start, Carbon $end): array
    {
        $days = $start->diffInDays($end) + 1;
        $capacityHours = max(1, $days * self::OPERATING_HOURS_PER_DAY);

        return [
            'asumsi_jam_operasional_per_hari' => self::OPERATING_HOURS_PER_DAY,
            'data' => $rows->groupBy('table_id')->map(function ($group, $tableId) use ($capacityHours) {
                $table = Table::find($tableId);
                $minutes = $group->sum(fn ($row) => $this->durationMinutes($row));
                $hours = round($minutes / 60, 1);

                return [
                    'meja' => $table?->name ?? 'Meja terhapus',
                    'nomor_meja' => $table?->table_number,
                    'transaksi' => $group->count(),
                    'total_jam_pakai' => $hours,
                    'estimasi_utilisasi_persen' => min(100, round($hours / $capacityHours * 100, 1)),
                    'revenue' => round((float) $group->sum('total_cost'), 2),
                    'rata_rata_durasi_menit' => round(
                        $group->filter(fn ($row) => $row->end_time)->map(fn ($row) => $this->durationMinutes($row))->avg() ?? 0,
                        1
                    ),
                ];
            })->sortByDesc('revenue')->values()->all(),
        ];
    }

    private function fnbSales(array $range): array
    {
        $items = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'completed')
            ->whereBetween(DB::raw('date(transactions.created_at)'), $range)
            ->select(
                'transaction_items.fnb_item_id',
                DB::raw('SUM(transaction_items.quantity) as qty'),
                DB::raw('SUM(transaction_items.subtotal) as revenue')
            )
            ->groupBy('transaction_items.fnb_item_id')
            ->get();

        $names = FnbItem::whereIn('id', $items->pluck('fnb_item_id'))->get()->keyBy('id');
        $totalRevenue = (float) $items->sum('revenue');

        return [
            'total_item_terjual' => (int) $items->sum('qty'),
            'total_revenue' => round($totalRevenue, 2),
            'data' => $items->map(function ($item) use ($names, $totalRevenue) {
                $fnb = $names->get($item->fnb_item_id);

                return [
                    'item' => $fnb?->name ?? 'Item terhapus',
                    'kategori' => $fnb?->category,
                    'qty' => (int) $item->qty,
                    'revenue' => round((float) $item->revenue, 2),
                    'kontribusi_persen' => $totalRevenue > 0
                        ? round((float) $item->revenue / $totalRevenue * 100, 1)
                        : 0,
                ];
            })->sortByDesc('revenue')->values()->all(),
        ];
    }

    private function packageStats($rows): array
    {
        $packages = Package::all()->keyBy('id');

        return $rows->groupBy('package_id')->map(function ($group, $packageId) use ($packages) {
            $minutes = $group->sum(fn ($row) => $this->durationMinutes($row));

            return [
                'paket' => $packages->get($packageId)?->name ?? 'Tanpa paket / F&B saja',
                'transaksi' => $group->count(),
                'total_jam' => round($minutes / 60, 1),
                'revenue_biliar' => round((float) $group->sum('billiard_cost'), 2),
                'rata_rata_durasi_menit' => round(
                    $group->filter(fn ($row) => $row->end_time)->map(fn ($row) => $this->durationMinutes($row))->avg() ?? 0,
                    1
                ),
            ];
        })->sortByDesc('revenue_biliar')->values()->all();
    }

    private function busyHours($rows): array
    {
        return $rows->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('H'))
            ->sortKeys()
            ->map(function ($group, $hour) {
                return [
                    'jam' => $hour.':00',
                    'transaksi' => $group->count(),
                    'revenue' => round((float) $group->sum('total_cost'), 2),
                    'rata_rata_durasi_menit' => round(
                        $group->filter(fn ($row) => $row->end_time)->map(fn ($row) => $this->durationMinutes($row))->avg() ?? 0,
                        1
                    ),
                ];
            })->values()->all();
    }

    private function cancellations(array $range): array
    {
        $cancelled = Transaction::where('status', 'cancelled')
            ->whereBetween(DB::raw('date(created_at)'), $range)
            ->get(['total_cost']);

        $noShow = Transaction::where('status', 'booked')
            ->whereNotNull('expected_end_time')
            ->where('expected_end_time', '<', Carbon::now())
            ->whereBetween(DB::raw('date(start_time)'), $range)
            ->count();

        return [
            'transaksi_dibatalkan' => $cancelled->count(),
            'nilai_dibatalkan' => round((float) $cancelled->sum('total_cost'), 2),
            'booking_hangus' => $noShow,
        ];
    }

    private function comparison(Carbon $start, Carbon $end): array
    {
        $days = $start->diffInDays($end) + 1;
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subDay();

        $previous = Transaction::where('status', 'completed')
            ->whereBetween(DB::raw('date(created_at)'), [
                $previousStart->toDateString(),
                $previousEnd->toDateString(),
            ])
            ->get(['total_cost', 'billiard_cost', 'fnb_cost']);

        $count = $previous->count();
        $total = (float) $previous->sum('total_cost');

        return [
            'periode_pembanding' => [
                'mulai' => $previousStart->toDateString(),
                'selesai' => $previousEnd->toDateString(),
            ],
            'transaksi' => $count,
            'revenue_biliar' => round((float) $previous->sum('billiard_cost'), 2),
            'revenue_fnb' => round((float) $previous->sum('fnb_cost'), 2),
            'revenue_total' => round($total, 2),
            'rata_rata_nilai_transaksi' => $count ? round($total / $count, 2) : 0,
        ];
    }

    private function sample(array $range): array
    {
        return Transaction::where('status', 'completed')
            ->whereBetween(DB::raw('date(created_at)'), $range)
            ->with(['table:id,name', 'package:id,name'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (Transaction $trx) {
                return [
                    'tanggal' => $trx->created_at?->toDateTimeString(),
                    'meja' => $trx->table?->name,
                    'paket' => $trx->package?->name,
                    'durasi_menit' => $this->durationMinutes($trx),
                    'biliar' => (float) $trx->billiard_cost,
                    'fnb' => (float) $trx->fnb_cost,
                    'total' => (float) $trx->total_cost,
                    'pelanggan' => $this->maskName($trx->customer_name),
                ];
            })
            ->all();
    }

    private function maskName(?string $name): string
    {
        if (! $name) {
            return '-';
        }

        $parts = preg_split('/\s+/', trim($name));

        if (count($parts) < 2) {
            return $parts[0];
        }

        return $parts[0].' '.strtoupper(substr(end($parts), 0, 1)).'.';
    }
}
