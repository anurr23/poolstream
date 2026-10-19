<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const masterMenus = [
    {
        title: 'Kelola Meja',
        icon: 'bi-columns-gap',
        desc: 'Atur nama meja dan channel relay',
        href: route('tables.index'),
        accent: '#10b981',
        accent2: '#059669',
        rgb: '16, 185, 129',
    },
    {
        title: 'Paket & Harga',
        icon: 'bi-tag-fill',
        desc: 'Tarif per jam dan durasi sesi',
        href: route('packages.index'),
        accent: '#6366f1',
        accent2: '#4f46e5',
        rgb: '99, 102, 241',
    },
    {
        title: 'Kelola Makanan',
        icon: 'bi-cup-straw',
        desc: 'Menu F&B, foto, dan harga jual',
        href: route('fnb_items.index'),
        accent: '#f97316',
        accent2: '#ea580c',
        rgb: '249, 115, 22',
    },
    {
        title: 'Kelola User',
        icon: 'bi-people-fill',
        desc: 'Akun kasir dan pengaturan akses',
        href: route('users.index'),
        accent: '#8b5cf6',
        accent2: '#7c3aed',
        rgb: '139, 92, 246',
    },
];

const reportMenus = [
    {
        title: 'Penjualan F&B',
        icon: 'bi-receipt-cutoff',
        desc: 'Rekap penjualan makanan & minuman',
        href: route('reports.fnb-sales'),
        accent: '#f59e0b',
        accent2: '#d97706',
        rgb: '245, 158, 11',
    },
    {
        title: 'Transaksi Meja',
        icon: 'bi-journal-text',
        desc: 'Riwayat transaksi sesi meja',
        href: route('reports.table-transactions'),
        accent: '#10b981',
        accent2: '#059669',
        rgb: '16, 185, 129',
    },
    {
        title: 'Total Pendapatan',
        icon: 'bi-wallet2',
        desc: 'Rekap seluruh pendapatan usaha',
        href: route('reports.revenue'),
        accent: '#6366f1',
        accent2: '#4f46e5',
        rgb: '99, 102, 241',
    },
    {
        title: 'Analityc',
        icon: 'bi-graph-up-arrow',
        desc: 'Analisis performa dan tren usaha',
        href: route('reports.analytics'),
        accent: '#8b5cf6',
        accent2: '#7c3aed',
        rgb: '139, 92, 246',
    },
];
</script>

<template>
    <Head title="Panel Admin" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="bb-header-title"><i class="bi bi-shield-lock-fill me-2" style="color: #6366f1;"></i>Panel Admin</h1>
        </template>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <p class="text-secondary mb-0 small">
                <i class="bi bi-info-circle me-1"></i>Pilih modul yang ingin dikelola.
            </p>
            <Link :href="route('landing')" class="bb-btn bb-btn--ghost bb-btn--sm">
                <i class="bi bi-arrow-left"></i> Kembali ke Menu Utama
            </Link>
        </div>

        <!-- Master Data -->
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-database-fill" style="color: #10b981;"></i>Master Data
        </h6>
        <div class="row g-3 mb-4">
            <div v-for="menu in masterMenus" :key="menu.title" class="col-12 col-sm-6 col-xl-3">
                <Link
                    :href="menu.href"
                    class="bb-menu-card bb-menu-card--sm"
                    :style="{
                        '--bb-menu-accent': menu.accent,
                        '--bb-menu-accent-2': menu.accent2,
                        '--bb-menu-accent-rgb': menu.rgb,
                    }"
                >
                    <div class="d-flex align-items-center gap-3">
                        <div class="bb-menu-icon">
                            <i class="bi" :class="menu.icon"></i>
                        </div>
                        <div style="min-width: 0;">
                            <div class="bb-menu-title text-truncate">{{ menu.title }}</div>
                            <div class="bb-menu-desc">{{ menu.desc }}</div>
                        </div>
                    </div>
                    <span class="bb-menu-cta mt-3">
                        Buka <i class="bi bi-arrow-right"></i>
                    </span>
                </Link>
            </div>
        </div>

        <!-- Reports -->
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-graph-up-arrow" style="color: #f59e0b;"></i>Laporan
        </h6>
        <div class="row g-3">
            <div v-for="menu in reportMenus" :key="menu.title" class="col-12 col-sm-6 col-xl-3">
                <Link
                    :href="menu.href"
                    class="bb-menu-card bb-menu-card--sm"
                    :style="{
                        '--bb-menu-accent': menu.accent,
                        '--bb-menu-accent-2': menu.accent2,
                        '--bb-menu-accent-rgb': menu.rgb,
                    }"
                >
                    <div class="d-flex align-items-center gap-3">
                        <div class="bb-menu-icon">
                            <i class="bi" :class="menu.icon"></i>
                        </div>
                        <div style="min-width: 0;">
                            <div class="bb-menu-title text-truncate">{{ menu.title }}</div>
                            <div class="bb-menu-desc">{{ menu.desc }}</div>
                        </div>
                    </div>
                    <span class="bb-menu-cta mt-3">
                        Buka <i class="bi bi-arrow-right"></i>
                    </span>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
