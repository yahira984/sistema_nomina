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

// NUEVA FUNCIÓN: Convierte "8.50" decimal a "8 h 30 m"
const formatoReloj = (horasDecimales) => {
    if (!horasDecimales) return '0 h 00 m';
    
    // Sacamos las horas enteras (el 8)
    const horas = Math.floor(horasDecimales);
    
    // Sacamos los decimales (el 0.50) y los multiplicamos por 60 minutos
    const minutosDecimales = (horasDecimales - horas) * 60;
    
    // Redondeamos los minutos para que no salgan cosas raras
    const minutos = Math.round(minutosDecimales);
    
    // Le ponemos un 0 a la izquierda si los minutos son menores a 10 (ej. "05")
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
                <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">Control de Asistencias</h2>
            </div>
        </template>

        <div class="py-10 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300" :class="editando ? 'ring-2 ring-orange-400' : ''">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <div class="p-2 rounded-lg" :class="editando ? 'bg-orange-100 text-orange-600' : 'bg-emerald-100 text-emerald-600'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800">
                                {{ editando ? 'Editando Registro de Horas' : 'Capturar Nueva Checada' }}
                            </h3>
                        </div>
                        <button v-if="editando" @click="cancelarEdicion" class="text-sm font-medium text-red-500 hover:text-red-700">
                            Cancelar Edición
                        </button>
                    </div>

                    <div class="p-6">
                        <form @submit.prevent="guardarAsistencia" class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                            
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Empleado <span class="text-red-500">*</span></label>
                                <select v-model="form.empleado_id" required :disabled="editando" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                    <option value="" disabled>Selecciona un trabajador...</option>
                                    <option v-for="emp in empleados" :key="emp.id" :value="emp.id">
                                        {{ emp.numero_empleado ? '#' + emp.numero_empleado + ' - ' : '' }}{{ emp.nombre_completo }}
                                    </option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha <span class="text-red-500">*</span></label>
                                <input v-model="form.fecha" type="date" required class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Entrada <span class="text-red-500">*</span></label>
                                <input v-model="form.hora_entrada" type="time" required class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Salida <span class="text-red-500">*</span></label>
                                <input v-model="form.hora_salida" type="time" required class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" />
                            </div>

                            <div class="md:col-span-5 flex justify-end mt-2">
                                <button type="submit" :disabled="form.processing" 
                                        :class="editando ? 'bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 focus:ring-orange-500' : 'bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 focus:ring-emerald-500'"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 text-white text-sm font-bold rounded-xl shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all w-full md:w-auto justify-center">
                                    <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    {{ form.processing ? 'Procesando...' : (editando ? 'Actualizar Horas' : 'Guardar Asistencia') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h3 class="text-lg font-bold text-gray-800">Últimos Registros</h3>
                        
                        <div v-if="form.empleado_id" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-100">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            <span class="text-sm font-bold text-blue-800">
                                Filtrando: {{ empleados.find(e => e.id === form.empleado_id)?.nombre_completo }}
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Empleado</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Horario</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jornada</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="asistencia in asistenciasFiltradas" :key="asistencia.id" class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ asistencia.fecha }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-800">{{ asistencia.empleado.nombre_completo }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ asistencia.hora_entrada }} - {{ asistencia.hora_salida }}
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col items-start">
                                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-bold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                                {{ formatoReloj(asistencia.horas_trabajadas) }}
                                            </span>
                                            <span class="text-[10px] text-gray-400 mt-1 ml-1 font-medium">Decimal: {{ asistencia.horas_trabajadas }}</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="editarAsistencia(asistencia)" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors" title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                            <button @click="eliminarAsistencia(asistencia.id)" class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="Borrar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="asistenciasFiltradas.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <p class="mt-4 text-sm text-gray-500 font-medium">No hay registros de asistencia para mostrar.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>