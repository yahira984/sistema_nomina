<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-900">
        <div class="grid min-h-screen lg:grid-cols-[1fr_520px]">
            <section class="hidden items-center px-10 lg:flex">
                <div class="mx-auto max-w-xl text-white">
                    <Link href="/" class="inline-flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-white text-slate-950">
                            <ApplicationLogo class="h-9 w-9" />
                        </span>
                        <span>
                            <span class="block text-base font-semibold">Sistema de Nóminas</span>
                            <span class="block text-sm text-slate-300">PROMATEC-LUGARTH</span>
                        </span>
                    </Link>

                    <div class="mt-16 border-l border-teal-300/50 pl-6">
                        <p class="text-sm font-semibold uppercase text-teal-200">Acceso administrativo</p>
                        <h1 class="mt-4 text-4xl font-semibold leading-tight">
                            Control claro para empleados, asistencias y pagos.
                        </h1>
                        <p class="mt-5 text-base leading-7 text-slate-300">
                            Una experiencia enfocada en capturar información rápido, revisar estados y generar recibos sin distracciones.
                        </p>
                    </div>
                </div>
            </section>

            <section class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 sm:px-6">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex justify-center lg:hidden">
                        <Link href="/" class="inline-flex items-center gap-3 text-slate-950">
                            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-950 text-teal-300">
                                <ApplicationLogo class="h-8 w-8" />
                            </span>
                            <span>
                                <span class="block text-sm font-semibold">Sistema de Nóminas</span>
                                <span class="block text-xs text-slate-500">PROMATEC-LUGARTH</span>
                            </span>
                        </Link>
                    </div>

                    <div class="auth-card">
                        <slot />
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
