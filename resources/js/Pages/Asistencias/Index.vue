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
    tipo_asistencia: 'Normal', 
    hora_entrada: '08:00',
    hora_salida: '17:00'
});

const formUpload = useForm({
    archivo_reloj: null,
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

// Subida de Archivo CSV
const subirArchivo = (e) => {
    formUpload.archivo_reloj = e.target.files[0];
    formUpload.post(route('asistencias.importar'), {
        preserveScroll: true,
        onSuccess: () => {
            alert('¡Reloj sincronizado y horas calculadas correctamente!');
            formUpload.reset();
            e.target.value = null; // Limpiar el campo
        }
    });
};

const guardarAsistencia = () => {
    if (editando.value) {
        form.put(route('asistencias.update', asistenciaId.value), {
            onSuccess: () => cancelarEdicion()
        });
    } else {
        form.post(route('asistencias.store'), {
            onSuccess: () => {
                form.reset('hora_entrada', 'hora_salida', 'fecha', 'tipo_asistencia');
            }
        });
    }
};

const editarAsistencia = (asistencia) => {
    editando.value = true;
    asistenciaId.value = asistencia.id;
    form.empleado_id = asistencia.empleado_id;
    form.fecha = asistencia.fecha;
    form.tipo_asistencia = asistencia.tipo_asistencia || 'Normal';
    form.hora_entrada = asistencia.hora_entrada ? asistencia.hora_entrada.substring(0, 5) : '08:00';
    form.hora_salida = asistencia.hora_salida ? asistencia.hora_salida.substring(0, 5) : '17:00';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelarEdicion = () => {
    editando.value = false;
    asistenciaId.value = null;
    form.reset('hora_entrada', 'hora_salida', 'tipo_asistencia');
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
                
                <section class="app-panel border border-emerald-200 bg-emerald-50/50">
                    <div class="p-5 sm:p-6 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div>
                            <h3 class="font-bold text-emerald-800 text-lg mb-1 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Importar desde Reloj Biométrico
                            </h3>
                            <p class="text-sm text-emerald-700">Sube el archivo CSV del reloj. El sistema agrupará y calculará automáticamente las horas.</p>
                        </div>
                        <div class="w-full md:w-auto">
                            <input 
                                type="file" 
                                accept=".csv"
                                @change="subirArchivo" 
                                :disabled="formUpload.processing"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer"
                            />
                            <progress v-if="formUpload.progress" :value="formUpload.progress.percentage" max="100" class="mt-2 w-full"></progress>
                        </div>
                    </div>
                </section>

                <section class="app-panel" :class="editando ? 'ring-2 ring-amber-400/70' : ''">
                    <div class="panel-header">
                        <div class="flex items-start gap-3">
                            <div :class="editando ? 'soft-icon-amber' : 'soft-icon-blue'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="panel-title">{{ editando ? 'Editar registro' : 'Capturar nueva asistencia' }}</h3>
                                <p class="panel-subtitle">Selecciona empleado, fecha y tipo de jornada.</p>
                            </div>
                        </div>
                        <button v-if="editando" @click="cancelarEdicion" class="btn-secondary" type="button">
                            Cancelar edición
                        </button>
                    </div>

                    <div class="p-5 sm:p-6">
                        <form @submit.prevent="guardarAsistencia" class="grid grid-cols-1 items-end gap-5 md:grid-cols-6">
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
                                <label class="field-label">Tipo <span class="text-rose-500">*</span></label>
                                <select v-model="form.tipo_asistencia" required class="field-input-soft">
                                    <option value="Normal">Normal (Asistencia)</option>
                                    <option value="Falta">Falta Injustificada</option>
                                    <option value="Incapacidad">Incapacidad (60%)</option>
                                    <option value="Vacaciones">Vacaciones (+25% Prima)</option>
                                </select>
                            </div>

                            <div>
                                <label class="field-label" :class="form.tipo_asistencia !== 'Normal' ? 'text-slate-400' : ''">Entrada</label>
                                <input v-model="form.hora_entrada" type="time" :required="form.tipo_asistencia === 'Normal'" :disabled="form.tipo_asistencia !== 'Normal'" class="field-input-soft" :class="form.tipo_asistencia !== 'Normal' ? 'opacity-50 cursor-not-allowed' : ''" />
                            </div>

                            <div>
                                <label class="field-label" :class="form.tipo_asistencia !== 'Normal' ? 'text-slate-400' : ''">Salida</label>
                                <input v-model="form.hora_salida" type="time" :required="form.tipo_asistencia === 'Normal'" :disabled="form.tipo_asistencia !== 'Normal'" class="field-input-soft" :class="form.tipo_asistencia !== 'Normal' ? 'opacity-50 cursor-not-allowed' : ''" />
                            </div>

                            <div class="flex justify-end md:col-span-6 mt-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    :class="editando ? 'btn-warning' : 'btn-accent'"
                                >
                                    <svg v-if="!form.processing" class="h-4 w-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <th>Tipo</th>
                                    <th>Horario</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="asistencia in asistenciasFiltradas" :key="asistencia.id">
                                    <td class="whitespace-nowrap font-semibold text-slate-950">{{ asistencia.fecha }}</td>
                                    <td class="whitespace-nowrap font-semibold text-slate-900">{{ asistencia.empleado.nombre_completo }}</td>
                                    
                                    <td class="whitespace-nowrap">
                                        <span :class="{
                                            'bg-emerald-100 text-emerald-800 border-emerald-200': asistencia.tipo_asistencia === 'Normal',
                                            'bg-rose-100 text-rose-800 border-rose-200': asistencia.tipo_asistencia === 'Falta',
                                            'bg-amber-100 text-amber-800 border-amber-200': asistencia.tipo_asistencia === 'Incapacidad'
                                        }" class="px-2.5 py-1 rounded-md text-xs font-bold border">
                                            {{ asistencia.tipo_asistencia || 'Normal' }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap">
                                        <div v-if="asistencia.tipo_asistencia === 'Normal'">
                                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
                                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                {{ asistencia.hora_entrada ? asistencia.hora_entrada.substring(0,5) : '--' }} - {{ asistencia.hora_salida ? asistencia.hora_salida.substring(0,5) : '--' }}
                                            </span>
                                            <div class="mt-1 text-xs font-medium text-slate-500">
                                                {{ formatoReloj(asistencia.horas_trabajadas) }} trabajadas
                                                <span v-if="asistencia.horas_extra > 0" class="text-amber-600 font-bold ml-2">(+{{ formatoReloj(asistencia.horas_extra) }} extras)</span>
                                            </div>
                                        </div>
                                        <div v-else class="text-sm font-medium text-slate-400 italic">
                                            No aplica
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