<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    empleados: Array,
    asistencias: Array
});

// Control de Pestañas (Tabs)
const tabActiva = ref('captura'); 
const busquedaGlobal = ref('');

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

// Filtros para la pestaña de captura
const asistenciasFiltradas = computed(() => {
    if (!form.empleado_id) {
        return props.asistencias;
    }
    return props.asistencias.filter(a => a.empleado_id === form.empleado_id);
});

// Filtro para las tablas globales (Vacaciones y Faltas)
const empleadosFiltradosGlobal = computed(() => {
    if (!busquedaGlobal.value) return props.empleados;
    return props.empleados.filter(emp => {
        const term = busquedaGlobal.value.toLowerCase();
        return emp.nombre_completo.toLowerCase().includes(term) || 
               (emp.numero_empleado && emp.numero_empleado.toLowerCase().includes(term));
    });
});

const empleadoSeleccionado = computed(() => {
    if (!form.empleado_id) return null;
    return props.empleados.find(e => e.id === form.empleado_id);
});

const formatoReloj = (horasDecimales) => {
    if (!horasDecimales) return '0 h 00 m';
    const horas = Math.floor(horasDecimales);
    const minutosDecimales = (horasDecimales - horas) * 60;
    const minutos = Math.round(minutosDecimales);
    const minutosFormateados = minutos < 10 ? '0' + minutos : minutos;
    return `${horas} h ${minutosFormateados} m`;
};

