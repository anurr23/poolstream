<?php

namespace App\Services;

use App\Models\AiEvaluation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Membangun prompt, memanggil endpoint AI lokal (9router / OpenAI-compatible),
 * lalu menyimpan hasil evaluasi ke model AiEvaluation.
 */
class AiEvaluationService
{
    public function __construct(private readonly ReportDataService $reportData)
    {
    }

    public function generate(AiEvaluation $evaluation): void
    {
        $startedAt = microtime(true);

        $start = Carbon::parse($evaluation->start_date);
        $end = Carbon::parse($evaluation->end_date);
        $bucket = $this->reportData->bucketFor($start, $end);

        $payload = $this->reportData->build(
            $start,
            $end,
            $evaluation->sections ?? [],
            $bucket,
            (bool) data_get($evaluation->payload, 'opsi.sertakan_sampel', false)
        );

        $evaluation->forceFill([
            'bucket' => $bucket,
            'model' => config('services.ai.model'),
            'payload' => $payload,
        ])->save();

        [$system, $user] = $this->prompts($payload);

        // Laravel's HTTP client defaults to 30s; 0 means "no timeout" in Guzzle.
        $response = Http::withToken((string) config('services.ai.api_key'))
            ->acceptJson()
            ->timeout((int) config('services.ai.timeout'))
            ->post(rtrim((string) config('services.ai.base_url'), '/').'/chat/completions', [
                'model' => config('services.ai.model'),
                'temperature' => 0.3,
                // 9router membalas dalam format SSE bila flag ini tidak dikirim eksplisit.
                'stream' => false,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'AI menolak permintaan (HTTP '.$response->status().'): '
                .mb_substr($response->body(), 0, 500)
            );
        }

        $content = $this->extractContent($response->json(), $response->body());

        if (trim($content) === '') {
            throw new RuntimeException('AI mengembalikan respons kosong.');
        }

        $result = $this->decode($content);
        $usage = $this->extractUsage($response->json(), $response->body());

        $evaluation->forceFill([
            'status' => 'completed',
            'result' => $result ?? ['ringkasan' => mb_substr($content, 0, 4000)],
            'error' => $result === null
                ? 'AI tidak mengembalikan JSON valid; hasil ditampilkan sebagai teks mentah.'
                : null,
            'prompt_tokens' => $usage['prompt_tokens'],
            'completion_tokens' => $usage['completion_tokens'],
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ])->save();
    }

    /**
     * Ambil isi jawaban, baik dari JSON biasa maupun dari stream SSE (9router).
     */
    private function extractContent(?array $json, string $body): string
    {
        $content = (string) data_get($json, 'choices.0.message.content', '');

        if ($content !== '') {
            return $content;
        }

        if (! str_contains($body, 'data:')) {
            return $content;
        }

        $parts = [];

        foreach (preg_split('/\r?\n/', $body) as $line) {
            $line = trim($line);

            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $chunk = trim(substr($line, 5));

            if ($chunk === '' || $chunk === '[DONE]') {
                continue;
            }

            $decoded = json_decode($chunk, true);

            if (is_array($decoded)) {
                $delta = data_get($decoded, 'choices.0.delta.content')
                    ?? data_get($decoded, 'choices.0.message.content');
                $parts[] = (string) $delta;
            }
        }

        return trim(implode('', $parts));
    }

    /**
     * @return array{prompt_tokens: int|null, completion_tokens: int|null}
     */
    private function extractUsage(?array $json, string $body): array
    {
        $prompt = data_get($json, 'usage.prompt_tokens');
        $completion = data_get($json, 'usage.completion_tokens');

        if ($prompt === null && str_contains($body, 'data:')) {
            foreach (preg_split('/\r?\n/', $body) as $line) {
                $line = trim($line);

                if (! str_starts_with($line, 'data:')) {
                    continue;
                }

                $decoded = json_decode(trim(substr($line, 5)), true);

                if (is_array($decoded) && is_array($decoded['usage'] ?? null)) {
                    $prompt = $decoded['usage']['prompt_tokens'] ?? $prompt;
                    $completion = $decoded['usage']['completion_tokens'] ?? $completion;
                }
            }
        }

        return [
            'prompt_tokens' => $prompt !== null ? (int) $prompt : null,
            'completion_tokens' => $completion !== null ? (int) $completion : null,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function prompts(array $payload): array
    {
        $system = <<<'PROMPT'
Anda adalah analis bisnis untuk pool hall / biliar bernama PoolStream.

Tugas: mengevaluasi performa usaha berdasarkan DATA AGREGAT yang diberikan, dalam Bahasa Indonesia.

Aturan wajib:
1. Gunakan HANYA angka yang ada pada data. Jangan mengarang, jangan menebak, jangan menghitung ulang total yang sudah tersedia.
2. Jika data tidak cukup untuk sebuah kesimpulan, tulis "data belum cukup".
3. Nominal dalam Rupiah (IDR), tulis tanpa desimal.
4. Fokus pada temuan yang bisa ditindaklanjuti pemilik usaha (operasional, harga, jam ramai, menu, utilisasi meja).
5. Jawab HANYA dengan satu objek JSON valid, tanpa markdown, tanpa penjelasan di luar JSON.

Skema JSON yang wajib diikuti:
{
  "ringkasan": "2-3 kalimat tentang kondisi bisnis pada periode ini",
  "metrik_kunci": [{"label": "string", "nilai": "string", "tren": "naik|turun|stabil", "catatan": "string"}],
  "temuan": [{"judul": "string", "detail": "string", "dampak": "tinggi|sedang|rendah", "bukti": "angka pendukung dari data"}],
  "rekomendasi": [{"aksi": "string", "alasan": "string", "prioritas": 1}],
  "risiko": [{"risiko": "string", "indikasi": "string"}]
}
PROMPT;

        $user = "Berikut data agregat periode usaha yang harus dievaluasi (JSON):\n\n"
            .json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ."\n\nBuat evaluasi sesuai skema JSON yang diminta.";

        return [$system, $user];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(string $content): ?array
    {
        $text = trim($content);

        // Buang pembungkus markdown ```json ... ```
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;

        $first = strpos($text, '{');
        $last = strrpos($text, '}');

        if ($first !== false && $last !== false && $last > $first) {
            $text = substr($text, $first, $last - $first + 1);
        }

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return null;
        }

        return $decoded;
    }
}
