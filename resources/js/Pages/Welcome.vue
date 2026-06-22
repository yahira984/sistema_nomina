<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Sistema de Nóminas" />

    <main class="min-h-screen bg-slate-950 text-white">
        <div class="content-wrap flex min-h-screen flex-col">
            <header class="flex items-center justify-between py-6">
                <Link href="/" class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-white text-slate-950">
                        <ApplicationLogo class="h-8 w-8" />
                    </span>
                    <span>
                        <span class="block text-sm font-semibold">Sistema de Nóminas</span>
                        <span class="block text-xs text-slate-300">PROMATEC-LUGARTH</span>
                    </span>
                </Link>

                <nav v-if="canLogin" class="flex items-center gap-2">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15"
                    >
                        Abrir panel
                    </Link>

                    <template v-else>
                        <Link :href="route('login')" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15">
                            Iniciar sesión
                        </Link>

                        <Link v-if="canRegister" :href="route('register')" class="btn-accent">
                            Crear cuenta
                        </Link>
                    </template>
                </nav>
            </header>

            <section class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[0.95fr_1.05fr]">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase text-teal-200">Administración de personal y pagos</p>
                    <h1 class="mt-5 text-4xl font-semibold leading-tight sm:text-5xl">
                        Nóminas claras, asistencias ordenadas y recibos listos en minutos.
                    </h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-300">
                        Gestiona trabajadores, captura jornadas y controla pagos desde un panel pensado para operación diaria.
                    </p>

                    <div v-if="canLogin" class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="btn-accent"
                        >
                            Entrar al panel
                        </Link>
                        <template v-else>
                            <Link :href="route('login')" class="btn-accent">
                                Iniciar sesión
                            </Link>
                            <Link v-if="canRegister" :href="route('register')" class="btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/15">
                                Registrar usuario
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="rounded-lg border border-white/10 bg-white p-4 text-slate-900 shadow-2xl">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                            <div>
                                <p class="text-xs font-semibold uppercase text-teal-700">Vista operativa</p>
                                <h2 class="mt-1 text-lg font-semibold text-slate-950">Semana contable</h2>
                            </div>
                            <span class="status-pill status-success">Activa</span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-lg border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase text-slate-500">Empleados</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950">Activos</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase text-slate-500">Asistencias</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950">Horas</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase text-slate-500">Recibos</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950">PDF</p>
                            </div>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
                            <div class="grid grid-cols-4 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase text-slate-500">
                                <span>Empleado</span>
                                <span>Cuenta</span>
                                <span>Estado</span>
                                <span class="text-right">Recibo</span>
                            </div>
                            <div class="grid grid-cols-4 items-center px-4 py-3 text-sm">
                                <span class="font-semibold text-slate-950">Personal</span>
                                <span class="text-slate-500">CLABE</span>
                                <span><span class="status-pill status-warning">Pendiente</span></span>
                                <span class="text-right font-semibold text-teal-700">Crear</span>
                            </div>
                            <div class="grid grid-cols-4 items-center border-t border-slate-100 px-4 py-3 text-sm">
                                <span class="font-semibold text-slate-950">Operación</span>
                                <span class="text-slate-500">Banco</span>
                                <span><span class="status-pill status-success">Liquidado</span></span>
                                <span class="text-right font-semibold text-teal-700">PDF</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>

