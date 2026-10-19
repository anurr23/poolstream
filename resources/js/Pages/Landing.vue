<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import MenuIllustration from '@/Components/MenuIllustration.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isAuth = computed(() => !!user.value);
const isAdmin = computed(() => user.value?.role === 'admin');

const isDark = ref(true);
const currentTime = ref('');
const currentDate = ref('');

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour12: false });
    currentDate.value = now.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

let clock;
onMounted(() => {
    if (localStorage.theme === 'light') {
        isDark.value = false;
        document.documentElement.setAttribute('data-bs-theme', 'light');
    } else {
        isDark.value = true;
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    }
    updateClock();
    clock = setInterval(updateClock, 1000);
});

onUnmounted(() => clearInterval(clock));

const toggleTheme = () => {
    isDark.value = !isDark.value;
    document.documentElement.setAttribute('data-bs-theme', isDark.value ? 'dark' : 'light');
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};

const userInitial = computed(() => (user.value?.name || '?').charAt(0).toUpperCase());

// Kartu menuju modul tujuan. Belum login? Route tujuan dilindungi middleware
// 'auth', jadi server mengarahkan ke halaman login (intended url tersimpan).
const menus = computed(() => [
    {
        key: 'admin',
        title: 'Admin',
        illustration: 'admin',
        tag: 'Data, laporan, user',
        accent: '#6366f1',
        accent2: '#4f46e5',
        rgb: '99, 102, 241',
        href: route('admin.index'),
        adminOnly: true,
    },
    {
        key: 'kasir_meja',
        title: 'Kasir Meja',
        illustration: 'meja',
        tag: 'Sesi meja & timer',
        accent: '#10b981',
        accent2: '#059669',
        rgb: '16, 185, 129',
        href: route('dashboard'),
    },
    {
        key: 'kasir',
        title: 'Kasir F&B',
        illustration: 'fnb',
        tag: 'Pesanan F&B',
        accent: '#f59e0b',
        accent2: '#d97706',
        rgb: '245, 158, 11',
        href: route('fnb-orders.index'),
    },
].filter((menu) => !menu.adminOnly || !isAuth.value || isAdmin.value));
</script>

