<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    empleados: Array,
    historial: Array,
    semanaActual: Number,
    semanasDisponibles: Array,
    fechaCorteActual: String
});

const searchQuery = ref('');
const showToast = ref(false);
const toastTitle = ref('');
const toastMessage = ref('');

// Variable enlazada al menú desplegable de semanas
const selectedCorte = ref(props.fechaCorteActual);

// MAGIA: Si la contadora cambia de semana, recargamos la info silenciosamente
watch(selectedCorte, (newDate) => {
    router.get(route('nominas.index'), { fecha_corte: newDate }, { preserveState: true, preserveScroll: true });
});

const empleadosFiltrados = computed(() => {
    if (!searchQuery.value) return props.empleados;
    return props.empleados.filter(emp => {
        const query = searchQuery.value.toLowerCase();
        return emp.nombre_completo.toLowerCase().includes(query) || 
               (emp.numero_empleado && emp.numero_empleado.toLowerCase().includes(query));
    });
});

const copiarCuenta = (banco, cuenta) => {
    if (!cuenta) return;
    const texto = `${banco ? banco + ' - ' : ''}${cuenta}`;
    navigator.clipboard.writeText(cuenta).then(() => {
        toastTitle.value = '¡Cuenta Copiada!';
        toastMessage.value = texto;
        showToast.value = true;
        setTimeout(() => { showToast.value = false; }, 3500);
    });
};

const marcarComoGenerado = (empleado) => {
    empleado.nomina_generada = true;
    setTimeout(() => {
        router.reload({ preserveScroll: true });
    }, 1500);
};

