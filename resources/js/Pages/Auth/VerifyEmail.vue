<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verificar correo" />

        <div class="mb-6">
            <p class="text-sm font-semibold text-teal-700">Verificación pendiente</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-950">Revisa tu correo</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Antes de continuar, confirma tu correo con el enlace que enviamos. Si no llegó, puedes solicitar uno nuevo.
            </p>
        </div>

        <div
            class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
            v-if="verificationLinkSent"
        >
            Enviamos un nuevo enlace de verificación a tu correo.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between gap-4">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                    </svg>
                    Reenviar correo
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="auth-link"
                    >Cerrar sesión</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
