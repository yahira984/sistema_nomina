<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    conexion: String,
    baseDatos: String,
    tablas: {
        type: Array,
        default: () => [],
    },
    totalTablas: Number,
    backups: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const archivoInput = ref(null);
const creandoRespaldo = ref(false);
const verificandoRespaldo = ref(null);

const mensajeExito = computed(() => page.props.flash?.success);

const form = useForm({
    archivo_sql: null,
    confirmacion: '',
});

const seleccionarArchivo = (event) => {
    form.archivo_sql = event.target.files[0] || null;
};

const importar = () => {
    form.post(route('base-datos.importar'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (archivoInput.value) {
                archivoInput.value.value = null;
            }
        },
    });
};

const crearRespaldoAutomatico = () => {
    creandoRespaldo.value = true;
    router.post(route('base-datos.respaldos.store'), {}, {
        preserveScroll: true,
        onFinish: () => { creandoRespaldo.value = false; },
    });
};

const verificarRespaldo = (backup) => {
    verificandoRespaldo.value = backup.id;
    router.post(route('base-datos.respaldos.verify', backup.id), {}, {
        preserveScroll: true,
        onFinish: () => { verificandoRespaldo.value = null; },
    });
};
</script>

<template>
    <Head title="Base de Datos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <Link :href="route('dashboard')" class="icon-button" aria-label="Volver al panel">
                    <i class="ti ti-arrow-left" aria-hidden="true"></i>
                </Link>
                <div class="min-w-0">
                    <p class="page-kicker">
                        <i class="ti ti-database" aria-hidden="true"></i>
                        Sistema
                    </p>
                    <h2 class="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">Respaldo de Base de Datos</h2>
                    <p class="mt-1 text-sm text-slate-500">Protege la base, fotografías y expedientes, o restaura respaldos de versiones anteriores.</p>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-6">
                <div v-if="mensajeExito" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700">
                    <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                    {{ mensajeExito }}
                </div>

                <section class="grid gap-6 lg:grid-cols-3">
                    <div class="metric-card">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="metric-label">Conexion</p>
                                <p class="metric-value break-words text-2xl">{{ conexion }}</p>
                            </div>
                            <div class="soft-icon-blue">
                                <i class="ti ti-plug-connected text-xl" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="metric-label">Base activa</p>
                                <p class="metric-value break-words text-2xl">{{ baseDatos }}</p>
                            </div>
                            <div class="soft-icon-teal">
                                <i class="ti ti-database text-xl" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="metric-label">Tablas</p>
                                <p class="metric-value text-2xl">{{ totalTablas }}</p>
                            </div>
                            <div class="soft-icon-amber">
                                <i class="ti ti-table text-xl" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 lg:grid-cols-2">
                    <div class="app-panel">
                        <div class="panel-header">
                            <div class="flex items-start gap-3">
                                <div class="soft-icon-emerald">
                                    <i class="ti ti-download text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h3 class="panel-title">Exportar respaldo</h3>
                                    <p class="panel-subtitle">Descarga un SQL ligero con tablas, estructura y datos.</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                                Guarda este archivo en una carpeta segura. Te sirve para migrar la app a otra computadora o recuperar informacion.
                            </div>

                            <a :href="route('base-datos.exportar')" class="btn-accent w-full">
                                <i class="ti ti-database-export" aria-hidden="true"></i>
                                Descargar respaldo .sql
                            </a>
                        </div>
                    </div>

                    <div class="app-panel">
                        <div class="panel-header border-b-rose-100">
                            <div class="flex items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700">
                                    <i class="ti ti-upload text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h3 class="panel-title text-rose-900">Importar respaldo</h3>
                                    <p class="panel-subtitle">Acepta paquetes integrales ZIP y respaldos SQL anteriores.</p>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="importar" class="space-y-5 p-5 sm:p-6">
                            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">
                                Importar reemplaza la informacion actual por la del respaldo. Haz una exportacion antes de restaurar.
                            </div>

                            <div>
                                <label class="field-label">Archivo de respaldo</label>
                                <input
                                    ref="archivoInput"
                                    type="file"
                                    accept=".zip,.sql,.txt"
                                    class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-white hover:file:bg-slate-800"
                                    @change="seleccionarArchivo"
                                />
                                <p v-if="form.errors.archivo_sql" class="mt-2 text-sm font-semibold text-rose-600">{{ form.errors.archivo_sql }}</p>
                            </div>

                            <div>
                                <label class="field-label">Confirmacion</label>
                                <input v-model="form.confirmacion" type="text" class="field-input-soft" placeholder="Escribe RESTAURAR" />
                                <p v-if="form.errors.confirmacion" class="mt-2 text-sm font-semibold text-rose-600">{{ form.errors.confirmacion }}</p>
                            </div>

                            <button type="submit" :disabled="form.processing" class="btn-danger w-full">
                                <i class="ti ti-database-import" aria-hidden="true"></i>
                                {{ form.processing ? 'Restaurando...' : 'Restaurar base de datos' }}
                            </button>
                        </form>
                    </div>
                </section>

                <section class="app-panel">
                    <div class="panel-header">
                        <div class="flex items-start gap-3">
                            <div class="soft-icon-emerald"><i class="ti ti-shield-check text-xl" aria-hidden="true"></i></div>
                            <div>
                                <h3 class="panel-title">Respaldos automáticos</h3>
                                <p class="panel-subtitle">Cada ZIP incluye base de datos, documentos privados y fotografías.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-accent" :disabled="creandoRespaldo" @click="crearRespaldoAutomatico">
                            <i class="ti ti-database-plus" aria-hidden="true"></i>
                            {{ creandoRespaldo ? 'Creando...' : 'Crear respaldo ahora' }}
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Archivo</th><th>Fecha</th><th>Tamaño</th><th>Estado</th><th>Verificación</th><th class="text-right">Acción</th></tr></thead>
                            <tbody>
                                <tr v-for="backup in backups" :key="backup.id">
                                    <td class="font-bold text-slate-900">{{ backup.file_name }}</td>
                                    <td>{{ new Date(backup.created_at).toLocaleString('es-MX') }}</td>
                                    <td>{{ (Number(backup.size_bytes || 0) / 1024 / 1024).toFixed(2) }} MB</td>
                                    <td><span :class="backup.status === 'verified' ? 'status-success' : (backup.status === 'failed' ? 'status-danger' : 'status-warning')">{{ backup.status }}</span></td>
                                    <td>{{ backup.verification_message || 'Pendiente' }}</td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                        <a :href="route('base-datos.respaldos.download', backup.id)" class="btn-secondary" title="Descargar respaldo integral">
                                            <i class="ti ti-download" aria-hidden="true"></i>
                                            Descargar
                                        </a>
                                        <button type="button" class="btn-secondary" :disabled="verificandoRespaldo === backup.id" @click="verificarRespaldo(backup)">
                                            <i class="ti ti-file-check" aria-hidden="true"></i>
                                            {{ verificandoRespaldo === backup.id ? 'Verificando...' : 'Verificar' }}
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="backups.length === 0"><td colspan="6" class="empty-state">Todavía no hay respaldos automáticos registrados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="app-panel">
                    <div class="panel-header">
                        <div class="flex items-start gap-3">
                            <div class="soft-icon">
                                <i class="ti ti-list-details text-xl" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h3 class="panel-title">Tablas incluidas</h3>
                                <p class="panel-subtitle">El respaldo incluye estas tablas de la base activa.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-3">
                        <div v-for="tabla in tablas" :key="tabla" class="info-row">
                            <div class="info-row-icon">
                                <i class="ti ti-table" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ tabla }}</p>
                                <p class="text-xs font-medium text-slate-500">Estructura y registros</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
