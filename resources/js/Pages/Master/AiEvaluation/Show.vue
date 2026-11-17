<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    evaluation: { type: Object, required: true },
    sections: { type: Array, default: () => [] },
});

const result = computed(() => props.evaluation.result || {});
const meta = computed(() => props.evaluation.meta || {});
const isDone = computed(() => props.evaluation.status === 'completed');

const retryForm = useForm({
    preset: props.evaluation.preset || 'month',
    start_date: props.evaluation.start_date,
    end_date: props.evaluation.end_date,
    sections: props.evaluation.sections || [],
    include_sample: !!meta.value.sertakan_sampel,
});

const rerun = () => retryForm.post(route('ai-evaluations.store'), { preserveScroll: true });

const remove = () => {
    if (!confirm('Hapus evaluasi ini?')) return;
    router.delete(route('ai-evaluations.destroy', props.evaluation.id));
};

const sectionLabel = (key) => props.sections.find((item) => item.key === key)?.label || key;

const impactMeta = (impact) => {
    const map = {
        tinggi: { class: 'is-high', icon: 'bi-exclamation-octagon-fill' },
        sedang: { class: 'is-medium', icon: 'bi-exclamation-diamond-fill' },
        rendah: { class: 'is-low', icon: 'bi-info-circle-fill' },
    };
    return map[impact] || map.rendah;
};

const trendIcon = (trend) => {
    if (trend === 'naik') return 'bi-arrow-up-right';
    if (trend === 'turun') return 'bi-arrow-down-right';
    return 'bi-arrow-right';
};

const trendClass = (trend) => {
    if (trend === 'naik') return 'is-up';
    if (trend === 'turun') return 'is-down';
    return 'is-flat';
};

const formatNumber = (value) => {
    if (value === null || value === undefined || value === '') return '-';
    return new Intl.NumberFormat('id-ID').format(value);
};

