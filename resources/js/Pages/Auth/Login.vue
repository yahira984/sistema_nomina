<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
});

const sessionExpired = ref(false);

onMounted(() => {
    const queryExpired = new URLSearchParams(window.location.search).get('expired') === '1';
    const storedExpired = window.sessionStorage.getItem('session-expired-notice') === '1';
    sessionExpired.value = queryExpired || storedExpired;
    window.sessionStorage.removeItem('session-expired-notice');
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div class="mb-6">
            <p class="text-sm font-semibold text-teal-700">Bienvenido de vuelta</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-950">Inicia sesión</h1>
            <p class="mt-2 text-sm text-slate-500">Accede al panel para gestionar empleados, asistencias y nóminas.</p>
        </div>

        <div v-if="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ status }}
        </div>

        <div v-if="sessionExpired" class="mb-4 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
            <i class="ti ti-clock-exclamation mt-0.5 text-xl text-amber-700" aria-hidden="true"></i>
            <div>
                <p class="font-extrabold">Tu sesión terminó por inactividad</p>
                <p class="mt-1 leading-5">Por seguridad, el sistema se desconecta después de una hora sin uso. Inicia sesión nuevamente para continuar.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Correo electrónico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Contraseña" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="auth-link"
                >
                    ¿Olvidaste tu contraseña?
                </Link>

                <PrimaryButton
                    class="ms-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H3m12 0-4-4m4 4-4 4m5-10h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-3" />
                    </svg>
                    Entrar
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
