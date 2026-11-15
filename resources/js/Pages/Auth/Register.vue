<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar Akun" />

        <h5 class="bb-text-primary fw-bold mb-1">Daftar Akun Customer</h5>
        <p class="small mb-4 text-secondary">
            Buat akun untuk booking meja online. Kode OTP akan dikirim ke email Anda.
        </p>

        <div v-if="$page.props.errors?.email" class="alert alert-danger rounded-3 mb-4 small">
            <i class="bi bi-exclamation-circle me-1"></i>{{ $page.props.errors.email }}
        </div>

        <form @submit.prevent="submit">
            <div class="mb-3">
                <label for="name" class="bb-label text-secondary">Nama Lengkap</label>
                <input
                    id="name"
                    type="text"
                    class="bb-input w-100 mt-1"
                    :class="{ 'border-danger': form.errors.name }"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Nama Anda"
                />
                <div v-if="form.errors.name" class="small text-danger mt-1">{{ form.errors.name }}</div>
            </div>

            <div class="mb-3">
                <label for="email" class="bb-label text-secondary">Email</label>
                <input
                    id="email"
                    type="email"
                    class="bb-input w-100 mt-1"
                    :class="{ 'border-danger': form.errors.email }"
                    v-model="form.email"
                    required
                    autocomplete="email"
                    placeholder="nama@email.com"
                />
                <div v-if="form.errors.email" class="small text-danger mt-1">{{ form.errors.email }}</div>
            </div>

            <div class="mb-3">
                <label for="password" class="bb-label text-secondary">Password</label>
                <input
                    id="password"
                    type="password"
                    class="bb-input w-100 mt-1"
                    :class="{ 'border-danger': form.errors.password }"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                />
                <div v-if="form.errors.password" class="small text-danger mt-1">{{ form.errors.password }}</div>
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="bb-label text-secondary">Konfirmasi Password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    class="bb-input w-100 mt-1"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi password"
                />
            </div>

            <button type="submit" :disabled="form.processing" class="bb-btn bb-btn--success w-100 py-3">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-person-plus me-1"></i>
                Daftar
            </button>

            <a
                :href="route('auth.google.redirect')"
                class="bb-btn bb-btn--ghost w-100 py-3 mt-3"
            >
                <i class="bi bi-google me-2"></i>
                Daftar dengan Google
            </a>

            <div class="small text-secondary text-center mt-4">
                <i class="bi bi-info-circle me-1"></i>
                Akun kasir/admin dibuat oleh admin.
            </div>

            <div class="text-center small mt-3 text-secondary">
                Sudah punya akun?
                <Link :href="route('login')" class="fw-bold text-decoration-none" style="color: #10b981;">
                    Masuk
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