const formatDateLong = (value) => {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const durationLabel = computed(() => {
    const ms = meta.value.duration_ms;
    if (!ms && ms !== 0) return '-';
    return ms >= 1000 ? `${(ms / 1000).toFixed(1)} detik` : `${ms} ms`;
});

const ringkasanData = computed(() => meta.value.ringkasan_data || null);

// Polling ringan selama AI memproses, tanpa memblokir halaman.
let pollTimer = null;

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

const startPolling = () => {
    stopPolling();

    if (props.evaluation.status !== 'running') return;

    pollTimer = setInterval(() => {
        router.reload({ only: ['evaluation'], preserveScroll: true, preserveState: true });
    }, 3000);
};

onMounted(startPolling);
onUnmounted(stopPolling);

watch(
    () => props.evaluation.status,
    (status) => {
        if (status !== 'running') stopPolling();
    }
);
</script>

<template>
    <Head title="Hasil Evaluasi AI" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="bb-header-title">
                <i class="bi bi-stars me-2" style="color: #a855f7;"></i>Hasil Evaluasi AI
            </h1>
        </template>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2">
                <span class="bb-ai-chip"><i class="bi bi-calendar-range"></i> {{ evaluation.preset_label }}</span>
                <span class="bb-ai-chip">{{ formatDateLong(evaluation.start_date) }} — {{ formatDateLong(evaluation.end_date) }}</span>
                <span class="bb-ai-chip"><i class="bi bi-cpu"></i> {{ evaluation.model || 'model lokal' }}</span>
                <span class="bb-ai-chip"><i class="bi bi-collection"></i> {{ evaluation.bucket }}</span>
            </div>
            <div class="d-flex gap-2">
                <button class="bb-btn bb-btn--ghost bb-btn--sm" :disabled="retryForm.processing" @click="rerun">
                    <i class="bi bi-arrow-repeat"></i> {{ retryForm.processing ? 'Memproses…' : 'Jalankan ulang' }}
                </button>
                <button class="bb-btn bb-btn--ghost bb-btn--sm" @click="remove">
                    <i class="bi bi-trash"></i> Hapus
                </button>
                <Link :href="route('ai-evaluations.index')" class="bb-btn bb-btn--primary bb-btn--sm">
                    <i class="bi bi-plus-lg"></i> Evaluasi Baru
                </Link>
            </div>
        </div>

        <div v-if="evaluation.status === 'running'" class="bb-ai-running mb-3">
            <span class="bb-ai-running__spinner"></span>
            <div>
                <div class="fw-bold" style="font-size: 0.9rem;">AI sedang menganalisa…</div>
                <div class="small text-secondary">
                    Data periode sudah dikirim ke {{ evaluation.model || 'model lokal' }}. Halaman ini akan memperbarui sendiri saat selesai — kamu bisa lanjut membuka menu lain.
                </div>
            </div>
        </div>

        <div v-if="evaluation.status === 'failed'" class="bb-card mb-3">
            <div class="bb-card-body">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.6rem; color: #f59e0b;"></i>
                    <div>
                        <div class="fw-bold mb-1">Evaluasi gagal diproses</div>
                        <div class="small text-secondary">{{ evaluation.error || 'Terjadi kesalahan yang tidak diketahui.' }}</div>
                        <div class="small text-secondary mt-2">
                            Periksa apakah 9router aktif di <code>AI_BASE_URL</code> dan model <code>{{ evaluation.model }}</code> tersedia.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template v-if="isDone">
            <!-- Ringkasan -->
            <div class="bb-card mb-3">
                <div class="bb-card-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clipboard-data me-2"></i>Ringkasan</h6>
                </div>
                <div class="bb-card-body">
                    <p class="mb-0" style="font-size: 0.95rem; line-height: 1.65;">{{ result.ringkasan || '-' }}</p>
                </div>
            </div>

            <!-- Metrik kunci -->
            <div v-if="result.metrik_kunci?.length" class="row g-3 mb-3">
                <div v-for="(metric, index) in result.metrik_kunci" :key="index" class="col-12 col-sm-6 col-xl-3">
                    <div class="bb-ai-metric">
                        <div class="bb-ai-metric__label">{{ metric.label }}</div>
                        <div class="bb-ai-metric__value">{{ metric.nilai }}</div>
                        <div class="bb-ai-metric__trend" :class="trendClass(metric.tren)">
                            <i class="bi" :class="trendIcon(metric.tren)"></i> {{ metric.tren }}
                        </div>
                        <div v-if="metric.catatan" class="bb-ai-metric__note">{{ metric.catatan }}</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <!-- Temuan -->
                <div class="col-12 col-xl-7">
                    <div class="bb-card h-100">
                        <div class="bb-card-header d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0"><i class="bi bi-search me-2"></i>Temuan</h6>
                            <span class="small text-secondary">{{ result.temuan?.length || 0 }}</span>
                        </div>
                        <div class="bb-card-body">
                            <div v-if="!result.temuan?.length" class="small text-secondary">AI tidak menemukan temuan signifikan.</div>
                            <div v-else class="bb-ai-findings">
                                <div v-for="(finding, index) in result.temuan" :key="index" class="bb-ai-finding">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="fw-bold" style="font-size: 0.88rem;">{{ finding.judul }}</div>
                                        <span class="bb-ai-impact" :class="impactMeta(finding.dampak).class">
                                            <i class="bi" :class="impactMeta(finding.dampak).icon"></i> {{ finding.dampak }}
                                        </span>
                                    </div>
                                    <div class="small mt-1" style="line-height: 1.6;">{{ finding.detail }}</div>
                                    <div v-if="finding.bukti" class="bb-ai-evidence">
                                        <i class="bi bi-bar-chart-line me-1"></i>{{ finding.bukti }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rekomendasi & risiko -->
                <div class="col-12 col-xl-5">
                    <div class="bb-card mb-3">
                        <div class="bb-card-header">
                            <h6 class="fw-bold mb-0"><i class="bi bi-lightbulb me-2"></i>Rekomendasi</h6>
                        </div>
                        <div class="bb-card-body">
                            <div v-if="!result.rekomendasi?.length" class="small text-secondary">Belum ada rekomendasi.</div>
                            <ol v-else class="bb-ai-recos">
                                <li v-for="(reco, index) in result.rekomendasi" :key="index">
                                    <div class="fw-semibold" style="font-size: 0.86rem;">{{ reco.aksi }}</div>
                                    <div class="small text-secondary">{{ reco.alasan }}</div>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="bb-card">
                        <div class="bb-card-header">
                            <h6 class="fw-bold mb-0"><i class="bi bi-shield-exclamation me-2"></i>Risiko</h6>
                        </div>
                        <div class="bb-card-body">
                            <div v-if="!result.risiko?.length" class="small text-secondary">Tidak ada risiko yang ditonjolkan.</div>
                            <div v-else class="d-flex flex-column gap-2">
                                <div v-for="(risk, index) in result.risiko" :key="index" class="bb-ai-risk">
                                    <div class="fw-semibold" style="font-size: 0.84rem;">{{ risk.risiko }}</div>
                                    <div class="small text-secondary">{{ risk.indikasi }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data yang dipakai -->
            <div class="bb-card">
                <div class="bb-card-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-database-check me-2"></i>Data yang dikirim ke AI</h6>
                </div>
                <div class="bb-card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span v-for="key in evaluation.sections" :key="key" class="bb-ai-chip">{{ sectionLabel(key) }}</span>
                        <span v-if="meta.sertakan_sampel" class="bb-ai-chip"><i class="bi bi-list-ul"></i> Sampel 50 transaksi</span>
                    </div>

                    <div v-if="ringkasanData" class="bb-ai-facts">
                        <div><span>Total transaksi</span><strong>{{ formatNumber(ringkasanData.total_transaksi) }}</strong></div>
                        <div><span>Revenue biliar</span><strong>Rp {{ formatNumber(ringkasanData.revenue_biliar) }}</strong></div>
                        <div><span>Revenue F&B</span><strong>Rp {{ formatNumber(ringkasanData.revenue_fnb) }}</strong></div>
                        <div><span>Revenue total</span><strong>Rp {{ formatNumber(ringkasanData.revenue_total) }}</strong></div>
                        <div><span>Rata-rata nilai transaksi</span><strong>Rp {{ formatNumber(ringkasanData.rata_rata_nilai_transaksi) }}</strong></div>
                        <div><span>Rata-rata durasi</span><strong>{{ formatNumber(ringkasanData.rata_rata_durasi_menit) }} menit</strong></div>
                    </div>

                    <div class="small text-secondary mt-3">
                        Model: {{ evaluation.model }} ·
                        Token: {{ formatNumber(meta.prompt_tokens) }} prompt / {{ formatNumber(meta.completion_tokens) }} completion ·
                        Durasi: {{ durationLabel }} ·
                        Dibuat: {{ evaluation.created_at }}<template v-if="evaluation.created_by"> oleh {{ evaluation.created_by }}</template>
                    </div>
                    <div v-if="evaluation.error" class="small text-warning mt-2">
                        <i class="bi bi-info-circle me-1"></i>{{ evaluation.error }}
                    </div>
                </div>
            </div>
        </template>
    </AuthenticatedLayout>
</template>

<style scoped>
.bb-ai-running {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.9rem 1rem;
    border-radius: 0.9rem;
    border: 1px dashed rgba(139, 92, 246, 0.45);
    background: rgba(139, 92, 246, 0.08);
}

.bb-ai-running__spinner {
    width: 22px;
    height: 22px;
    flex-shrink: 0;
    border-radius: 50%;
    border: 3px solid rgba(139, 92, 246, 0.25);
    border-top-color: #8b5cf6;
    animation: bb-ai-spin 0.8s linear infinite;
}

@keyframes bb-ai-spin {
    to {
        transform: rotate(360deg);
    }
}

.bb-ai-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.74rem;
    font-weight: 600;
    padding: 0.3rem 0.65rem;
    border-radius: 100px;
    border: 1px solid var(--bs-border-color-translucent);
    background: var(--bs-tertiary-bg);
}

.bb-ai-metric {
    height: 100%;
    padding: 0.85rem;
    border-radius: 0.9rem;
    border: 1px solid rgba(139, 92, 246, 0.28);
    background: linear-gradient(160deg, rgba(139, 92, 246, 0.12), rgba(6, 182, 212, 0.06));
}

.bb-ai-metric__label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: var(--bs-secondary-color);
}

.bb-ai-metric__value {
    font-size: 1.05rem;
    font-weight: 800;
    margin: 0.15rem 0 0.25rem;
    word-break: break-word;
}

.bb-ai-metric__trend {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: capitalize;
}

.bb-ai-metric__trend.is-up {
    color: #10b981;
}

.bb-ai-metric__trend.is-down {
    color: #ef4444;
}

.bb-ai-metric__trend.is-flat {
    color: #94a3b8;
}

.bb-ai-metric__note {
    font-size: 0.72rem;
    color: var(--bs-secondary-color);
    margin-top: 0.25rem;
}

.bb-ai-findings {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.bb-ai-finding {
    padding: 0.75rem;
    border-radius: 0.75rem;
    border: 1px solid var(--bs-border-color-translucent);
    background: var(--bs-tertiary-bg);
}

.bb-ai-impact {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    font-size: 0.66rem;
    font-weight: 800;
    text-transform: uppercase;
    padding: 0.15rem 0.5rem;
    border-radius: 100px;
    white-space: nowrap;
}

.bb-ai-impact.is-high {
    background: rgba(239, 68, 68, 0.14);
    color: #ef4444;
}

.bb-ai-impact.is-medium {
    background: rgba(245, 158, 11, 0.14);
    color: #f59e0b;
}

.bb-ai-impact.is-low {
    background: rgba(16, 185, 129, 0.14);
    color: #10b981;
}

.bb-ai-evidence {
    margin-top: 0.4rem;
    font-size: 0.73rem;
    color: var(--bs-secondary-color);
}

.bb-ai-recos {
    margin: 0;
    padding-left: 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.bb-ai-risk {
    padding: 0.6rem 0.7rem;
    border-radius: 0.7rem;
    border-left: 3px solid rgba(245, 158, 11, 0.6);
    background: rgba(245, 158, 11, 0.07);
}

.bb-ai-facts {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.5rem;
}

.bb-ai-facts > div {
    display: flex;
    flex-direction: column;
    padding: 0.6rem 0.7rem;
    border-radius: 0.7rem;
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color-translucent);
    font-size: 0.78rem;
}

.bb-ai-facts span {
    color: var(--bs-secondary-color);
    font-size: 0.7rem;
}

.bb-ai-facts strong {
    font-size: 0.86rem;
}
</style>
