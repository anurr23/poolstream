<?php

namespace Tests\Feature;

use App\Models\AiEvaluation;
use App\Models\Package;
use App\Models\Table;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function completedTransaction(): Transaction
    {
        $table = Table::factory()->create(['name' => 'Meja 1']);

        return Transaction::create([
            'type' => 'billiard',
            'table_id' => $table->id,
            'package_id' => Package::factory()->create()->id,
            'customer_name' => 'Budi Santoso',
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'expected_end_time' => now(),
            'billiard_cost' => 50000,
            'fnb_cost' => 15000,
            'total_cost' => 65000,
            'status' => 'completed',
        ]);
    }

    private function fakeAiResponse(string $content): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => $content]],
                ],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 60],
            ]),
        ]);
    }

    public function test_only_admin_can_open_the_ai_evaluation_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('ai-evaluations.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->get(route('ai-evaluations.index'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('ai-evaluations.index'))
            ->assertOk();
    }

    public function test_admin_gets_an_evaluation_saved_after_ai_response(): void
    {
        $this->completedTransaction();

        $this->fakeAiResponse(json_encode([
            'ringkasan' => 'Pendapatan stabil dengan kontribusi F&B kecil.',
            'metrik_kunci' => [
                ['label' => 'Revenue', 'nilai' => 'Rp 65.000', 'tren' => 'naik', 'catatan' => 'satu transaksi'],
            ],
            'temuan' => [
                ['judul' => 'F&B rendah', 'detail' => 'Hanya 23% dari revenue.', 'dampak' => 'sedang', 'bukti' => '15.000 / 65.000'],
            ],
            'rekomendasi' => [
                ['aksi' => 'Bundling minuman', 'alasan' => 'Naikkan attach rate F&B.', 'prioritas' => 1],
            ],
            'risiko' => [
                ['risiko' => 'Data terlalu sedikit', 'indikasi' => 'Hanya satu transaksi'],
            ],
        ]));

        $response = $this->actingAs($this->admin())->post(route('ai-evaluations.store'), [
            'preset' => 'month',
            'sections' => ['ringkasan', 'pendapatan', 'meja', 'fnb'],
            'include_sample' => false,
        ]);

        $evaluation = AiEvaluation::firstOrFail();

        $response->assertRedirect(route('ai-evaluations.show', $evaluation));

        $this->assertSame('completed', $evaluation->status);
        $this->assertSame('Pendapatan stabil dengan kontribusi F&B kecil.', $evaluation->result['ringkasan']);
        $this->assertSame(1, $evaluation->payload['ringkasan']['total_transaksi']);
        $this->assertSame(65000.0, (float) $evaluation->payload['ringkasan']['revenue_total']);
        $this->assertSame(120, $evaluation->prompt_tokens);
        $this->assertSame(60, $evaluation->completion_tokens);
        $this->assertNotNull($evaluation->user_id);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/chat/completions')
                && ($request['model'] ?? null) === config('services.ai.model')
                && ($request['max_tokens'] ?? null) === (int) config('services.ai.max_tokens')
                && str_contains($request['messages'][1]['content'], '"ringkasan"');
        });
    }

    public function test_evaluation_fails_when_ai_output_hits_the_token_limit(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"ringkasan": "jawaban terpotong'], 'finish_reason' => 'length'],
                ],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 16384],
            ]),
        ]);

        $this->actingAs($this->admin())->post(route('ai-evaluations.store'), [
            'preset' => 'today',
            'sections' => ['ringkasan'],
        ]);

        $evaluation = AiEvaluation::firstOrFail();

        $this->assertSame('failed', $evaluation->status);
        $this->assertStringContainsString('terpotong', (string) $evaluation->error);
    }

    public function test_evaluation_is_marked_failed_when_ai_returns_an_error(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response(['error' => ['message' => 'model tidak ditemukan']], 500),
        ]);

        $this->actingAs($this->admin())->post(route('ai-evaluations.store'), [
            'preset' => 'today',
            'sections' => ['ringkasan'],
        ]);

        $evaluation = AiEvaluation::firstOrFail();

        $this->assertSame('failed', $evaluation->status);
        $this->assertStringContainsString('HTTP 500', $evaluation->error);
    }

    public function test_custom_preset_requires_a_date_range(): void
    {
        $this->actingAs($this->admin())->post(route('ai-evaluations.store'), [
            'preset' => 'custom',
            'sections' => ['ringkasan'],
        ])->assertSessionHasErrors('start_date');

        $this->assertSame(0, AiEvaluation::count());
    }

    public function test_admin_can_delete_an_evaluation(): void
    {
        $evaluation = AiEvaluation::create([
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'bucket' => 'day',
            'sections' => ['ringkasan'],
            'status' => 'completed',
            'result' => ['ringkasan' => 'ok'],
        ]);

        $this->actingAs($this->admin())
            ->delete(route('ai-evaluations.destroy', $evaluation))
            ->assertRedirect(route('ai-evaluations.index'));

        $this->assertSame(0, AiEvaluation::count());
    }

    private function completedEvaluation(): AiEvaluation
    {
        return AiEvaluation::create([
            'start_date' => now()->subDays(6)->toDateString(),
            'end_date' => now()->toDateString(),
            'bucket' => 'day',
            'sections' => ['ringkasan', 'pendapatan', 'meja', 'fnb'],
            'status' => 'completed',
            'model' => 'ag/gemini-3.8-flash-high',
            'result' => [
                'ringkasan' => 'Pendapatan stabil dengan kontribusi F&B yang masih rendah.',
                'metrik_kunci' => [
                    ['label' => 'Revenue', 'nilai' => 'Rp 155.000', 'tren' => 'naik', 'catatan' => 'periode singkat'],
                ],
                'temuan' => [
                    ['judul' => 'F&B nihil', 'detail' => 'Tidak ada penjualan F&B.', 'dampak' => 'tinggi', 'bukti' => '0 transaksi'],
                ],
                'rekomendasi' => [
                    ['aksi' => 'Bundling minuman', 'alasan' => 'Menaikkan attach rate.', 'prioritas' => 1],
                ],
                'risiko' => [
                    ['risiko' => 'Data sedikit', 'indikasi' => 'Hanya 2 transaksi'],
                ],
            ],
            'payload' => [
                'periode' => ['mulai' => now()->subDays(6)->toDateString(), 'selesai' => now()->toDateString()],
                'ringkasan' => [
                    'total_transaksi' => 2,
                    'revenue_biliar' => 155000,
                    'revenue_fnb' => 0,
                    'revenue_total' => 155000,
                    'rata_rata_nilai_transaksi' => 77500,
                    'rata_rata_durasi_menit' => 120,
                ],
                'pendapatan' => [
                    'bucket' => 'day',
                    'deret' => [
                        ['label' => now()->subDay()->toDateString(), 'transaksi' => 1, 'revenue_total' => 75000],
                        ['label' => now()->toDateString(), 'transaksi' => 1, 'revenue_total' => 80000],
                    ],
                ],
                'meja' => [
                    'data' => [
                        ['meja' => 'Meja 1', 'transaksi' => 2, 'total_jam_pakai' => 4, 'estimasi_utilisasi_persen' => 4.8, 'revenue' => 155000],
                    ],
                ],
                'fnb' => [
                    'data' => [
                        ['item' => 'Teh Botol', 'kategori' => 'Minuman', 'qty' => 3, 'revenue' => 15000, 'kontribusi_persen' => 100],
                    ],
                ],
            ],
        ]);
    }

    public function test_admin_can_open_the_evaluation_result_page(): void
    {
        $evaluation = $this->completedEvaluation();

        $this->actingAs($this->admin())
            ->get(route('ai-evaluations.show', $evaluation))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Master/AiEvaluation/Show')
                ->where('evaluation.id', $evaluation->id)
                ->where('evaluation.status', 'completed'));
    }

    public function test_admin_can_download_the_pdf_report(): void
    {
        $evaluation = $this->completedEvaluation();

        $response = $this->actingAs($this->admin())
            ->get(route('ai-evaluations.pdf', $evaluation));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $content = (string) $response->getContent();

        $this->assertStringStartsWith('%PDF', $content);
        $this->assertGreaterThan(5000, strlen($content));
    }

    public function test_non_admin_cannot_download_the_pdf_report(): void
    {
        $evaluation = $this->completedEvaluation();

        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->get(route('ai-evaluations.pdf', $evaluation))
            ->assertForbidden();
    }

    public function test_pdf_is_not_generated_while_evaluation_is_still_running(): void
    {
        $evaluation = AiEvaluation::create([
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'bucket' => 'day',
            'sections' => ['ringkasan'],
            'status' => 'running',
        ]);

        $this->actingAs($this->admin())
            ->get(route('ai-evaluations.pdf', $evaluation))
            ->assertRedirect(route('ai-evaluations.show', $evaluation));
    }
}
