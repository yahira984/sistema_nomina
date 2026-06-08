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

const selectedCorte = ref(props.fechaCorteActual);

// --- VARIABLES DE FILTRADO Y ORDENAMIENTO ---
const filtroEstado = ref('todos'); // 'todos', 'pendiente', 'liquidado'
const criterioOrden = ref('asc');   // 'asc', 'desc', 'num_asc', 'num_desc'

// Extrae el número de semana correspondiente a la fecha de corte seleccionada actualmente
const numeroSemanaSeleccionada = computed(() => {
    const encontrada = props.semanasDisponibles.find(sem => sem.fecha_corte === selectedCorte.value);
    return encontrada ? encontrada.numero_semana : props.semanaActual;
});

watch(selectedCorte, (newDate) => {
    router.get(route('nominas.index'), { fecha_corte: newDate }, { preserveState: true, preserveScroll: true });
});

// 1. Primer filtro: Búsqueda + Filtros Estado + Ordenamientos (Alfabético y Numérico)
const empleadosFiltrados = computed(() => {
    let resultado = [...props.empleados];

    // Filtro A: Buscador de texto
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        resultado = resultado.filter(emp => {
            return emp.nombre_completo.toLowerCase().includes(query) ||
                   (emp.numero_empleado && emp.numero_empleado.toLowerCase().includes(query));
        });
    }

    // Filtro B: Estado de pago (Pendiente / Liquidado)
    if (filtroEstado.value === 'pendiente') {
        resultado = resultado.filter(emp => !emp.pagado);
    } else if (filtroEstado.value === 'liquidado') {
        resultado = resultado.filter(emp => emp.pagado);
    }

    // Filtro C: Lógica de Ordenamiento
    resultado.sort((a, b) => {
        // --- ORDEN NUMÉRICO ---
        if (criterioOrden.value === 'num_asc' || criterioOrden.value === 'num_desc') {
            const numA = parseInt(a.numero_empleado, 10);
            const numB = parseInt(b.numero_empleado, 10);

            // Manejo de empleados sin número (los mandamos al fondo)
            if (isNaN(numA) && isNaN(numB)) return 0;
            if (isNaN(numA)) return 1;
            if (isNaN(numB)) return -1;

            return criterioOrden.value === 'num_asc' ? numA - numB : numB - numA;
        } 
        
        // --- ORDEN ALFABÉTICO (Por defecto si es 'asc' o 'desc') ---
        else {
            const nombreA = a.nombre_completo.toLowerCase();
            const nombreB = b.nombre_completo.toLowerCase();
            
            if (criterioOrden.value === 'asc') {
                return nombreA.localeCompare(nombreB);
            } else {
                return nombreB.localeCompare(nombreA);
            }
        }
    });

    return resultado;
});

// 2. Agrupamos los filtrados por su Banco
const empleadosAgrupados = computed(() => {
    const grupos = {};
    
    empleadosFiltrados.value.forEach(empleado => {
        const nombreBanco = empleado.banco ? empleado.banco.toUpperCase() : 'EFECTIVO / SIN BANCO';
        
        if (!grupos[nombreBanco]) {
            grupos[nombreBanco] = [];
        }
        grupos[nombreBanco].push(empleado);
    });

    return Object.keys(grupos).sort().reduce((obj, key) => {
        obj[key] = grupos[key];
        return obj;
    }, {});
});

