<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirmar contraseña" />

        <div class="mb-6">
            <p class="text-sm font-semibold text-teal-700">Área protegida</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-950">Confirma tu contraseña</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Por seguridad, confirma tu contraseña antes de continuar.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3V6a3 3 0 0 0-6 0v2c0 1.657 1.343 3 3 3Zm7 0H5a2 2 0 0 0-2 2v7h18v-7a2 2 0 0 0-2-2Z" />
                    </svg>
                    Confirmar
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
