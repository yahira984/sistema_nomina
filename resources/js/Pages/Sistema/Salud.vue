<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import OperationCenter from '@/Components/OperationCenter.vue'
import { Head, router } from '@inertiajs/vue3'

const props = defineProps({
    health: { type: Object, default: () => ({ services: [], inconsistencies: {} }) },
    backups: { type: Array, default: () => [] },
    failures: { type: Array, default: () => [] },
    operations: { type: Array, default: () => [] },
})

const stateClass = status => ({
    healthy: 'status-success',
    warning: 'status-warning',
    critical: 'status-danger',
}[status] || 'status-neutral')

const stateIcon = status => ({
    healthy: 'ti-circle-check',
    warning: 'ti-alert-triangle',
    critical: 'ti-alert-circle',
}[status] || 'ti-info-circle')

const stateLabel = status => ({
    healthy: 'Correcto',
    warning: 'Advertencia',
    critical: 'Crítico',
    verified: 'Verificado',
    invalid: 'Inválido',
    created: 'Pendiente',
    resolved: 'Resuelto',
    pending: 'Pendiente',
    failed: 'Falló',
}[status] || status)

const refresh = () => router.reload({ preserveScroll: true })
</script>

<template>
    <Head title="Salud del sistema" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase text-blue-700 dark:text-blue-300">Diagnóstico</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Salud del sistema</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Estado técnico, respaldos e inconsistencias que requieren atención.</p>
                </div>
                <button class="btn-secondary" @click="refresh"><i class="ti ti-refresh" aria-hidden="true"></i> Actualizar</button>
            </div>
        </template>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <article v-for="service in health.services" :key="service.key" class="metric-card">
                <div class="flex items-start justify-between gap-3">
                    <p class="metric-label">{{ service.label }}</p>
                    <i :class="['ti text-xl', stateIcon(service.status), service.status === 'healthy' ? 'text-emerald-600' : service.status === 'warning' ? 'text-amber-600' : 'text-rose-600']" aria-hidden="true"></i>
                </div>
                <span :class="['status-pill mt-4', stateClass(service.status)]">{{ stateLabel(service.status) }}</span>
                <p class="metric-note">{{ service.message }}</p>
            </article>
        </section>

        <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(360px,.8fr)]">
            <section class="app-panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Reporte de inconsistencias</h2>
                        <p class="panel-subtitle">{{ health.inconsistencies?.total || 0 }} elementos detectados.</p>
                    </div>
                    <span :class="['status-pill', health.inconsistencies?.total ? 'status-warning' : 'status-success']">
                        {{ health.inconsistencies?.total ? 'Requiere revisión' : 'Sin hallazgos' }}
                    </span>
                </div>

                <div class="grid gap-px bg-slate-200 sm:grid-cols-2 dark:bg-slate-700">
                    <div class="bg-white p-4 dark:bg-slate-900">
                        <p class="metric-label">Números duplicados</p>
                        <p class="mt-2 text-2xl font-extrabold">{{ health.inconsistencies?.duplicate_employees?.length || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 dark:bg-slate-900">
                        <p class="metric-label">Fotografías faltantes</p>
                        <p class="mt-2 text-2xl font-extrabold">{{ health.inconsistencies?.missing_photos?.length || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 dark:bg-slate-900">
                        <p class="metric-label">Asistencias huérfanas</p>
                        <p class="mt-2 text-2xl font-extrabold">{{ health.inconsistencies?.orphan_attendance || 0 }}</p>
                    </div>
                    <div class="bg-white p-4 dark:bg-slate-900">
                        <p class="metric-label">Nóminas huérfanas</p>
                        <p class="mt-2 text-2xl font-extrabold">{{ health.inconsistencies?.orphan_payroll || 0 }}</p>
                    </div>
                </div>

                <div v-if="health.inconsistencies?.duplicate_employees?.length" class="border-t border-slate-200 p-4 dark:border-slate-700">
                    <h3 class="text-sm font-bold">Números repetidos</h3>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span v-for="item in health.inconsistencies.duplicate_employees" :key="item.key" class="status-pill status-danger">#{{ item.key }} · {{ item.count }}</span>
                    </div>
                </div>

                <div v-if="health.inconsistencies?.missing_photos?.length" class="border-t border-slate-200 p-4 dark:border-slate-700">
                    <h3 class="text-sm font-bold">Empleados sin fotografía</h3>
                    <div class="mt-2 max-h-56 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                        <div v-for="employee in health.inconsistencies.missing_photos" :key="employee.id" class="flex justify-between py-2 text-sm">
                            <span class="truncate">{{ employee.nombre_completo }}</span>
                            <strong>#{{ employee.numero_empleado || 'S/N' }}</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="app-panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Centro de procesos</h2>
                        <p class="panel-subtitle">Progreso y descargas recientes.</p>
                    </div>
                </div>
                <div class="p-5">
                    <OperationCenter :initial-operations="operations" />
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Usa el botón para revisar importaciones y exportaciones en curso.</p>
                </div>
            </section>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <section class="app-panel">
                <div class="panel-header"><div><h2 class="panel-title">Últimos respaldos</h2><p class="panel-subtitle">Integridad y fecha de verificación.</p></div></div>
                <div v-if="backups.length === 0" class="empty-state">No hay respaldos registrados.</div>
                <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                    <div v-for="backup in backups" :key="backup.id" class="flex items-center justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold">{{ backup.path }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ backup.created_at }} · {{ Math.round((backup.size_bytes || 0) / 1024 / 1024 * 10) / 10 }} MB</p>
                        </div>
                        <span :class="['status-pill', backup.status === 'verified' ? 'status-success' : backup.status === 'invalid' ? 'status-danger' : 'status-warning']">{{ stateLabel(backup.status) }}</span>
                    </div>
                </div>
            </section>

            <section class="app-panel">
                <div class="panel-header"><div><h2 class="panel-title">Fallos de integración</h2><p class="panel-subtitle">Firebase reintenta automáticamente antes de aparecer aquí.</p></div></div>
                <div v-if="failures.length === 0" class="empty-state">No hay fallos de integración.</div>
                <div v-else class="max-h-[420px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                    <div v-for="failure in failures" :key="failure.id" class="p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-bold">{{ failure.integration }} · {{ failure.operation }}</p>
                            <span :class="['status-pill', failure.status === 'resolved' ? 'status-success' : 'status-danger']">{{ stateLabel(failure.status) }}</span>
                        </div>
                        <p class="mt-2 line-clamp-2 text-xs text-slate-500">{{ failure.error }}</p>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