const copiarCuenta = (banco, cuenta) => {
    if (!cuenta) return;
    const texto = `${banco ? banco + ' - ' : ''}${cuenta}`;
    navigator.clipboard.writeText(cuenta).then(() => {
        toastTitle.value = 'Cuenta copiada';
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
                <Link :href="route('dashboard')" class="icon-button" aria-label="Volver al panel">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19 3 12m0 0 7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <p class="text-sm font-semibold text-teal-700">Pagos y recibos</p>
                    <h2 class="text-2xl font-semibold text-slate-950">Control y Pago de Nóminas</h2>
                </div>
            </div>
        </template>

        <div class="page-shell relative">
            <div class="content-wrap space-y-8">
                <section class="app-panel">
                    <div class="panel-header">
                        <div class="flex items-start gap-3">
                            <div class="soft-icon-teal">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="panel-title">Periodo a procesar</h3>
                                <p class="panel-subtitle">Selecciona la semana y localiza empleados para emitir recibos.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative w-full sm:w-auto">
                                <select v-model="selectedCorte" class="field-input-soft appearance-none pr-10 font-semibold text-slate-800">
                                    <option v-for="sem in semanasDisponibles" :key="sem.fecha_corte" :value="sem.fecha_corte">
                                        {{ sem.etiqueta }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <div class="flex rounded-lg bg-slate-100 p-1 w-full sm:w-auto border border-slate-200">
                                <button 
                                    type="button"
                                    @click="filtroEstado = 'todos'" 
                                    :class="filtroEstado === 'todos' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold transition-all sm:flex-none"
                                >
                                    <i class="ti ti-layout-grid" aria-hidden="true"></i>
                                    Todos
                                </button>
                                <button 
                                    type="button"
                                    @click="filtroEstado = 'pendiente'" 
                                    :class="filtroEstado === 'pendiente' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold transition-all sm:flex-none"
                                >
                                    <i class="ti ti-clock-dollar" aria-hidden="true"></i>
                                    Pendientes
                                </button>
                                <button 
                                    type="button"
                                    @click="filtroEstado = 'liquidado'" 
                                    :class="filtroEstado === 'liquidado' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold transition-all sm:flex-none"
                                >
                                    <i class="ti ti-circle-check" aria-hidden="true"></i>
                                    Liquidados
                                </button>
                            </div>

                            <div class="relative w-full sm:w-auto">
                                <select v-model="criterioOrden" class="field-input-soft appearance-none pr-10 text-slate-700 text-sm font-medium">
                                    <option value="asc">Nombre (A - Z)</option>
                                    <option value="desc">Nombre (Z - A)</option>
                                    <option value="num_asc">No. Empleado (Menor a Mayor)</option>
                                    <option value="num_desc">No. Empleado (Mayor a Menor)</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <div class="relative w-full sm:w-auto">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                    </svg>
                                </div>
                                <input v-model="searchQuery" type="text" class="field-input-soft pl-10" placeholder="Buscar empleado..." />
                            </div>

                            <a :href="route('nominas.reporte', semanaActual)" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 w-full sm:w-auto justify-center">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Excel Global
                            </a>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div v-if="Object.keys(empleadosAgrupados).length === 0" class="empty-state rounded-xl border border-dashed border-slate-300 p-10 text-center">
                            No se encontraron empleados para ese filtro.
                        </div>

                        <div v-else>
                            <div v-for="(empleadosBanco, nombreBanco) in empleadosAgrupados" :key="nombreBanco" class="mb-10 last:mb-0">
                                
                                <div class="mb-4 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-100/80 px-4 py-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm">
                                        <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3Z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-800">{{ nombreBanco }}</h4>
                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        {{ empleadosBanco.length }} empleado(s)
                                    </span>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-slate-200 shadow-sm">
                                    <div class="overflow-x-auto">
                                        <table class="table-premium w-full !border-0">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th>Empleado</th>
                                                    <th>Datos de depósito</th>
                                                    <th class="text-center">Estado de pago</th>
                                                    <th class="text-right">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="empleado in empleadosBanco" :key="empleado.id" class="border-t border-slate-100 hover:bg-slate-50">
                                                    <td class="whitespace-nowrap px-4 py-3">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex h-10 min-w-10 max-w-16 items-center justify-center rounded-lg border border-teal-200 bg-teal-50 px-2 text-xs font-bold text-teal-700">
                                                                {{ empleado.numero_empleado || 'S/N' }}
                                                            </div>
                                                            <div class="min-w-0">
                                                                <div class="truncate font-semibold text-slate-950">{{ empleado.nombre_completo }}</div>
                                                                <div class="text-xs text-slate-500">{{ empleado.puesto || 'Sin puesto asignado' }}</div>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="whitespace-nowrap px-4 py-3">
                                                        <div v-if="empleado.numero_cuenta" class="flex flex-col items-start">
                                                            <span class="text-xs font-semibold uppercase text-slate-500">{{ empleado.banco || 'Banco no especificado' }}</span>
                                                            <button
                                                                @click="copiarCuenta(empleado.banco, empleado.numero_cuenta)"
                                                                class="mt-1 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700 shadow-sm"
                                                                title="Copiar cuenta"
                                                                type="button"
                                                            >
                                                                <span class="font-mono">{{ empleado.numero_cuenta }}</span>
                                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <div v-else class="inline-flex items-center gap-1.5 text-sm font-semibold text-rose-600 bg-rose-50 px-2.5 py-1 rounded-md border border-rose-100">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                                                            </svg>
                                                            Sin cuenta
                                                        </div>
                                                    </td>

                                                    <td class="whitespace-nowrap px-4 py-3 text-center">
                                                        <div v-if="!empleado.nomina_generada" class="text-xs font-medium text-slate-400">
                                                            Genera el recibo primero
                                                        </div>
                                                        <div v-else class="flex flex-col items-center justify-center gap-1.5">
                                                            <span :class="empleado.pagado ? 'status-success' : 'status-warning'" class="status-pill">
                                                                {{ empleado.pagado ? 'Liquidado' : 'Pendiente' }}
                                                            </span>
                                                            <button
                                                                @click="cambiarEstadoPago(empleado.nomina_id)"
                                                                class="text-xs font-semibold text-slate-500 underline decoration-slate-300 transition hover:text-teal-700 hover:decoration-teal-500"
                                                                type="button"
                                                            >
                                                                {{ empleado.pagado ? 'Marcar pendiente' : 'Marcar pagado' }}
                                                            </button>
                                                        </div>
                                                    </td>

                                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <a
                                                                :href="route('nominas.excel-individual', { empleado_id: empleado.id, fecha_corte: selectedCorte })"
                                                                class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-green-700 active:scale-95"
                                                                title="Descargar Recibo en Excel"
                                                            >
                                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8.5 19l-1.5-2.5L5.5 19H4l2-3.5L4 12h1.5l1.5 2.5L8.5 12H10l-2 3.5 2 3.5H8.5zm4.5 0h-1.3l-2-7h1.3l1.4 5.2 1.4-5.2H15l-2 7z"/>
                                                                </svg>
                                                                <span class="hidden md:inline">Excel</span>
                                                            </a>

                                                            <a
                                                                :href="route('nominas.generar', { empleado_id: empleado.id, fecha_corte: selectedCorte })"
                                                                target="_blank"
                                                                @click="marcarComoGenerado(empleado)"
                                                                :class="empleado.nomina_generada ? 'btn-warning' : 'btn-accent'"
                                                            >
                                                                <svg class="h-4 w-4 mr-1.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2Zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10Z" />
                                                                </svg>
                                                                {{ empleado.nomina_generada ? 'Regenerar' : 'Crear recibo' }}
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="app-panel">
                    <div class="panel-header">
                        <div class="flex items-start gap-3">
                            <div class="soft-icon">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="panel-title">Historial de recibos emitidos</h3>
                                <p class="panel-subtitle">Consulta pagos anteriores, estatus y archivos PDF.</p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Periodo</th>
                                    <th>Empleado</th>
                                    <th class="text-center">Estado de pago</th>
                                    <th>Total</th>
                                    <th class="text-right">Archivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="registro in historial" :key="registro.id">
                                    <td class="whitespace-nowrap">
                                        <div class="font-semibold text-slate-950">Semana {{ registro.numero_semana }}</div>
                                        <div class="text-xs text-slate-500">{{ registro.fecha_inicio }} al {{ registro.fecha_fin }}</div>
                                    </td>
                                    <td class="whitespace-nowrap font-semibold text-slate-900">{{ registro.empleado.nombre_completo }}</td>
                                    <td class="whitespace-nowrap text-center">
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <span :class="registro.pagado ? 'status-success' : 'status-warning'" class="status-pill">
                                                {{ registro.pagado ? 'Liquidado' : 'Pendiente' }}
                                            </span>
                                            <button
                                                @click="cambiarEstadoPago(registro.id)"
                                                class="text-xs font-semibold text-slate-500 underline decoration-slate-300 transition hover:text-teal-700 hover:decoration-teal-500"
                                                type="button"
                                            >
                                                Cambiar
                                            </button>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span class="status-pill status-neutral">
                                            ${{ Number(registro.pago_neto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <a
                                            :href="route('nominas.descargar', registro.id)"
                                            target="_blank"
                                            class="btn-secondary"
                                        >
                                            <svg class="h-4 w-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 0 0 2-2V9.414a1 1 0 0 0-.293-.707l-5.414-5.414A1 1 0 0 0 12.586 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z" />
                                            </svg>
                                            PDF
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="historial.length === 0">
                                    <td colspan="5" class="empty-state">
                                        Todavía no hay recibos emitidos en el historial.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div
                class="fixed bottom-5 right-5 z-50 transition duration-300"
                :class="showToast ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0 pointer-events-none'"
            >
                <div class="flex items-center gap-3 rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-white shadow-2xl">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold">{{ toastTitle }}</h4>
                        <p class="mt-0.5 text-xs text-slate-300">{{ toastMessage }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
