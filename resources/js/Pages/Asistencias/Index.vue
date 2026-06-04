<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    empleados: Array,
    asistencias: Array
});

const editando = ref(false);
const asistenciaId = ref(null);

const form = useForm({
    empleado_id: '',
    fecha: new Date().toISOString().split('T')[0],
    hora_entrada: '08:00',
    hora_salida: '17:00'
});

const asistenciasFiltradas = computed(() => {
    if (!form.empleado_id) {
        return props.asistencias;
    }
    return props.asistencias.filter(a => a.empleado_id === form.empleado_id);
});

const formatoReloj = (horasDecimales) => {
    if (!horasDecimales) return '0 h 00 m';

    const horas = Math.floor(horasDecimales);
    const minutosDecimales = (horasDecimales - horas) * 60;
    const minutos = Math.round(minutosDecimales);
    const minutosFormateados = minutos < 10 ? '0' + minutos : minutos;

    return `${horas} h ${minutosFormateados} m`;
};

const guardarAsistencia = () => {
    if (editando.value) {
        form.put(route('asistencias.update', asistenciaId.value), {
            onSuccess: () => cancelarEdicion()
        });
    } else {
        form.post(route('asistencias.store'), {
            onSuccess: () => {
                form.reset('hora_entrada', 'hora_salida', 'fecha');
            }
        });
    }
};

const editarAsistencia = (asistencia) => {
    editando.value = true;
    asistenciaId.value = asistencia.id;
    form.empleado_id = asistencia.empleado_id;
    form.fecha = asistencia.fecha;
    form.hora_entrada = asistencia.hora_entrada.substring(0, 5);
    form.hora_salida = asistencia.hora_salida.substring(0, 5);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelarEdicion = () => {
    editando.value = false;
    asistenciaId.value = null;
    form.reset('hora_entrada', 'hora_salida');
};

const eliminarAsistencia = (id) => {
    if (confirm('¿Estás seguro de eliminar este registro de horas? Esto afectará la nómina.')) {
        router.delete(route('asistencias.destroy', id));
    }
};
</script>

<template>
    <Head title="Control de Asistencias" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('dashboard')" class="icon-button" aria-label="Volver al panel">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19 3 12m0 0 7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <p class="text-sm font-semibold text-teal-700">Registro de jornada</p>
                    <h2 class="text-2xl font-semibold text-slate-950">Control de Asistencias</h2>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-8">
                <section class="app-panel" :class="editando ? 'ring-2 ring-amber-400/70' : ''">
                    <div class="panel-header">
                        <div class="flex items-start gap-3">
                            <div :class="editando ? 'soft-icon-amber' : 'soft-icon-emerald'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="panel-title">{{ editando ? 'Editar registro de horas' : 'Capturar nueva asistencia' }}</h3>
                                <p class="panel-subtitle">Selecciona empleado, fecha y horario trabajado.</p>
                            </div>
                        </div>

                        <button v-if="editando" @click="cancelarEdicion" class="btn-secondary" type="button">
                            Cancelar edición
                        </button>
                    </div>

                    <div class="p-5 sm:p-6">
                        <form @submit.prevent="guardarAsistencia" class="grid grid-cols-1 items-end gap-5 md:grid-cols-5">
                            <div class="md:col-span-2">
                                <label class="field-label">Empleado <span class="text-rose-500">*</span></label>
                                <select v-model="form.empleado_id" required :disabled="editando" class="field-input-soft">
                                    <option value="" disabled>Selecciona un trabajador...</option>
                                    <option v-for="emp in empleados" :key="emp.id" :value="emp.id">
                                        {{ emp.numero_empleado ? '#' + emp.numero_empleado + ' - ' : '' }}{{ emp.nombre_completo }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="field-label">Fecha <span class="text-rose-500">*</span></label>
                                <input v-model="form.fecha" type="date" required class="field-input-soft" />
                            </div>

                            <div>
                                <label class="field-label">Entrada <span class="text-rose-500">*</span></label>
                                <input v-model="form.hora_entrada" type="time" required class="field-input-soft" />
                            </div>

                            <div>
                                <label class="field-label">Salida <span class="text-rose-500">*</span></label>
                                <input v-model="form.hora_salida" type="time" required class="field-input-soft" />
                            </div>

                            <div class="flex justify-end md:col-span-5">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    :class="editando ? 'btn-warning' : 'btn-accent'"
                                >
                                    <svg v-if="!form.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
                                    </svg>
                                    {{ form.processing ? 'Procesando...' : (editando ? 'Actualizar horas' : 'Guardar asistencia') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="app-panel">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Últimos registros</h3>
                            <p class="panel-subtitle">{{ asistenciasFiltradas.length }} asistencia(s) visibles</p>
                        </div>

                        <div v-if="form.empleado_id" class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v2.586a1 1 0 0 1-.293.707l-6.414 6.414a1 1 0 0 0-.293.707V17l-4 4v-6.586a1 1 0 0 0-.293-.707L3.293 7.293A1 1 0 0 1 3 6.586V4Z" />
                            </svg>
                            Filtrando: {{ empleados.find(e => e.id === form.empleado_id)?.nombre_completo }}
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Empleado</th>
                                    <th>Horario</th>
                                    <th>Jornada</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="asistencia in asistenciasFiltradas" :key="asistencia.id">
                                    <td class="whitespace-nowrap font-semibold text-slate-950">{{ asistencia.fecha }}</td>
                                    <td class="whitespace-nowrap font-semibold text-slate-900">{{ asistencia.empleado.nombre_completo }}</td>
                                    <td class="whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
                                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            {{ asistencia.hora_entrada }} - {{ asistencia.hora_salida }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex flex-col items-start">
                                            <span class="status-pill status-info">
                                                {{ formatoReloj(asistencia.horas_trabajadas) }}
                                            </span>
                                            <span class="mt-1 text-xs font-medium text-slate-400">Decimal: {{ asistencia.horas_trabajadas }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="editarAsistencia(asistencia)" class="icon-button" title="Editar" type="button">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732Z" />
                                                </svg>
                                            </button>
                                            <button @click="eliminarAsistencia(asistencia.id)" class="icon-button-danger" title="Borrar" type="button">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 7-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="asistenciasFiltradas.length === 0">
                                    <td colspan="5" class="empty-state">
                                        No hay registros de asistencia para mostrar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