const cambiarEstadoPago = (nominaId) => {
    if(!nominaId) return;
    router.put(route('nominas.pagar', nominaId), {}, {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Control de Nóminas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">Control y Pago de Nóminas</h2>
            </div>
        </template>

        <div class="py-10 bg-gray-50 min-h-screen relative">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- SECCIÓN 1: CÁLCULO DE LA SEMANA -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-indigo-100 text-indigo-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Periodo a Procesar</h3>
                                <p class="text-sm text-gray-500">Selecciona la semana que quieres consultar o pagar.</p>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row gap-4 w-full lg:w-auto">
                            <!-- MENÚ DESPLEGABLE DE SEMANAS (LA MÁQUINA DEL TIEMPO) -->
                            <div class="relative w-full md:w-64">
                                <select v-model="selectedCorte" class="block w-full pl-4 pr-10 py-2.5 border-2 border-indigo-100 rounded-xl leading-5 bg-indigo-50/50 text-indigo-800 font-bold hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors sm:text-sm appearance-none cursor-pointer">
                                    <option v-for="sem in semanasDisponibles" :key="sem.fecha_corte" :value="sem.fecha_corte">
                                        {{ sem.etiqueta }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-indigo-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>

                            <!-- Buscador Premium -->
                            <div class="relative w-full md:w-64">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input v-model="searchQuery" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-colors sm:text-sm" placeholder="Buscar empleado..." />
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Empleado</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Datos de Depósito</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estatus del Dinero</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Recibo PDF</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="empleado in empleadosFiltrados" :key="empleado.id" class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full flex items-center justify-center text-indigo-700 font-bold border border-indigo-200">
                                                {{ empleado.numero_empleado || 'S/N' }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900">{{ empleado.nombre_completo }}</div>
                                                <div class="text-xs text-gray-500">{{ empleado.puesto || 'Sin puesto asignado' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="empleado.numero_cuenta" class="flex flex-col items-start">
                                            <span class="text-xs font-bold text-gray-500 uppercase">{{ empleado.banco || 'Banco No Especificado' }}</span>
                                            <button @click="copiarCuenta(empleado.banco, empleado.numero_cuenta)" 
                                                    class="mt-1 flex items-center gap-1.5 px-3 py-1 bg-gray-100 hover:bg-indigo-50 text-gray-700 hover:text-indigo-700 rounded-lg transition-colors border border-transparent hover:border-indigo-200"
                                                    title="Copiar cuenta">
                                                <span class="font-mono text-sm font-semibold tracking-wider">{{ empleado.numero_cuenta }}</span>
                                                <svg class="w-4 h-4 opacity-50 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                            </button>
                                        </div>
                                        <div v-else class="text-sm text-red-500 font-medium italic flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            Sin cuenta
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div v-if="!empleado.nomina_generada" class="text-xs text-gray-400 font-medium italic">
                                            Genera el recibo primero
                                        </div>
                                        <div v-else class="flex flex-col items-center justify-center gap-1">
                                            <span :class="empleado.pagado ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200'" 
                                                  class="px-3 py-1 inline-flex text-xs font-bold rounded-full border">
                                                {{ empleado.pagado ? 'Liquidado' : 'Pendiente' }}
                                            </span>
                                            <button @click="cambiarEstadoPago(empleado.nomina_id)" 
                                                    class="text-[10px] font-bold uppercase tracking-wider text-gray-400 hover:text-indigo-600 transition-colors mt-1 underline decoration-gray-300 hover:decoration-indigo-600">
                                                {{ empleado.pagado ? 'Marcar como Pendiente' : 'Marcar como Pagado' }}
                                            </button>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <!-- AQUÍ MANDAMOS LA SEMANA SELECCIONADA EN LA URL -->
                                        <a :href="route('nominas.generar', { empleado_id: empleado.id, fecha_corte: selectedCorte })" target="_blank" @click="marcarComoGenerado(empleado)"
                                           :class="empleado.nomina_generada ? 'bg-gradient-to-r from-orange-400 to-orange-500 hover:from-orange-500 hover:to-orange-600 shadow-orange-200' : 'bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 shadow-indigo-200'"
                                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs text-white uppercase tracking-wider shadow-lg transition-all hover:-translate-y-0.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                            {{ empleado.nomina_generada ? 'Re-Generar' : 'Crear Recibo' }}
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECCIÓN 2: HISTORIAL -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-gray-100 text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Historial de Recibos Emitidos</h3>
                            <p class="text-sm text-gray-500">Consulta los pagos anteriores y su estatus.</p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Periodo</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Empleado</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estatus del Dinero</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Archivo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="registro in historial" :key="registro.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">Semana {{ registro.numero_semana }}</div>
                                        <div class="text-xs text-gray-500">{{ registro.fecha_inicio }} al {{ registro.fecha_fin }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-800">{{ registro.empleado.nombre_completo }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex flex-col items-center justify-center gap-1">
                                            <span :class="registro.pagado ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200'" 
                                                  class="px-3 py-1 inline-flex text-xs font-bold rounded-full border">
                                                {{ registro.pagado ? 'Liquidado' : 'Pendiente' }}
                                            </span>
                                            <button @click="cambiarEstadoPago(registro.id)" 
                                                    class="text-[10px] font-bold uppercase tracking-wider text-gray-400 hover:text-indigo-600 transition-colors mt-1 underline decoration-gray-300 hover:decoration-indigo-600">
                                                Cambiar
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-bold rounded-full bg-gray-100 text-gray-800 border">
                                            ${{ Number(registro.pago_neto).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a :href="route('nominas.descargar', registro.id)" target="_blank" 
                                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-bold transition-colors">
                                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            PDF
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- TOAST NOTIFICATION PREMIUM -->
        <div class="fixed bottom-5 right-5 z-50 transition-all duration-300 transform" 
             :class="showToast ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0 pointer-events-none'">
            <div class="bg-gray-900 text-white px-5 py-4 rounded-xl shadow-2xl flex items-center gap-4 border border-gray-700">
                <div class="bg-emerald-500/20 text-emerald-400 p-2 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <h4 class="font-bold text-sm">{{ toastTitle }}</h4>
                    <p class="text-xs text-gray-400 mt-0.5">{{ toastMessage }}</p>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>