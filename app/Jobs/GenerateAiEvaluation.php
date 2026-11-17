<?php

namespace App\Jobs;

use App\Models\AiEvaluation;
use App\Services\AiEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Menjalankan analisa AI di luar siklus request (setelah response dikirim),
 * sehingga UI tidak memblokir admin selama proses berjalan.
 */
class GenerateAiEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $evaluationId)
    {
    }

    public function handle(AiEvaluationService $service): void
    {
        $evaluation = AiEvaluation::find($this->evaluationId);

        if (! $evaluation || $evaluation->status !== 'running') {
            return;
        }

        // Panggilan AI ke 9router tidak dibatasi timeout.
        @set_time_limit(0);

        try {
            $service->generate($evaluation);
        } catch (\Throwable $e) {
            $evaluation->forceFill([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 900),
            ])->save();
        }
    }
}