const subirArchivo = (e) => {
    formUpload.archivo_reloj = e.target.files[0];
    formUpload.post(route('asistencias.importar'), {
        preserveScroll: true,
        onSuccess: () => {
            alert('¡Reloj sincronizado y horas calculadas correctamente!');
            formUpload.reset();
            e.target.value = null;
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
    if (confirm('¿Estás seguro de eliminar este registro? Esto afectará la nómina.')) {
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
                    <p class="text-sm font-semibold text-teal-700">Registro y Control</p>
                    <h2 class="text-2xl font-semibold text-slate-950">Jornadas e Incidencias</h2>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-6">
                
                <div class="flex space-x-1 rounded-xl bg-slate-200/60 p-1.5">
                    <button @click="tabActiva = 'captura'" :class="tabActiva === 'captura' ? 'bg-white shadow text-teal-700 font-bold' : 'text-slate-600 hover:text-slate-800 hover:bg-slate-200'" class="w-full rounded-lg py-2.5 text-sm font-medium transition-all">
                        ⏱️ Captura y Reloj
                    </button>
                    <button @click="tabActiva = 'vacaciones'" :class="tabActiva === 'vacaciones' ? 'bg-white shadow text-teal-700 font-bold' : 'text-slate-600 hover:text-slate-800 hover:bg-slate-200'" class="w-full rounded-lg py-2.5 text-sm font-medium transition-all">
                        🌴 Control Vacaciones
                    </button>
                    <button @click="tabActiva = 'faltas'" :class="tabActiva === 'faltas' ? 'bg-white shadow text-rose-700 font-bold' : 'text-slate-600 hover:text-slate-800 hover:bg-slate-200'" class="w-full rounded-lg py-2.5 text-sm font-medium transition-all">
                        ⚠️ Control Faltas
                    </button>
                </div>

                <div v-show="tabActiva === 'captura'" class="space-y-8 animate-fade-in">
                    
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
                        <div class="panel-header border-b border-slate-100">
                            <div class="flex items-start gap-3">
                                <div :class="editando ? 'soft-icon-amber' : 'soft-icon-blue'">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="panel-title">{{ editando ? 'Editar registro' : 'Capturar nueva asistencia / incidencia' }}</h3>
                                    <p class="panel-subtitle">Selecciona empleado, fecha y tipo de jornada.</p>
                                </div>
                            </div>
                            <button v-if="editando" @click="cancelarEdicion" class="btn-secondary" type="button">Cancelar edición</button>
                        </div>

                        <div class="p-5 sm:p-6 bg-slate-50/50">
                            
                            <div class="mb-6">
                                <label class="field-label text-base">Selecciona un Empleado <span class="text-rose-500">*</span></label>
                                <select v-model="form.empleado_id" required :disabled="editando" class="field-input-soft text-lg font-bold text-slate-800 border-slate-300 shadow-sm focus:ring-blue-500/20 focus:border-blue-500">
                                    <option value="" disabled>Selecciona un trabajador...</option>
                                    <option v-for="emp in empleados" :key="emp.id" :value="emp.id">
                                        {{ emp.numero_empleado ? '#' + emp.numero_empleado + ' - ' : '' }}{{ emp.nombre_completo }}
                                    </option>
                                </select>
                            </div>

                            <div v-if="empleadoSeleccionado" class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm animate-fade-in">
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase">Antigüedad</p>
                                    <p class="text-lg font-bold text-slate-800">{{ empleadoSeleccionado.antiguedad_anios }} año(s)</p>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase">🌴 Vaca. Totales</p>
                                    <p class="text-lg font-bold text-teal-700">{{ empleadoSeleccionado.fecha_ingreso ? empleadoSeleccionado.dias_vacaciones_totales + ' días' : 'Falta fecha ing.' }}</p>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase">📅 Vaca. Restantes</p>
                                    <p class="text-lg font-bold" :class="empleadoSeleccionado.dias_vacaciones_restantes > 0 ? 'text-emerald-600' : 'text-slate-400'">
                                        {{ empleadoSeleccionado.fecha_ingreso ? empleadoSeleccionado.dias_vacaciones_restantes + ' disp.' : '--' }}
                                    </p>
                                </div>
                                <div class="bg-rose-50 p-3 rounded-lg border border-rose-100">
                                    <p class="text-xs font-semibold text-rose-600 uppercase">⚠️ Faltas Totales</p>
                                    <p class="text-lg font-bold text-rose-700">{{ empleadoSeleccionado.dias_faltas_totales }} faltas</p>
                                </div>
                            </div>

                            <form @submit.prevent="guardarAsistencia" class="grid grid-cols-1 items-end gap-5 md:grid-cols-4">
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

                                <div class="flex justify-end md:col-span-4 mt-2">
                                    <button type="submit" :disabled="form.processing" :class="editando ? 'btn-warning' : 'btn-accent'">
                                        <svg v-if="!form.processing" class="h-4 w-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" /></svg>
                                        {{ form.processing ? 'Procesando...' : (editando ? 'Actualizar registro' : 'Guardar asistencia') }}
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
                                Filtrando a: {{ empleados.find(e => e.id === form.empleado_id)?.nombre_completo }}
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table-premium">
    <thead>
        <tr>
            <th>Num</th>
            <th>Nombre del Trabajador</th>
            <th>Fecha de Ingreso</th>
            <th class="text-center">Años Cumplidos</th>
            <th class="text-center">Días por Ley</th>
            <th class="text-center">Días Tomados</th>
            <th class="text-center">Desc. años pasados</th>
            <th class="text-center">Días Pendientes</th>
        </tr>
    </thead>
    <tbody>
        <tr v-for="emp in empleadosFiltradosGlobal" :key="emp.id">
            <td class="whitespace-nowrap font-bold text-slate-500">{{ emp.numero_empleado || 'S/N' }}</td>
            <td class="whitespace-nowrap font-semibold text-slate-900">{{ emp.nombre_completo }}</td>
            <td class="whitespace-nowrap text-slate-600">{{ emp.fecha_ingreso || 'No registrada' }}</td>
            
            <td class="whitespace-nowrap text-center font-medium">{{ emp.antiguedad_anios }}</td>
            
            <td class="whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center bg-slate-100 text-slate-700 rounded-lg px-3 py-1 font-bold">
                    {{ emp.dias_vacaciones_totales }}
                </span>
            </td>

            <td class="whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center bg-amber-50 text-amber-700 border border-amber-200 rounded-lg px-3 py-1 font-bold">
                    {{ emp.dias_vacaciones_tomados }}
                </span>
            </td>

            <td class="whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center rounded-lg px-3 py-1 font-bold border"
                      :class="emp.ajuste_vacaciones < 0 
                          ? 'bg-rose-50 text-rose-700 border-rose-200' 
                          : 'bg-slate-50 text-slate-400 border-slate-200'">
                    {{ emp.ajuste_vacaciones < 0 ? Math.abs(emp.ajuste_vacaciones) : 0 }}
                </span>
            </td>

            <td class="whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center rounded-lg px-3 py-1 font-bold border" 
                      :class="emp.dias_vacaciones_restantes > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'">
                    {{ emp.dias_vacaciones_restantes }}
                </span>
            </td>
        </tr>

        <tr v-if="empleadosFiltradosGlobal.length === 0">
            <td colspan="8" class="empty-state">No se encontraron trabajadores.</td>
        </tr>
    </tbody>
</table>
                        </div>
                    </section>
                </div>

                <div v-show="tabActiva === 'vacaciones'" class="space-y-6 animate-fade-in">
                    <section class="app-panel">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title">Cuadro de Vacaciones</h3>
                                <p class="panel-subtitle">Control global de días correspondientes, tomados y restantes.</p>
                            </div>
                            <div class="relative w-full lg:w-96">
                                <input v-model="busquedaGlobal" type="text" class="field-input-soft pl-4" placeholder="Buscar trabajador..." />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table-premium">
    <thead>
        <tr>
            <th>Num</th>
            <th>Nombre del Trabajador</th>
            <th>Fecha de Ingreso</th>
            <th class="text-center">Años Cumplidos</th>
            <th class="text-center">Días por Ley</th>
            <th class="text-center">Días Tomados</th>
            <th class="text-center">Desc. años pasados</th>
            <th class="text-center">Días Pendientes</th>
        </tr>
    </thead>
    <tbody>
        <tr v-for="emp in empleadosFiltradosGlobal" :key="emp.id">
            <td class="whitespace-nowrap font-bold text-slate-500">{{ emp.numero_empleado || 'S/N' }}</td>
            <td class="whitespace-nowrap font-semibold text-slate-900">{{ emp.nombre_completo }}</td>
            <td class="whitespace-nowrap text-slate-600">{{ emp.fecha_ingreso || 'No registrada' }}</td>
            
            <td class="whitespace-nowrap text-center font-medium">{{ emp.antiguedad_anios }}</td>
            
            <td class="whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center bg-slate-100 text-slate-700 rounded-lg px-3 py-1 font-bold">
                    {{ emp.dias_vacaciones_totales }}
                </span>
            </td>

            <td class="whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center bg-amber-50 text-amber-700 border border-amber-200 rounded-lg px-3 py-1 font-bold">
                    {{ emp.dias_vacaciones_tomados }}
                </span>
            </td>

            <td class="whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center rounded-lg px-3 py-1 font-bold border"
                      :class="emp.ajuste_vacaciones < 0 
                          ? 'bg-rose-50 text-rose-700 border-rose-200' 
                          : 'bg-slate-50 text-slate-400 border-slate-200'">
                    {{ emp.ajuste_vacaciones < 0 ? Math.abs(emp.ajuste_vacaciones) : 0 }}
                </span>
            </td>

            <td class="whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center rounded-lg px-3 py-1 font-bold border" 
                      :class="emp.dias_vacaciones_restantes > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'">
                    {{ emp.dias_vacaciones_restantes }}
                </span>
            </td>
        </tr>

        <tr v-if="empleadosFiltradosGlobal.length === 0">
            <td colspan="8" class="empty-state">No se encontraron trabajadores.</td>
        </tr>
    </tbody>
</table>
                        </div>
                    </section>
                </div>

                <div v-show="tabActiva === 'faltas'" class="space-y-6 animate-fade-in">
                    <section class="app-panel">
                        <div class="panel-header border-b-rose-100">
                            <div>
                                <h3 class="panel-title text-rose-900">Control de Faltas Injustificadas</h3>
                                <p class="panel-subtitle">Acumulado anual de ausencias por empleado.</p>
                            </div>
                            <div class="relative w-full lg:w-96">
                                <input v-model="busquedaGlobal" type="text" class="field-input-soft pl-4 focus:ring-rose-500/20 focus:border-rose-400" placeholder="Buscar trabajador..." />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table-premium">
                                <thead class="bg-rose-50/50">
                                    <tr>
                                        <th>Num</th>
                                        <th>Nombre del Trabajador</th>
                                        <th class="text-center">Estatus</th>
                                        <th class="text-right text-rose-700">Faltas Totales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="emp in empleadosFiltradosGlobal" :key="emp.id" class="hover:bg-rose-50/30">
                                        <td class="whitespace-nowrap font-bold text-slate-500">{{ emp.numero_empleado || 'S/N' }}</td>
                                        <td class="whitespace-nowrap font-semibold text-slate-900">{{ emp.nombre_completo }}</td>
                                        
                                        <td class="whitespace-nowrap text-center">
                                            <span v-if="emp.dias_faltas_totales === 0" class="status-pill status-success">Asistencia Perfecta</span>
                                            <span v-else-if="emp.dias_faltas_totales < 3" class="status-pill status-warning">Alerta Mínima</span>
                                            <span v-else class="status-pill status-danger">Problema de Ausentismo</span>
                                        </td>

                                        <td class="whitespace-nowrap text-right">
                                            <span class="inline-flex items-center justify-center text-lg font-black"
                                                  :class="emp.dias_faltas_totales > 0 ? 'text-rose-600' : 'text-slate-300'">
                                                {{ emp.dias_faltas_totales }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="empleadosFiltradosGlobal.length === 0">
                                        <td colspan="4" class="empty-state">No se encontraron trabajadores.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>