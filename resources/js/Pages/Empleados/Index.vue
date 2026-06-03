<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    empleados: Array
});

const editando = ref(false);
const empleadoId = ref(null);
const searchQuery = ref(''); 

const form = useForm({
    numero_empleado: '',
    nombre_completo: '',
    puesto: '',
    sueldo_por_hora: '',
    banco: '', // <-- Nuevo campo
    numero_cuenta: '',
    nss: '',
    rfc: ''
});

const empleadosFiltrados = computed(() => {
    if (!searchQuery.value) return props.empleados;
    return props.empleados.filter(emp => {
        const query = searchQuery.value.toLowerCase();
        const nombreMatch = emp.nombre_completo.toLowerCase().includes(query);
        const numeroMatch = emp.numero_empleado && emp.numero_empleado.toLowerCase().includes(query);
        return nombreMatch || numeroMatch;
    });
});

const submitForm = () => {
    if (editando.value) {
        form.put(route('empleados.update', empleadoId.value), {
            onSuccess: () => cancelarEdicion()
        });
    } else {
        form.post(route('empleados.store'), {
            onSuccess: () => form.reset()
        });
    }
};

const editarEmpleado = (empleado) => {
    editando.value = true;
    empleadoId.value = empleado.id;
    form.numero_empleado = empleado.numero_empleado || '';
    form.nombre_completo = empleado.nombre_completo;
    form.puesto = empleado.puesto || '';
    form.sueldo_por_hora = empleado.sueldo_por_hora;
    form.banco = empleado.banco || ''; // <-- Cargamos el banco
    form.numero_cuenta = empleado.numero_cuenta || '';
    form.nss = empleado.nss || '';
    form.rfc = empleado.rfc || '';
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelarEdicion = () => {
    editando.value = false;
    empleadoId.value = null;
    form.reset();
};

const eliminarEmpleado = (id, nombre) => {
    if (confirm(`¿Estás seguro de eliminar a ${nombre}?`)) {
        router.delete(route('empleados.destroy', id));
    }
};
</script>

<template>
    <Head title="Control de Empleados" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">Directorio de Personal</h2>
            </div>
        </template>

        <div class="py-10 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Panel de Registro / Edición -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300" :class="editando ? 'ring-2 ring-orange-400' : ''">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <div class="p-2 rounded-lg" :class="editando ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800">
                                {{ editando ? 'Actualizar Expediente' : 'Alta de Nuevo Trabajador' }}
                            </h3>
                        </div>
                        <button v-if="editando" @click="cancelarEdicion" class="text-sm font-medium text-red-500 hover:text-red-700">
                            Cancelar Edición
                        </button>
                    </div>
                    
                    <div class="p-6">
                        <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            
                            <!-- Fila 1 -->
                            <div class="md:col-span-1">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">No. Empleado</label>
                                <input v-model="form.numero_empleado" type="text" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" placeholder="Ej. 84" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                                <input v-model="form.nombre_completo" type="text" required class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" />
                            </div>

                            <div class="md:col-span-1">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Puesto</label>
                                <input v-model="form.puesto" type="text" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" />
                            </div>

                            <!-- Fila 2 (Sueldo y Banco) -->
                            <div class="md:col-span-1">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Sueldo / Hora ($) <span class="text-red-500">*</span></label>
                                <input v-model="form.sueldo_por_hora" type="number" step="0.01" required class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" />
                            </div>

                            <div class="md:col-span-1">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Banco</label>
                                <input v-model="form.banco" type="text" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" placeholder="Ej. BBVA, Banamex..." />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Cuenta Bancaria o CLABE</label>
                                <input v-model="form.numero_cuenta" type="text" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" placeholder="18 dígitos o Tarjeta" />
                            </div>

                            <!-- Fila 3 -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">NSS</label>
                                <input v-model="form.nss" type="text" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">RFC</label>
                                <input v-model="form.rfc" type="text" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors" />
                            </div>

                            <!-- Botón -->
                            <div class="md:col-span-4 flex justify-end mt-2">
                                <button type="submit" :disabled="form.processing" 
                                        :class="editando ? 'bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 focus:ring-orange-500' : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:ring-blue-500'" 
                                        class="inline-flex items-center gap-2 px-6 py-2.5 text-white text-sm font-bold rounded-xl shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all">
                                    <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                    {{ form.processing ? 'Guardando...' : (editando ? 'Actualizar Expediente' : 'Registrar Empleado') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Personal -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h3 class="text-lg font-bold text-gray-800">Directorio Activo</h3>
                        
                        <div class="relative w-full md:w-96">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input v-model="searchQuery" type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500 transition-colors sm:text-sm" placeholder="Buscar por nombre o número..." />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Empleado</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Puesto / Cuenta Bancaria</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tarifa (Hr)</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="empleado in empleadosFiltrados" :key="empleado.id" class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center text-blue-700 font-bold border border-blue-200">
                                                {{ empleado.numero_empleado || 'S/N' }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900">{{ empleado.nombre_completo }}</div>
                                                <div class="text-xs text-gray-500">ID Sistema: #{{ empleado.id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">{{ empleado.puesto || 'No asignado' }}</div>
                                        <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                            <!-- Aquí se imprime Banco y Cuenta -->
                                            {{ empleado.banco ? empleado.banco + ' - ' : '' }}{{ empleado.numero_cuenta || 'Sin cuenta registrada' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-bold rounded-full bg-green-100 text-green-800 border border-green-200">
                                            ${{ empleado.sueldo_por_hora }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="editarEmpleado(empleado)" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors" title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                            <button @click="eliminarEmpleado(empleado.id, empleado.nombre_completo)" class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="Eliminar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="empleadosFiltrados.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                        <p class="mt-4 text-sm text-gray-500 font-medium">No se encontraron empleados con esa búsqueda.</p>
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