<template>
    <Head title="PoolStream - Sistem Manajemen Pool" />

    <div class="bb-landing">
        <div class="bb-landing-inner">
            <!-- Top Bar -->
            <div class="bb-landing-top">
                <Link :href="route('landing')" class="bb-landing-brand">
                    <img src="/poolstream/public/images/logo.png" alt="PoolStream Logo" style="width: 44px; height: 44px; object-fit: contain;">
                    <div>
                        <div class="bb-landing-brand-name">PoolStream</div>
                        <div class="bb-landing-brand-sub">Management System</div>
                    </div>
                </Link>

                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button @click="toggleTheme" class="bb-theme-toggle" title="Ganti tema">
                        <i :class="isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill'"></i>
                    </button>
                    <div v-if="isAuth" class="bb-landing-user">
                        <div class="bb-landing-avatar">{{ userInitial }}</div>
                        <div class="d-none d-sm-block">
                            <div class="bb-landing-user-name">{{ user.name }}</div>
                            <div class="bb-landing-user-role text-capitalize">{{ user.role }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero -->
            <div class="bb-landing-hero">
                <span class="bb-badge bb-landing-chip">
                    <i class="bi bi-grid-1x2-fill"></i>Sistem Manajemen Pool &amp; F&amp;B
                </span>
                <h1 class="bb-landing-title">
                    Kelola Pool &amp; <span class="text-gradient">F&amp;B</span> dalam Satu Halaman
                </h1>

                <div class="bb-landing-clock">
                    <div class="bb-landing-clock-main">
                        <span class="bb-live-dot"></span>
                        <span class="bb-landing-time">{{ currentTime }}</span>
                    </div>
                    <span class="bb-landing-date">{{ currentDate }}</span>
                </div>

                <!-- Animated Waves -->
                <div class="bb-landing-wave" aria-hidden="true">
                    <svg class="bb-wave bb-wave--1" viewBox="0 0 1440 120" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="bbWaveFill1" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#10b981" stop-opacity="0.26" />
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                            </linearGradient>
                            <linearGradient id="bbWaveCrest1" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#34d399" stop-opacity="0" />
                                <stop offset="25%" stop-color="#34d399" stop-opacity="0.8" />
                                <stop offset="50%" stop-color="#34d399" stop-opacity="0" />
                                <stop offset="75%" stop-color="#34d399" stop-opacity="0.8" />
                                <stop offset="100%" stop-color="#34d399" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path fill="url(#bbWaveFill1)" d="M0,62.4 C20,67.4 40,71.3 60,72.5 C80,73.7 100,70.5 120,69.6 C140,68.6 160,66.8 180,66.8 C200,66.8 220,69.4 240,69.8 C260,70.2 280,71.0 300,69.2 C320,67.4 340,63.1 360,59.1 C380,55.1 400,49.5 420,45.5 C440,41.5 460,37.9 480,34.9 C500,31.9 520,28.8 540,27.7 C560,26.6 580,25.9 600,28.3 C620,30.7 640,36.6 660,42.3 C680,48.0 700,57.4 720,62.4 C740,67.4 760,71.3 780,72.5 C800,73.7 820,70.5 840,69.6 C860,68.6 880,66.8 900,66.8 C920,66.8 940,69.4 960,69.8 C980,70.2 1000,71.0 1020,69.2 C1040,67.4 1060,63.1 1080,59.1 C1100,55.1 1120,49.5 1140,45.5 C1160,41.5 1180,37.9 1200,34.9 C1220,31.9 1240,28.8 1260,27.7 C1280,26.6 1300,25.9 1320,28.3 C1340,30.7 1360,36.6 1380,42.3 C1400,48.0 1420,57.4 1440,62.4 L1440,120 L0,120 Z" />
                        <path fill="none" stroke="url(#bbWaveCrest1)" stroke-width="2.2" d="M0,62.4 C20,67.4 40,71.3 60,72.5 C80,73.7 100,70.5 120,69.6 C140,68.6 160,66.8 180,66.8 C200,66.8 220,69.4 240,69.8 C260,70.2 280,71.0 300,69.2 C320,67.4 340,63.1 360,59.1 C380,55.1 400,49.5 420,45.5 C440,41.5 460,37.9 480,34.9 C500,31.9 520,28.8 540,27.7 C560,26.6 580,25.9 600,28.3 C620,30.7 640,36.6 660,42.3 C680,48.0 700,57.4 720,62.4 C740,67.4 760,71.3 780,72.5 C800,73.7 820,70.5 840,69.6 C860,68.6 880,66.8 900,66.8 C920,66.8 940,69.4 960,69.8 C980,70.2 1000,71.0 1020,69.2 C1040,67.4 1060,63.1 1080,59.1 C1100,55.1 1120,49.5 1140,45.5 C1160,41.5 1180,37.9 1200,34.9 C1220,31.9 1240,28.8 1260,27.7 C1280,26.6 1300,25.9 1320,28.3 C1340,30.7 1360,36.6 1380,42.3 C1400,48.0 1420,57.4 1440,62.4" />
                    </svg>

                    <svg class="bb-wave bb-wave--2" viewBox="0 0 1440 120" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="bbWaveFill2" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#6366f1" stop-opacity="0.22" />
                                <stop offset="100%" stop-color="#6366f1" stop-opacity="0" />
                            </linearGradient>
                            <linearGradient id="bbWaveCrest2" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#818cf8" stop-opacity="0" />
                                <stop offset="25%" stop-color="#818cf8" stop-opacity="0.6" />
                                <stop offset="50%" stop-color="#818cf8" stop-opacity="0" />
                                <stop offset="75%" stop-color="#818cf8" stop-opacity="0.6" />
                                <stop offset="100%" stop-color="#818cf8" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path fill="url(#bbWaveFill2)" d="M0,78.8 C20,76.2 40,71.7 60,69.5 C80,67.3 100,66.1 120,65.4 C140,64.7 160,66.1 180,65.4 C200,64.7 220,63.4 240,61.3 C260,59.2 280,54.7 300,52.8 C320,50.9 340,49.0 360,49.7 C380,50.4 400,53.8 420,57 C440,60.2 460,65.3 480,68.8 C500,72.3 520,75.7 540,78.2 C560,80.8 580,82.9 600,84.1 C620,85.3 640,86.0 660,85.1 C680,84.2 700,81.4 720,78.8 C740,76.2 760,71.7 780,69.5 C800,67.3 820,66.1 840,65.4 C860,64.7 880,66.1 900,65.4 C920,64.7 940,63.4 960,61.3 C980,59.2 1000,54.7 1020,52.8 C1040,50.9 1060,49.0 1080,49.7 C1100,50.4 1120,53.8 1140,57 C1160,60.2 1180,65.3 1200,68.8 C1220,72.3 1240,75.7 1260,78.2 C1280,80.8 1300,82.9 1320,84.1 C1340,85.3 1360,86.0 1380,85.1 C1400,84.2 1420,81.4 1440,78.8 L1440,120 L0,120 Z" />
                        <path fill="none" stroke="url(#bbWaveCrest2)" stroke-width="1.8" d="M0,78.8 C20,76.2 40,71.7 60,69.5 C80,67.3 100,66.1 120,65.4 C140,64.7 160,66.1 180,65.4 C200,64.7 220,63.4 240,61.3 C260,59.2 280,54.7 300,52.8 C320,50.9 340,49.0 360,49.7 C380,50.4 400,53.8 420,57 C440,60.2 460,65.3 480,68.8 C500,72.3 520,75.7 540,78.2 C560,80.8 580,82.9 600,84.1 C620,85.3 640,86.0 660,85.1 C680,84.2 700,81.4 720,78.8 C740,76.2 760,71.7 780,69.5 C800,67.3 820,66.1 840,65.4 C860,64.7 880,66.1 900,65.4 C920,64.7 940,63.4 960,61.3 C980,59.2 1000,54.7 1020,52.8 C1040,50.9 1060,49.0 1080,49.7 C1100,50.4 1120,53.8 1140,57 C1160,60.2 1180,65.3 1200,68.8 C1220,72.3 1240,75.7 1260,78.2 C1280,80.8 1300,82.9 1320,84.1 C1340,85.3 1360,86.0 1380,85.1 C1400,84.2 1420,81.4 1440,78.8" />
                    </svg>

                    <svg class="bb-wave bb-wave--3" viewBox="0 0 1440 120" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="bbWaveFill3" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.16" />
                                <stop offset="100%" stop-color="#06b6d4" stop-opacity="0" />
                            </linearGradient>
                            <linearGradient id="bbWaveCrest3" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#22d3ee" stop-opacity="0" />
                                <stop offset="25%" stop-color="#22d3ee" stop-opacity="0.45" />
                                <stop offset="50%" stop-color="#22d3ee" stop-opacity="0" />
                                <stop offset="75%" stop-color="#22d3ee" stop-opacity="0.45" />
                                <stop offset="100%" stop-color="#22d3ee" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path fill="url(#bbWaveFill3)" d="M0,74.4 C20,73.7 40,72.8 60,72.6 C80,72.4 100,72.9 120,73.2 C140,73.5 160,74.0 180,74.4 C200,74.8 220,74.5 240,75.4 C260,76.3 280,77.5 300,79.6 C320,81.7 340,85.6 360,87.9 C380,90.2 400,92.9 420,93.3 C440,93.7 460,92.1 480,90.4 C500,88.7 520,85.2 540,83.2 C560,81.2 580,79.7 600,78.6 C620,77.5 640,77.4 660,76.7 C680,76.0 700,75.1 720,74.4 C740,73.7 760,72.8 780,72.6 C800,72.4 820,72.9 840,73.2 C860,73.5 880,74.0 900,74.4 C920,74.8 940,74.5 960,75.4 C980,76.3 1000,77.5 1020,79.6 C1040,81.7 1060,85.6 1080,87.9 C1100,90.2 1120,92.9 1140,93.3 C1160,93.7 1180,92.1 1200,90.4 C1220,88.7 1240,85.2 1260,83.2 C1280,81.2 1300,79.7 1320,78.6 C1340,77.5 1360,77.4 1380,76.7 C1400,76.0 1420,75.1 1440,74.4 L1440,120 L0,120 Z" />
                        <path fill="none" stroke="url(#bbWaveCrest3)" stroke-width="1.4" d="M0,74.4 C20,73.7 40,72.8 60,72.6 C80,72.4 100,72.9 120,73.2 C140,73.5 160,74.0 180,74.4 C200,74.8 220,74.5 240,75.4 C260,76.3 280,77.5 300,79.6 C320,81.7 340,85.6 360,87.9 C380,90.2 400,92.9 420,93.3 C440,93.7 460,92.1 480,90.4 C500,88.7 520,85.2 540,83.2 C560,81.2 580,79.7 600,78.6 C620,77.5 640,77.4 660,76.7 C680,76.0 700,75.1 720,74.4 C740,73.7 760,72.8 780,72.6 C800,72.4 820,72.9 840,73.2 C860,73.5 880,74.0 900,74.4 C920,74.8 940,74.5 960,75.4 C980,76.3 1000,77.5 1020,79.6 C1040,81.7 1060,85.6 1080,87.9 C1100,90.2 1120,92.9 1140,93.3 C1160,93.7 1180,92.1 1200,90.4 C1220,88.7 1240,85.2 1260,83.2 C1280,81.2 1300,79.7 1320,78.6 C1340,77.5 1360,77.4 1380,76.7 C1400,76.0 1420,75.1 1440,74.4" />
                    </svg>
                </div>
            </div>

            <!-- Menu Cards -->
            <div class="row g-4">
                <div v-for="menu in menus" :key="menu.key" class="col-12 col-md-6 col-lg-4">
                    <Link
                        :href="menu.href"
                        class="bb-menu-card"
                        :style="{
                            '--bb-menu-accent': menu.accent,
                            '--bb-menu-accent-2': menu.accent2,
                            '--bb-menu-accent-rgb': menu.rgb,
                        }"
                    >
                        <div class="d-flex justify-content-between align-items-start">
                            <MenuIllustration :type="menu.illustration" />
                            <span v-if="!isAuth" class="bb-menu-lock" title="Login diperlukan"><i class="bi bi-lock-fill"></i></span>
                        </div>

                        <h5 class="bb-menu-title mt-3 mb-1">{{ menu.title }}</h5>
                        <p class="bb-menu-desc mb-3">{{ menu.tag }}</p>

                        <span class="bb-menu-cta">
                            Masuk <i class="bi bi-arrow-right"></i>
                        </span>
                    </Link>
                </div>
            </div>
        </div>

        <footer class="bb-landing-footer">
            <span class="bb-landing-footer-dot"></span>
            <span>&copy; {{ new Date().getFullYear() }} PoolStream</span>
        </footer>
    </div>
</template>
