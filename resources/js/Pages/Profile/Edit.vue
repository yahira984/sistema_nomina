<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-teal-700">Cuenta de usuario</p>
                <h2 class="text-xl font-semibold text-slate-950 sm:text-2xl">Perfil</h2>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-6">
                <div class="app-panel p-5 sm:p-6">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="app-panel p-5 sm:p-6">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="app-panel p-5 sm:p-6">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
