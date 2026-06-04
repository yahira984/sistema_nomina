<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    empleadosActivos: Number,
    semanaActual: Number,
    totalPagado: Number
});
</script>

<template>
    <Head title="Panel Principal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <p class="text-sm font-semibold text-teal-700">Resumen operativo</p>
                <h2 class="text-2xl font-semibold text-slate-950">Bienvenido al Sistema PROMATEC-LUGARTH</h2>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-8">
                <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="metric-card">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="metric-label">Personal activo</p>
                                <h3 class="metric-value">{{ empleadosActivos }}</h3>
                                <p class="metric-note">Colaboradores registrados</p>
                            </div>
                            <div class="soft-icon-blue">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="metric-label">Semana contable</p>
                                <h3 class="metric-value">No. {{ semanaActual }}</h3>
                                <p class="metric-note">Corte de miércoles a martes</p>
                            </div>
                            <div class="soft-icon-amber">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="metric-label">Gasto semanal</p>
                                <h3 class="metric-value">${{ Number(totalPagado || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</h3>
                                <p class="metric-note">Total neto dispersado</p>
                            </div>
                            <div class="soft-icon-emerald">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-4 flex flex-col gap-1">
                        <h3 class="section-title">Módulos principales</h3>
                        <p class="section-copy">Accede a las tareas clave de nómina desde un solo lugar.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <Link :href="route('empleados.index')" class="action-card group">
                            <div class="flex items-start gap-4">
                                <div class="soft-icon-blue">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-5m-4 0V5a2 2 0 1 1 4 0v1m-4 0a2 2 0 1 0 4 0m-5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 0c1.306 0 2.417.835 2.83 2M15 11h3m-3 4h2" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-base font-semibold text-slate-950">Directorio de personal</h4>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">Alta, edición y consulta de trabajadores, puestos, cuentas y tarifas.</p>
                                    <div class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-teal-700">
                                        Abrir módulo
                                        <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </Link>

                        <Link :href="route('asistencias.index')" class="action-card group">
                            <div class="flex items-start gap-4">
                                <div class="soft-icon-emerald">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-base font-semibold text-slate-950">Control de asistencias</h4>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">Captura entradas y salidas para calcular jornadas semanales.</p>
                                    <div class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-teal-700">
                                        Abrir módulo
                                        <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </Link>

                        <Link :href="route('nominas.index')" class="action-card group">
                            <div class="flex items-start gap-4">
                                <div class="soft-icon-amber">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-base font-semibold text-slate-950">Generar nóminas</h4>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">Calcula pagos, marca estatus y genera recibos PDF listos para imprimir.</p>
                                    <div class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-teal-700">
                                        Abrir módulo
                                        <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
