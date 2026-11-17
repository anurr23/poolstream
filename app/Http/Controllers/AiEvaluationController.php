<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAiEvaluation;
use App\Models\AiEvaluation;
use App\Models\Transaction;
use App\Services\ReportDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AiEvaluationController extends Controller
{
    /** Preset rentang waktu yang dikenali server (validasi otoritatif). */
    private const PRESETS = [
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

    public function index()
    {
        return Inertia::render('Master/AiEvaluation/Index', [
            'presets' => collect(self::PRESETS)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values(),
            'sections' => collect(ReportDataService::SECTIONS)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values(),
            'defaultSections' => ['ringkasan', 'pendapatan', 'meja', 'fnb'],
            'history' => AiEvaluation::query()
                ->latest()
                ->limit(20)
                ->get(['id', 'preset', 'start_date', 'end_date', 'status', 'created_at']),
            'today' => Carbon::today()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'preset' => ['required', 'string', 'in:'.implode(',', array_keys(self::PRESETS))],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['string', 'in:'.implode(',', array_keys(ReportDataService::SECTIONS))],
            'include_sample' => ['boolean'],
        ]);

        [$start, $end, $label] = $this->resolveRange(
            $validated['preset'],
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $evaluation = AiEvaluation::create([
            'user_id' => Auth::id(),
            'preset' => $validated['preset'],
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'bucket' => app(ReportDataService::class)->bucketFor($start, $end),
            'sections' => array_values(array_unique($validated['sections'])),
            'status' => 'running',
            'model' => config('services.ai.model'),
            'payload' => [
                'opsi' => [
                    'sertakan_sampel' => (bool) ($validated['include_sample'] ?? false),
                    'label_periode' => $label,
                ],
            ],
        ]);

        // Analisa dijalankan setelah response dikirim, jadi UI tidak terblokir.
        GenerateAiEvaluation::dispatchAfterResponse($evaluation->id);

        return redirect()->route('ai-evaluations.show', $evaluation);
    }

    public function show(AiEvaluation $evaluation)
    {
        $evaluation->load('user:id,name');

        return Inertia::render('Master/AiEvaluation/Show', [
            'evaluation' => [
                'id' => $evaluation->id,
                'preset' => $evaluation->preset,
                'preset_label' => self::PRESETS[$evaluation->preset] ?? $evaluation->preset,
                'start_date' => $evaluation->start_date?->toDateString(),
                'end_date' => $evaluation->end_date?->toDateString(),
                'bucket' => $evaluation->bucket,
                'sections' => $evaluation->sections,
                'status' => $evaluation->status,
                'result' => $evaluation->result,
                'meta' => [
                    'ringkasan_data' => $evaluation->payload['ringkasan'] ?? null,
                    'periode_data' => $evaluation->payload['periode'] ?? null,
                    'sertakan_sampel' => (bool) data_get($evaluation->payload, 'opsi.sertakan_sampel', false),
                    'prompt_tokens' => $evaluation->prompt_tokens,
                    'completion_tokens' => $evaluation->completion_tokens,
                    'duration_ms' => $evaluation->duration_ms,
                ],
                'error' => $evaluation->error,
                'created_by' => $evaluation->user?->name,
                'created_at' => $evaluation->created_at?->toDateTimeString(),
            ],
            'sections' => collect(ReportDataService::SECTIONS)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values(),
        ]);
    }

    public function pdf(AiEvaluation $evaluation)
    {
        if ($evaluation->status !== 'completed') {
            return redirect()->route('ai-evaluations.show', $evaluation)
                ->with('error', 'PDF hanya bisa dibuat setelah evaluasi selesai diproses.');
        }

        $payload = $evaluation->payload ?? [];

        $data = [
            'evaluation' => $evaluation,
            'result' => $evaluation->result ?? [],
            'payload' => $payload,
            'sections' => ReportDataService::SECTIONS,
            'logo' => public_path('images/logo.jpg'),
            'generatedAt' => Carbon::now()->locale('id')->translatedFormat('d F Y, H:i'),
            'chart' => $this->revenueChart($payload),
        ];

        $filename = 'Evaluasi-AI-PoolStream_'
            .$evaluation->start_date->format('Ymd').'-'.$evaluation->end_date->format('Ymd').'.pdf';

        return Pdf::loadView('reports.ai-evaluation-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption(['isRemoteEnabled' => true, 'defaultFont' => 'sans-serif'])
            ->download($filename);
    }

    /**
     * Data grafik tren pendapatan (maksimal 14 titik terakhir) untuk PDF.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function revenueChart(array $payload): array
    {
        $series = data_get($payload, 'pendapatan.deret', []);

        if (! is_array($series) || $series === []) {
            return ['bars' => [], 'max' => 0, 'total' => 0];
        }

        $series = array_slice($series, -14);
        $values = array_map(fn ($row) => (float) ($row['revenue_total'] ?? 0), $series);
        $max = max($values) ?: 1;

        return [
            'max' => (int) round($max),
            'total' => (int) round(array_sum($values)),
            'bars' => array_map(function ($row, $index) use ($values, $max) {
                return [
                    'label' => (string) ($row['label'] ?? ''),
                    'value' => (int) round($values[$index]),
                    'pct' => round($values[$index] / $max * 100, 1),
                ];
            }, $series, array_keys($values)),
        ];
    }

    public function destroy(AiEvaluation $evaluation)
    {
        $evaluation->delete();

        return redirect()->route('ai-evaluations.index')
            ->with('success', 'Evaluasi berhasil dihapus.');
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveRange(string $preset, ?string $startDate, ?string $endDate): array
    {
        $today = Carbon::today();

        $range = match ($preset) {
            'today' => [$today->copy(), $today->copy()],
            '7d' => [$today->copy()->subDays(6), $today->copy()],
            'month' => [$today->copy()->startOfMonth(), $today->copy()],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            '3m' => [$today->copy()->subMonthsNoOverflow(3)->addDay(), $today->copy()],
            '6m' => [$today->copy()->subMonthsNoOverflow(6)->addDay(), $today->copy()],
            'year' => [$today->copy()->startOfYear(), $today->copy()],
            '12m' => [$today->copy()->subMonthsNoOverflow(12)->addDay(), $today->copy()],
            'all' => $this->allRange($today),
            'custom' => $this->customRange($startDate, $endDate),
            default => [$today->copy()->startOfMonth(), $today->copy()],
        };

        [$start, $end] = $range;

        if ($start->gt($end)) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal mulai tidak boleh melebihi tanggal selesai.',
            ]);
        }

        if ($start->diffInDays($end) > 3650) {
            throw ValidationException::withMessages([
                'start_date' => 'Rentang maksimal 10 tahun.',
            ]);
        }

        return [$start, $end, self::PRESETS[$preset]];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function allRange(Carbon $today): array
    {
        $first = Transaction::min('created_at');

        return $first
            ? [Carbon::parse($first)->startOfDay(), $today->copy()]
            : [$today->copy()->startOfMonth(), $today->copy()];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function customRange(?string $startDate, ?string $endDate): array
    {
        if (! $startDate || ! $endDate) {
            throw ValidationException::withMessages([
                'start_date' => 'Isi tanggal mulai dan tanggal selesai untuk rentang khusus.',
            ]);
        }

        return [Carbon::createFromFormat('Y-m-d', $startDate), Carbon::createFromFormat('Y-m-d', $endDate)];
    }
}
