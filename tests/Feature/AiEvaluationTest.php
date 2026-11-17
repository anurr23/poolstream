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
                && str_contains($request['messages'][1]['content'], '"ringkasan"');
        });
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
}
