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
const historialSearch = ref('');
const showToast = ref(false);
const toastTitle = ref('');
const toastMessage = ref('');
const ajustesNomina = ref({});
const guardandoAjuste = ref(null);
const selectedEmpleadoIds = ref([]);

const selectedCorte = ref(props.fechaCorteActual);

// --- VARIABLES DE FILTRADO Y ORDENAMIENTO ---
const filtroEstado = ref('todos'); // 'todos', 'pendiente', 'liquidado'
const filtroBanco = ref('todos');
const criterioOrden = ref('asc');   // 'asc', 'desc', 'num_asc', 'num_desc'

const nombreBancoEmpleado = (empleado) => {
    const banco = String(empleado.banco || '').trim();
    return banco ? banco.toUpperCase() : 'EFECTIVO / SIN BANCO';
};

const bancosDisponibles = computed(() => {
    const bancos = new Set(props.empleados.map((empleado) => nombreBancoEmpleado(empleado)));
    return Array.from(bancos).sort();
});

// Extrae el número de semana correspondiente a la fecha de corte seleccionada actualmente
const numeroSemanaSeleccionada = computed(() => {
    const encontrada = props.semanasDisponibles.find(sem => sem.fecha_corte === selectedCorte.value);
    return encontrada ? encontrada.numero_semana : props.semanaActual;
});

watch(selectedCorte, (newDate) => {
    router.get(route('nominas.index'), { fecha_corte: newDate }, { preserveState: true, preserveScroll: true });
});

