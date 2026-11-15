<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import Logo from '@/Components/Logo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userInitial = computed(() => (user.value?.name || '?').charAt(0).toUpperCase());

const isDark = ref(true);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    document.documentElement.setAttribute('data-bs-theme', isDark.value ? 'dark' : 'light');
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};

onMounted(() => {
    if (localStorage.theme === 'light') {
        isDark.value = false;
        document.documentElement.setAttribute('data-bs-theme', 'light');
    } else {
        isDark.value = true;
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    }
});
</script>

<template>
    <div class="bb-customer">
        <Toast />

        <header class="bb-customer-header">
            <Link :href="route('landing')" class="bb-customer-brand">
                <Logo :size="34" />
                <div>
                    <div class="bb-customer-brand-name">PoolStream</div>
                    <div class="bb-customer-brand-sub">Booking Online</div>
                </div>
            </Link>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="bb-theme-toggle" title="Ganti tema" @click="toggleTheme">
                    <i :class="isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill'"></i>
                </button>

                <div class="dropdown">
                    <button
                        class="bb-customer-avatar dropdown-toggle"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        style="--bs-dropdown-toggle-icon-transform: none;"
                    >
                        <img
                            v-if="user?.photo_url"
                            :src="user.photo_url"
                            :alt="user.name"
                            class="bb-customer-avatar-img"
                        />
                        <span v-else>{{ userInitial }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" style="border-radius: 0.75rem; min-width: 210px;">
                        <li class="px-3 py-2">
                            <div class="fw-bold small">{{ user?.name }}</div>
                            <div class="small text-secondary">@{{ user?.username }}</div>
                        </li>
                        <li><hr class="dropdown-divider my-1" /></li>
                        <li>
                            <Link class="dropdown-item rounded-2 py-2 px-3" :href="route('profile.edit')">
                                <i class="bi bi-person-circle me-2"></i> Profil
                            </Link>
                        </li>
                        <li>
                            <Link class="dropdown-item rounded-2 py-2 px-3 text-danger" :href="route('logout')" method="post" as="button">
                                <i class="bi bi-box-arrow-left me-2"></i> Log Out
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="bb-customer-main">
            <slot />
        </main>

        <nav class="bb-customer-nav" aria-label="Navigasi customer">
            <div class="bb-customer-nav__inner">
                <Link
                    :href="route('customer.booking.index')"
                    class="bb-customer-nav__item"
                    :class="{ 'is-active': route().current('customer.booking.index') }"
                >
                    <i class="bi bi-calendar2-plus"></i>
                    <span>Booking</span>
                </Link>
                <Link
                    :href="route('customer.history')"
                    class="bb-customer-nav__item"
                    :class="{ 'is-active': route().current('customer.history') }"
                >
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.bb-customer {
    height: 100vh;
    height: 100dvh;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-y: contain;
    background: var(--bs-body-bg);
}

.bb-customer-header {
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.7rem 1rem;
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color-translucent);
}

.bb-customer-brand {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    text-decoration: none;
    color: var(--bs-body-color);
    min-width: 0;
}

.bb-customer-brand-name {
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.02em;
}

.bb-customer-brand-sub {
    font-size: 0.68rem;
    color: var(--bs-secondary-color);
}

.bb-customer-avatar {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    border: 1px solid var(--bs-border-color);
    background: linear-gradient(135deg, #06b6d4, #0e7490);
    color: #fff;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 0;
}

.bb-customer-avatar::after {
    display: none;
}

.bb-customer-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bb-customer-main {
    width: 100%;
    max-width: 860px;
    margin: 0 auto;
    padding: 1rem 1rem calc(6.5rem + env(safe-area-inset-bottom));
}

.bb-customer-nav {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 40;
    background: var(--bs-body-bg);
    border-top: 1px solid var(--bs-border-color-translucent);
    padding-bottom: env(safe-area-inset-bottom);
}

.bb-customer-nav__inner {
    display: flex;
    width: 100%;
    max-width: 860px;
    margin: 0 auto;
}

.bb-customer-nav__item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    padding: 0.55rem 0.5rem 0.6rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    transition: color 0.2s ease;
}

.bb-customer-nav__item i {
    font-size: 1.3rem;
    line-height: 1;
}

.bb-customer-nav__item.is-active {
    color: #06b6d4;
}

@media (min-width: 900px) {
    .bb-customer-header {
        padding: 0.8rem 1.5rem;
    }

    .bb-customer-main {
        padding-top: 1.5rem;
        padding-bottom: 7rem;
    }

    .bb-customer-nav {
        left: 50%;
        right: auto;
        bottom: 1.1rem;
        width: 360px;
        transform: translateX(-50%);
        border: 1px solid var(--bs-border-color);
        border-radius: 1rem;
        box-shadow: 0 18px 40px rgba(2, 6, 23, 0.22);
        overflow: hidden;
    }
}
</style>