const numero = (valor) => Number(valor ?? 0) || 0;
const valorDecimal = (valor) => Number(valor ?? 0).toFixed(2);
const moneda = (valor) => numero(valor).toLocaleString('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});
const horas = (valor) => numero(valor).toFixed(1);

const empleadosSinHorasExtra = new Set(['8', '9', '22']);
const empleadosSinRetardos = new Set(['14', '76', '78']);
const empleadosPagoPorHoraTopado = new Set(['76', '78']);

const numeroEmpleadoNomina = (empleado) => {
    const texto = String(empleado?.numero_empleado || empleado?.numero_empleado_baja || '').trim();
    const sinCeros = texto.replace(/^0+/, '');

    return sinCeros || texto || '';
};

const reglasEspecialesEmpleado = (empleado) => {
    const numeroEmpleado = numeroEmpleadoNomina(empleado);
    const reglas = [];

    if (empleadosSinHorasExtra.has(numeroEmpleado)) {
        reglas.push({
            texto: 'Sin H.E.',
            clase: 'border-sky-200 bg-sky-50 text-sky-700',
        });
    }

    if (empleadosSinRetardos.has(numeroEmpleado)) {
        reglas.push({
            texto: 'Sin retardos',
            clase: 'border-amber-200 bg-amber-50 text-amber-800',
        });
    }

    if (empleadosPagoPorHoraTopado.has(numeroEmpleado)) {
        reglas.push({
            texto: 'Tope 48 h',
            clase: 'border-violet-200 bg-violet-50 text-violet-700',
        });
    }

    return reglas;
};

const tieneReglaEspecial = (empleado) => reglasEspecialesEmpleado(empleado).length > 0;

const claseFilaNomina = (empleado) => {
    return tieneReglaEspecial(empleado)
        ? 'border-t border-l-4 border-l-amber-400 border-slate-100 bg-amber-50/35 hover:bg-amber-50/70'
        : 'border-t border-slate-100 hover:bg-slate-50';
};

const claseNumeroNomina = (empleado) => {
    if (empleadosPagoPorHoraTopado.has(numeroEmpleadoNomina(empleado))) {
        return 'border-violet-200 bg-violet-50 text-violet-700';
    }

    if (empleadosSinHorasExtra.has(numeroEmpleadoNomina(empleado))) {
        return 'border-sky-200 bg-sky-50 text-sky-700';
    }

    if (empleadosSinRetardos.has(numeroEmpleadoNomina(empleado))) {
        return 'border-amber-200 bg-amber-50 text-amber-800';
    }

    return 'border-teal-200 bg-teal-50 text-teal-700';
};

const inicializarAjustes = () => {
    const actuales = { ...ajustesNomina.value };

    props.empleados.forEach((empleado) => {
        const ajuste = empleado.ajustes_nomina || {};
        actuales[empleado.id] = {
            prestamo_otorgado: valorDecimal(ajuste.prestamo_otorgado),
            prestamo_descuento: valorDecimal(ajuste.prestamo_descuento),
            deduccion_manual: valorDecimal(ajuste.deduccion_manual),
            faltas_pagadas: ajuste.faltas_pagadas ?? 0,
            horas_adeudo_descontadas: valorDecimal(ajuste.horas_adeudo_descontadas),
            dias_vacaciones_pagadas: valorDecimal(ajuste.dias_vacaciones_pagadas),
        };
    });

    ajustesNomina.value = actuales;
};

watch(() => props.empleados, inicializarAjustes, { immediate: true, deep: true });

const parametrosNomina = (empleado) => ({
    empleado_id: empleado.id,
    fecha_corte: selectedCorte.value,
    ...(ajustesNomina.value[empleado.id] || {}),
});

const resumenNomina = (empleado) => empleado.ajustes_nomina || {};

const deudaDespues = (empleado) => {
    const ajuste = ajustesNomina.value[empleado.id] || {};
    const resumen = resumenNomina(empleado);
    const saldoActual = numero(resumen.saldo_prestamo_actual ?? empleado.saldo_prestamo);
    const prestamoActual = numero(ajuste.prestamo_otorgado);
    const prestamoGuardado = numero(resumen.prestamo_otorgado_guardado);
    const descuentoActual = numero(ajuste.prestamo_descuento);
    const descuentoGuardado = numero(resumen.prestamo_descuento_guardado);

    return Math.max(0, saldoActual + (prestamoActual - prestamoGuardado) - (descuentoActual - descuentoGuardado));
};

const faltasPagadasPreview = (empleado) => {
    const ajuste = ajustesNomina.value[empleado.id] || {};
    const detectadas = numero(resumenNomina(empleado).faltas_detectadas);

    return Math.min(numero(ajuste.faltas_pagadas), detectadas);
};

const horasAdeudoGeneradasPreview = (empleado) => faltasPagadasPreview(empleado) * 9.5;

const horasAdeudoDescontadasPreview = (empleado) => {
    const ajuste = ajustesNomina.value[empleado.id] || {};
    const extraDetectada = numero(resumenNomina(empleado).horas_extra_detectadas);

    return Math.min(numero(ajuste.horas_adeudo_descontadas), extraDetectada);
};

const saldoHorasPreview = (empleado) => {
    const resumen = resumenNomina(empleado);

    return Math.max(0, numero(resumen.saldo_horas_adeudo_anterior) + horasAdeudoGeneradasPreview(empleado) - horasAdeudoDescontadasPreview(empleado));
};

const horasExtraPagadasPreview = (empleado) => {
    const resumen = resumenNomina(empleado);

    if (resumen.pago_por_hora_topado) {
        return 0;
    }

    return Math.max(0, numero(resumen.horas_extra_detectadas) - horasAdeudoDescontadasPreview(empleado));
};

const diasVacacionesPreview = (empleado) => {
    const ajuste = ajustesNomina.value[empleado.id] || {};

    return numero(ajuste.dias_vacaciones_pagadas);
};

const pagoVacacionesPreview = (empleado) => {
    const resumen = resumenNomina(empleado);

    return diasVacacionesPreview(empleado) * numero(resumen.pago_dia_planta) * 1.25;
};

const guardarAjustes = (empleado) => {
    guardandoAjuste.value = empleado.id;
    router.put(route('nominas.ajustes', empleado.id), {
        fecha_corte: selectedCorte.value,
        ...(ajustesNomina.value[empleado.id] || {}),
    }, {
        preserveScroll: true,
        onFinish: () => {
            guardandoAjuste.value = null;
        },
    });
};

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
    if (filtroBanco.value !== 'todos') {
        resultado = resultado.filter(emp => nombreBancoEmpleado(emp) === filtroBanco.value);
    }

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

const seleccionadosCount = computed(() => selectedEmpleadoIds.value.length);

const empleadoSeleccionado = (empleadoId) => selectedEmpleadoIds.value.includes(empleadoId);

const toggleEmpleado = (empleadoId, checked) => {
    const actuales = new Set(selectedEmpleadoIds.value);

    if (checked) {
        actuales.add(empleadoId);
    } else {
        actuales.delete(empleadoId);
    }

    selectedEmpleadoIds.value = Array.from(actuales);
};

const todosFiltradosSeleccionados = computed(() => {
    return empleadosFiltrados.value.length > 0
        && empleadosFiltrados.value.every((empleado) => empleadoSeleccionado(empleado.id));
});

const toggleTodosFiltrados = (checked) => {
    const actuales = new Set(selectedEmpleadoIds.value);

    empleadosFiltrados.value.forEach((empleado) => {
        if (checked) {
            actuales.add(empleado.id);
        } else {
            actuales.delete(empleado.id);
        }
    });

    selectedEmpleadoIds.value = Array.from(actuales);
};

const empleadosGrupoSeleccionados = (empleadosGrupo) => {
    return empleadosGrupo.length > 0
        && empleadosGrupo.every((empleado) => empleadoSeleccionado(empleado.id));
};

const toggleEmpleadosGrupo = (empleadosGrupo, checked) => {
    const actuales = new Set(selectedEmpleadoIds.value);

    empleadosGrupo.forEach((empleado) => {
        if (checked) {
            actuales.add(empleado.id);
        } else {
            actuales.delete(empleado.id);
        }
    });

    selectedEmpleadoIds.value = Array.from(actuales);
};

const seleccionarBanco = (empleadosBanco) => {
    toggleEmpleadosGrupo(empleadosBanco, true);
};

const urlRecibosMasivos = (todos = false) => {
    const parametros = {
        fecha_corte: selectedCorte.value,
    };

    if (!todos) {
        parametros.empleado_ids = selectedEmpleadoIds.value;
    }

    return route('nominas.recibos-masivos', parametros);
};

const urlRecibosGrupo = (empleadosGrupo) => {
    return route('nominas.recibos-masivos', {
        fecha_corte: selectedCorte.value,
        empleado_ids: empleadosGrupo.map((empleado) => empleado.id),
    });
};

watch(() => props.empleados, (empleados) => {
    const idsActuales = new Set(empleados.map((empleado) => empleado.id));
    selectedEmpleadoIds.value = selectedEmpleadoIds.value.filter((id) => idsActuales.has(id));
}, { deep: true });

// 2. Agrupamos los filtrados por su Banco
const empleadosAgrupados = computed(() => {
    const grupos = {};
    
    empleadosFiltrados.value.forEach(empleado => {
        const nombreBanco = nombreBancoEmpleado(empleado);
        
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

const historialFiltrado = computed(() => {
    const term = historialSearch.value.toLowerCase().trim();

    if (!term) {
        return props.historial;
    }

    return props.historial.filter((registro) => {
        const empleado = registro.empleado || {};

        return String(empleado.nombre_completo || '').toLowerCase().includes(term)
            || String(empleado.numero_empleado || '').toLowerCase().includes(term)
            || String(registro.numero_semana || '').toLowerCase().includes(term)
            || String(registro.fecha_inicio || '').toLowerCase().includes(term)
            || String(registro.fecha_fin || '').toLowerCase().includes(term);
    });
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

const cambiarEstadoPago = (nominaId, pagadoActual = false) => {
    if(!nominaId) return;

    const mensaje = pagadoActual
        ? 'Marcar esta nomina como pendiente? Se revertira el movimiento de prestamo aplicado.'
        : 'Marcar esta nomina como pagada? En este momento se aplicara el movimiento de prestamo al saldo del empleado.';

    if (!confirm(mensaje)) {
        return;
    }

    router.put(route('nominas.pagar', nominaId), {}, {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Control de Nóminas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <Link :href="route('dashboard')" class="icon-button" aria-label="Volver al panel">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19 3 12m0 0 7-7m-7 7h18" />
                    </svg>
                </Link>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-teal-700">Pagos y recibos</p>
                    <h2 class="text-xl font-semibold text-slate-950 sm:text-2xl">Control y Pago de Nóminas</h2>
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

                        <div class="flex w-full flex-wrap items-center gap-3 lg:w-auto">
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
                                <select v-model="filtroBanco" class="field-input-soft appearance-none pr-10 text-slate-700 text-sm font-medium">
                                    <option value="todos">Todos los bancos</option>
                                    <option v-for="banco in bancosDisponibles" :key="banco" :value="banco">
                                        {{ banco }}
                                    </option>
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

                            <a :href="route('nominas.reporte', { semana: numeroSemanaSeleccionada, fecha_corte: selectedCorte })" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 w-full sm:w-auto justify-center">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Excel Global
                            </a>

                            <a
                                v-if="seleccionadosCount > 0"
                                :href="urlRecibosMasivos(false)"
                                target="_blank"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700 sm:w-auto"
                            >
                                <i class="ti ti-printer" aria-hidden="true"></i>
                                PDF seleccionados ({{ seleccionadosCount }})
                            </a>
                            <button
                                v-else
                                type="button"
                                disabled
                                class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500 sm:w-auto"
                            >
                                <i class="ti ti-printer" aria-hidden="true"></i>
                                PDF seleccionados
                            </button>

                            <a
                                :href="urlRecibosMasivos(true)"
                                target="_blank"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 sm:w-auto"
                            >
                                <i class="ti ti-printer" aria-hidden="true"></i>
                                PDF todos
                            </a>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div v-if="Object.keys(empleadosAgrupados).length === 0" class="empty-state rounded-xl border border-dashed border-slate-300 p-10 text-center">
                            No se encontraron empleados para ese filtro.
                        </div>

                        <div v-else>
                            <div v-for="(empleadosBanco, nombreBanco) in empleadosAgrupados" :key="nombreBanco" class="mb-10 last:mb-0">
                                
                                <div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-slate-100/80 px-4 py-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm">
                                        <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3Z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-800">{{ nombreBanco }}</h4>
                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        {{ empleadosBanco.length }} empleado(s)
                                    </span>
                                    <div class="ml-auto flex w-full flex-wrap gap-2 sm:w-auto">
                                        <button
                                            type="button"
                                            @click="seleccionarBanco(empleadosBanco)"
                                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 sm:flex-none"
                                        >
                                            <i class="ti ti-checks" aria-hidden="true"></i>
                                            Seleccionar banco
                                        </button>
                                        <a
                                            :href="urlRecibosGrupo(empleadosBanco)"
                                            target="_blank"
                                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800 sm:flex-none"
                                        >
                                            <i class="ti ti-printer" aria-hidden="true"></i>
                                            PDF banco
                                        </a>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-slate-200 shadow-sm">
                                    <div class="overflow-x-auto">
                                        <table class="table-premium w-full !border-0">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th class="w-12 text-center">
                                                        <input
                                                            type="checkbox"
                                                            :checked="empleadosGrupoSeleccionados(empleadosBanco)"
                                                            @change="toggleEmpleadosGrupo(empleadosBanco, $event.target.checked)"
                                                            class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                                            title="Seleccionar banco"
                                                        />
                                                    </th>
                                                    <th>Empleado</th>
                                                    <th>Datos de depósito</th>
                                                    <th class="text-center">Estado de pago</th>
                                                    <th class="min-w-[720px]">Ajustes de semana</th>
                                                    <th class="text-right">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="empleado in empleadosBanco" :key="empleado.id" :class="claseFilaNomina(empleado)">
                                                    <td class="px-4 py-3 text-center">
                                                        <input
                                                            type="checkbox"
                                                            :checked="empleadoSeleccionado(empleado.id)"
                                                            @change="toggleEmpleado(empleado.id, $event.target.checked)"
                                                            class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                                            :title="`Seleccionar ${empleado.nombre_completo}`"
                                                        />
                                                    </td>
                                                    <td class="whitespace-nowrap px-4 py-3">
                                                        <div class="flex items-center gap-3">
                                                            <div :class="['flex h-10 min-w-10 max-w-16 items-center justify-center rounded-lg border px-2 text-xs font-bold', claseNumeroNomina(empleado)]">
                                                                {{ empleado.numero_empleado || 'S/N' }}
                                                            </div>
                                                            <div class="min-w-0">
                                                                <div class="truncate font-semibold text-slate-950">{{ empleado.nombre_completo }}</div>
                                                                <div class="text-xs text-slate-500">{{ empleado.puesto || 'Sin puesto asignado' }}</div>
                                                                <div v-if="tieneReglaEspecial(empleado)" class="mt-1 flex flex-wrap gap-1">
                                                                    <span
                                                                        v-for="regla in reglasEspecialesEmpleado(empleado)"
                                                                        :key="regla.texto"
                                                                        :class="['rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-wide', regla.clase]"
                                                                    >
                                                                        {{ regla.texto }}
                                                                    </span>
                                                                </div>
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
                                                                @click="cambiarEstadoPago(empleado.nomina_id, empleado.pagado)"
                                                                class="text-xs font-semibold text-slate-500 underline decoration-slate-300 transition hover:text-teal-700 hover:decoration-teal-500"
                                                                type="button"
                                                            >
                                                                {{ empleado.pagado ? 'Revertir pago' : 'Marcar pagado y aplicar prestamo' }}
                                                            </button>
                                                        </div>
                                                    </td>

                                                    <td class="min-w-[720px] px-4 py-3 align-top">
                                                        <div v-if="ajustesNomina[empleado.id]" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                                            <div class="grid grid-cols-2 gap-px bg-slate-200 text-xs lg:grid-cols-4">
                                                                <div class="bg-emerald-50 px-3 py-2 text-emerald-800">
                                                                    <span class="block font-bold uppercase tracking-wide">Neto</span>
                                                                    <span class="text-sm font-black">${{ moneda(resumenNomina(empleado).pago_neto) }}</span>
                                                                </div>
                                                                <div class="bg-slate-50 px-3 py-2 text-slate-700">
                                                                    <span class="block font-bold uppercase tracking-wide">Deuda actual</span>
                                                                    <span class="text-sm font-black">${{ moneda(resumenNomina(empleado).saldo_prestamo_actual ?? empleado.saldo_prestamo) }}</span>
                                                                </div>
                                                                <div class="bg-blue-50 px-3 py-2 text-blue-800">
                                                                    <span class="block font-bold uppercase tracking-wide">Despues ajuste</span>
                                                                    <span class="text-sm font-black">${{ moneda(deudaDespues(empleado)) }}</span>
                                                                </div>
                                                                <div class="bg-amber-50 px-3 py-2 text-amber-800">
                                                                    <span class="block font-bold uppercase tracking-wide">Horas adeudo</span>
                                                                    <span class="text-sm font-black">{{ horas(saldoHorasPreview(empleado)) }} h</span>
                                                                </div>
                                                            </div>

                                                            <div class="grid gap-3 p-3 lg:grid-cols-[0.95fr_1.25fr_0.8fr]">
                                                                <section class="rounded-lg border border-blue-100 bg-blue-50/60 p-3">
                                                                    <div class="mb-3 flex items-center gap-2 text-sm font-black text-blue-900">
                                                                        <i class="ti ti-cash-banknote" aria-hidden="true"></i>
                                                                        Prestamo
                                                                    </div>
                                                                    <div class="grid grid-cols-2 gap-2">
                                                                        <label class="block">
                                                                            <span class="mb-1 block text-[10px] font-bold uppercase text-blue-700">Compensacion</span>
                                                                            <input v-model="ajustesNomina[empleado.id].prestamo_otorgado" type="number" step="0.01" min="0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                                        </label>
                                                                        <label class="block">
                                                                            <span class="mb-1 block text-[10px] font-bold uppercase text-blue-700">Adeudo</span>
                                                                            <input v-model="ajustesNomina[empleado.id].prestamo_descuento" type="number" step="0.01" min="0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                                        </label>
                                                                    </div>
                                                                </section>

                                                                <section class="rounded-lg border border-amber-100 bg-amber-50/70 p-3">
                                                                    <div class="mb-3 flex items-center justify-between gap-3">
                                                                        <div class="flex items-center gap-2 text-sm font-black text-amber-900">
                                                                            <i class="ti ti-calendar-exclamation" aria-hidden="true"></i>
                                                                            Faltas pagadas y horas
                                                                        </div>
                                                                        <span class="rounded-full border border-rose-200 bg-white px-2 py-1 text-[11px] font-bold text-rose-700">
                                                                            {{ resumenNomina(empleado).faltas_detectadas || 0 }} falta(s)
                                                                        </span>
                                                                    </div>
                                                                    <div class="grid grid-cols-2 gap-2">
                                                                        <label class="block">
                                                                            <span class="mb-1 block text-[10px] font-bold uppercase text-amber-700">Faltas que se pagaron</span>
                                                                            <input v-model="ajustesNomina[empleado.id].faltas_pagadas" type="number" step="1" min="0" :max="resumenNomina(empleado).faltas_detectadas || 0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                                        </label>
                                                                        <label class="block">
                                                                            <span class="mb-1 block text-[10px] font-bold uppercase text-amber-700">Hrs extra a tomar</span>
                                                                            <input v-model="ajustesNomina[empleado.id].horas_adeudo_descontadas" type="number" step="0.5" min="0" :max="resumenNomina(empleado).horas_extra_detectadas || 0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                                        </label>
                                                                    </div>
                                                                    <div class="mt-3 grid grid-cols-3 gap-2 text-[11px] font-bold">
                                                                        <span class="rounded-md bg-white px-2 py-1 text-slate-600">Genera {{ horas(horasAdeudoGeneradasPreview(empleado)) }} h</span>
                                                                        <span class="rounded-md bg-white px-2 py-1 text-slate-600">Extra {{ horas(resumenNomina(empleado).horas_extra_detectadas) }} h</span>
                                                                        <span class="rounded-md bg-white px-2 py-1 text-emerald-700">Paga {{ horas(horasExtraPagadasPreview(empleado)) }} h</span>
                                                                    </div>
                                                                    <div v-if="Number(resumenNomina(empleado).horas_extra_miercoles_anterior || 0) > 0" class="mt-2 text-[11px] font-semibold text-amber-800">
                                                                        Incluye {{ horas(resumenNomina(empleado).horas_extra_miercoles_anterior) }} h del miercoles anterior.
                                                                    </div>
                                                                </section>

                                                                <section class="flex flex-col justify-between rounded-lg border border-slate-200 bg-slate-50 p-3">
                                                                    <div>
                                                                        <div class="mb-3 flex items-center gap-2 text-sm font-black text-slate-800">
                                                                            <i class="ti ti-adjustments-dollar" aria-hidden="true"></i>
                                                                            Otros
                                                                        </div>
                                                                        <label class="block">
                                                                            <span class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Desc. extra</span>
                                                                            <input v-model="ajustesNomina[empleado.id].deduccion_manual" type="number" step="0.01" min="0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                                        </label>
                                                                        <label class="mt-2 block">
                                                                            <span class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Dias vac. +25%</span>
                                                                            <input v-model="ajustesNomina[empleado.id].dias_vacaciones_pagadas" type="number" step="0.5" min="0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                                        </label>
                                                                        <div class="mt-3 grid grid-cols-2 gap-2 text-[11px] font-bold">
                                                                            <span class="rounded-md bg-white px-2 py-1 text-rose-700">{{ resumenNomina(empleado).faltas_descontables || 0 }} falta(s) desc.</span>
                                                                            <span class="rounded-md bg-white px-2 py-1 text-amber-700">{{ resumenNomina(empleado).minutos_tarde_descontables || 0 }} min ret.</span>
                                                                            <span class="rounded-md bg-white px-2 py-1 text-blue-700">{{ horas(diasVacacionesPreview(empleado)) }} dia(s) vac.</span>
                                                                            <span class="rounded-md bg-white px-2 py-1 text-emerald-700">${{ moneda(pagoVacacionesPreview(empleado)) }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button" @click="guardarAjustes(empleado)" class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white transition hover:bg-slate-800">
                                                                        <i class="ti ti-device-floppy" aria-hidden="true"></i>
                                                                        {{ guardandoAjuste === empleado.id ? 'Guardando...' : 'Guardar ajustes' }}
                                                                    </button>
                                                                </section>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                                            <a
                                                                :href="route('nominas.excel-individual', parametrosNomina(empleado))"
                                                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-green-700 active:scale-95 sm:w-auto"
                                                                title="Descargar Recibo en Excel"
                                                            >
                                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8.5 19l-1.5-2.5L5.5 19H4l2-3.5L4 12h1.5l1.5 2.5L8.5 12H10l-2 3.5 2 3.5H8.5zm4.5 0h-1.3l-2-7h1.3l1.4 5.2 1.4-5.2H15l-2 7z"/>
                                                                </svg>
                                                                <span class="hidden sm:inline">Excel</span>
                                                            </a>

                                                            <a
                                                                :href="route('nominas.generar', parametrosNomina(empleado))"
                                                                target="_blank"
                                                                @click="marcarComoGenerado(empleado)"
                                                                :class="empleado.nomina_generada ? 'btn-warning w-full sm:w-auto' : 'btn-accent w-full sm:w-auto'"
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
                        <div class="relative w-full sm:w-80">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="ti ti-search" aria-hidden="true"></i>
                            </div>
                            <input
                                v-model="historialSearch"
                                type="text"
                                class="field-input-soft pl-9"
                                placeholder="Buscar nombre o numero..."
                            />
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
                                <tr v-for="registro in historialFiltrado" :key="registro.id">
                                    <td class="whitespace-nowrap">
                                        <div class="font-semibold text-slate-950">Semana {{ registro.numero_semana }}</div>
                                        <div class="text-xs text-slate-500">{{ registro.fecha_inicio }} al {{ registro.fecha_fin }}</div>
                                    </td>
                                    <td class="whitespace-nowrap font-semibold text-slate-900">{{ registro.empleado?.nombre_completo || 'Sin empleado' }}</td>
                                    <td class="whitespace-nowrap text-center">
                                        <div class="flex flex-col items-center justify-center gap-1.5">
                                            <span :class="registro.pagado ? 'status-success' : 'status-warning'" class="status-pill">
                                                {{ registro.pagado ? 'Liquidado' : 'Pendiente' }}
                                            </span>
                                            <button
                                                @click="cambiarEstadoPago(registro.id, registro.pagado)"
                                                class="text-xs font-semibold text-slate-500 underline decoration-slate-300 transition hover:text-teal-700 hover:decoration-teal-500"
                                                type="button"
                                            >
                                                {{ registro.pagado ? 'Revertir pago' : 'Marcar pagado' }}
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
                                <tr v-if="historialFiltrado.length === 0">
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
                class="fixed inset-x-3 bottom-4 z-50 transition duration-300 sm:inset-x-auto sm:bottom-5 sm:right-5"
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
