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

const claveTemaBanco = (nombreBanco) => {
    const banco = String(nombreBanco || '').toUpperCase();

    if (banco.includes('AZTECA')) return 'AZTECA';
    if (banco.includes('BANAMEX') || banco.includes('CITIBANAMEX')) return 'BANAMEX';
    if (banco.includes('BANORTE')) return 'BANORTE';
    if (banco.includes('BBVA')) return 'BBVA';
    if (banco.includes('COPPEL')) return 'COPPEL';
    if (banco.includes('HSBC')) return 'HSBC';
    if (banco.includes('MERCADO')) return 'MERCADO PAGO';
    if (banco === 'NU' || banco.includes('NU ')) return 'NU';
    if (banco.includes('SANTANDER')) return 'SANTANDER';
    if (banco.includes('SPIN') || banco.includes('OXXO')) return 'SPIN BY OXXO';
    if (banco.includes('EFECTIVO') || banco.includes('SIN BANCO')) return 'EFECTIVO / SIN BANCO';

    return 'DEFAULT';
};

const temasBanco = {
    AZTECA: {
        header: 'border-emerald-600 bg-emerald-50/95',
        icon: 'border-emerald-200 bg-white text-emerald-700',
        title: 'text-emerald-950',
        badge: 'bg-white text-emerald-800 ring-1 ring-emerald-200',
        stripe: 'bg-emerald-600',
        button: 'border-emerald-200 bg-white text-emerald-800 hover:bg-emerald-100',
        pdf: 'bg-emerald-700 hover:bg-emerald-800 text-white',
    },
    BANAMEX: {
        header: 'border-red-600 bg-blue-50/95',
        icon: 'border-blue-200 bg-white text-blue-700',
        title: 'text-blue-950',
        badge: 'bg-white text-red-700 ring-1 ring-red-200',
        stripe: 'bg-gradient-to-r from-red-600 via-white to-blue-700',
        button: 'border-blue-200 bg-white text-blue-800 hover:bg-blue-100',
        pdf: 'bg-red-600 hover:bg-red-700 text-white',
    },
    BANORTE: {
        header: 'border-red-700 bg-red-50/95',
        icon: 'border-red-200 bg-white text-red-700',
        title: 'text-red-950',
        badge: 'bg-white text-red-700 ring-1 ring-red-200',
        stripe: 'bg-red-700',
        button: 'border-red-200 bg-white text-red-800 hover:bg-red-100',
        pdf: 'bg-red-700 hover:bg-red-800 text-white',
    },
    BBVA: {
        header: 'border-blue-700 bg-blue-50/95',
        icon: 'border-blue-200 bg-white text-blue-700',
        title: 'text-blue-950',
        badge: 'bg-white text-blue-700 ring-1 ring-blue-200',
        stripe: 'bg-blue-700',
        button: 'border-blue-200 bg-white text-blue-800 hover:bg-blue-100',
        pdf: 'bg-blue-700 hover:bg-blue-800 text-white',
    },
    COPPEL: {
        header: 'border-yellow-400 bg-blue-50/95',
        icon: 'border-yellow-200 bg-yellow-100 text-blue-800',
        title: 'text-blue-950',
        badge: 'bg-yellow-100 text-blue-800 ring-1 ring-yellow-300',
        stripe: 'bg-gradient-to-r from-blue-700 to-yellow-400',
        button: 'border-yellow-200 bg-white text-blue-800 hover:bg-yellow-50',
        pdf: 'bg-blue-700 hover:bg-blue-800 text-white',
    },
    'EFECTIVO / SIN BANCO': {
        header: 'border-slate-500 bg-slate-100/95',
        icon: 'border-slate-300 bg-white text-slate-700',
        title: 'text-slate-950',
        badge: 'bg-white text-slate-700 ring-1 ring-slate-300',
        stripe: 'bg-slate-500',
        button: 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100',
        pdf: 'bg-slate-900 hover:bg-slate-800 text-white',
    },
    HSBC: {
        header: 'border-red-700 bg-slate-50/95',
        icon: 'border-slate-300 bg-white text-red-700',
        title: 'text-slate-950',
        badge: 'bg-white text-red-700 ring-1 ring-slate-300',
        stripe: 'bg-gradient-to-r from-red-700 via-white to-slate-950',
        button: 'border-slate-300 bg-white text-slate-900 hover:bg-red-50',
        pdf: 'bg-slate-950 hover:bg-slate-800 text-white',
    },
    'MERCADO PAGO': {
        header: 'border-yellow-400 bg-sky-50/95',
        icon: 'border-sky-200 bg-yellow-100 text-sky-800',
        title: 'text-sky-950',
        badge: 'bg-white text-sky-800 ring-1 ring-sky-200',
        stripe: 'bg-gradient-to-r from-yellow-300 to-sky-500',
        button: 'border-sky-200 bg-white text-sky-800 hover:bg-sky-100',
        pdf: 'bg-sky-600 hover:bg-sky-700 text-white',
    },
    NU: {
        header: 'border-purple-700 bg-purple-50/95',
        icon: 'border-purple-200 bg-white text-purple-700',
        title: 'text-purple-950',
        badge: 'bg-white text-purple-700 ring-1 ring-purple-200',
        stripe: 'bg-purple-700',
        button: 'border-purple-200 bg-white text-purple-800 hover:bg-purple-100',
        pdf: 'bg-purple-700 hover:bg-purple-800 text-white',
    },
    SANTANDER: {
        header: 'border-red-600 bg-red-50/95',
        icon: 'border-red-200 bg-white text-red-700',
        title: 'text-red-950',
        badge: 'bg-white text-red-700 ring-1 ring-red-200',
        stripe: 'bg-red-600',
        button: 'border-red-200 bg-white text-red-800 hover:bg-red-100',
        pdf: 'bg-red-600 hover:bg-red-700 text-white',
    },
    'SPIN BY OXXO': {
        header: 'border-purple-700 bg-orange-50/95',
        icon: 'border-orange-200 bg-white text-purple-700',
        title: 'text-purple-950',
        badge: 'bg-white text-orange-700 ring-1 ring-orange-200',
        stripe: 'bg-gradient-to-r from-purple-700 via-orange-500 to-white',
        button: 'border-orange-200 bg-white text-purple-800 hover:bg-orange-100',
        pdf: 'bg-purple-700 hover:bg-purple-800 text-white',
    },
    DEFAULT: {
        header: 'border-teal-600 bg-teal-50/95',
        icon: 'border-teal-200 bg-white text-teal-700',
        title: 'text-teal-950',
        badge: 'bg-white text-teal-700 ring-1 ring-teal-200',
        stripe: 'bg-teal-600',
        button: 'border-teal-200 bg-white text-teal-800 hover:bg-teal-100',
        pdf: 'bg-teal-700 hover:bg-teal-800 text-white',
    },
};

const temaBanco = (nombreBanco) => temasBanco[claveTemaBanco(nombreBanco)] || temasBanco.DEFAULT;

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
            faltas_cubiertas_vacaciones: valorDecimal(ajuste.faltas_cubiertas_vacaciones),
            faltas_cubiertas_incapacidad: valorDecimal(ajuste.faltas_cubiertas_incapacidad),
            horas_adeudo_descontadas: valorDecimal(ajuste.horas_adeudo_descontadas),
            dias_vacaciones_adicionales: valorDecimal(ajuste.dias_vacaciones_adicionales),
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

const faltasCubiertasVacacionesPreview = (empleado) => {
    const ajuste = ajustesNomina.value[empleado.id] || {};
    const disponibles = Math.max(0, numero(resumenNomina(empleado).faltas_detectadas) - faltasPagadasPreview(empleado));

    return Math.min(numero(ajuste.faltas_cubiertas_vacaciones), disponibles);
};

const faltasCubiertasIncapacidadPreview = (empleado) => {
    const ajuste = ajustesNomina.value[empleado.id] || {};
    const restantes = Math.max(
        0,
        numero(resumenNomina(empleado).faltas_detectadas) - faltasPagadasPreview(empleado) - faltasCubiertasVacacionesPreview(empleado),
    );

    return Math.min(numero(ajuste.faltas_cubiertas_incapacidad), restantes);
};

const faltasConDescuentoPreview = (empleado) => {
    return Math.max(0, numero(resumenNomina(empleado).faltas_detectadas) - faltasPagadasPreview(empleado));
};

const horasAdeudoMiercolesAnterior = (empleado) => numero(resumenNomina(empleado).horas_adeudo_miercoles_anterior);

const horasAdeudoGeneradasPreview = (empleado) => {
    return (faltasPagadasPreview(empleado) * 9.5) + horasAdeudoMiercolesAnterior(empleado);
};

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

const diasVacacionesAdicionalesPreview = (empleado) => {
    const ajuste = ajustesNomina.value[empleado.id] || {};

    return numero(ajuste.dias_vacaciones_adicionales);
};

const diasVacacionesPreview = (empleado) => {
    return numero(resumenNomina(empleado).dias_vacaciones_detectadas)
        + faltasCubiertasVacacionesPreview(empleado)
        + diasVacacionesAdicionalesPreview(empleado);
};

const diasIncapacidadPreview = (empleado) => {
    return numero(resumenNomina(empleado).dias_incapacidad_detectadas)
        + faltasCubiertasIncapacidadPreview(empleado);
};

const pagoVacacionesPreview = (empleado) => {
    const resumen = resumenNomina(empleado);

    return diasVacacionesPreview(empleado) * numero(resumen.pago_dia_planta) * 1.25;
};

const pagoIncapacidadPreview = (empleado) => {
    const resumen = resumenNomina(empleado);

    return diasIncapacidadPreview(empleado) * numero(resumen.pago_dia_planta) * 0.60;
};

const payloadAjustesEmpleado = (empleado) => ({
    fecha_corte: selectedCorte.value,
    ...(ajustesNomina.value[empleado.id] || {}),
});

const guardarAjustes = (empleado) => {
    guardandoAjuste.value = empleado.id;
    router.put(route('nominas.ajustes', empleado.id), payloadAjustesEmpleado(empleado), {
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

const cambiarEstadoPago = (nominaId, pagadoActual = false, empleado = null) => {
    if(!nominaId) return;

    const mensaje = pagadoActual
        ? 'Marcar esta nomina como pendiente? Se revertiran el prestamo y las vacaciones aplicadas.'
        : 'Marcar esta nomina como pagada? Se aplicaran prestamo y vacaciones al saldo del empleado.';

    if (!confirm(mensaje)) {
        return;
    }

    const payload = !pagadoActual && empleado ? payloadAjustesEmpleado(empleado) : {};

    router.put(route('nominas.pagar', nominaId), payload, {
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
                            <div v-for="(empleadosBanco, nombreBanco) in empleadosAgrupados" :key="nombreBanco" class="mb-10 scroll-mt-6 last:mb-0">
                                
                                <div :class="['sticky top-3 z-20 mb-4 overflow-hidden rounded-xl border border-l-8 px-4 py-3 shadow-sm backdrop-blur', temaBanco(nombreBanco).header]">
                                    <div class="flex flex-wrap items-center gap-3">
                                    <div :class="['flex h-9 w-9 items-center justify-center rounded-lg border shadow-sm', temaBanco(nombreBanco).icon]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3Z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 :class="['truncate text-lg font-black uppercase tracking-wide', temaBanco(nombreBanco).title]">{{ nombreBanco }}</h4>
                                        <div :class="['mt-1 h-1 w-32 rounded-full', temaBanco(nombreBanco).stripe]" aria-hidden="true"></div>
                                    </div>
                                    <span :class="['rounded-full px-2.5 py-1 text-xs font-bold', temaBanco(nombreBanco).badge]">
                                        {{ empleadosBanco.length }} empleado(s)
                                    </span>
                                    <div class="ml-auto flex w-full flex-wrap gap-2 sm:w-auto">
                                        <button
                                            type="button"
                                            @click="seleccionarBanco(empleadosBanco)"
                                            :class="['inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-bold shadow-sm transition sm:flex-none', temaBanco(nombreBanco).button]"
                                        >
                                            <i class="ti ti-checks" aria-hidden="true"></i>
                                            Seleccionar banco
                                        </button>
                                        <a
                                            :href="urlRecibosGrupo(empleadosBanco)"
                                            target="_blank"
                                            :class="['inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold shadow-sm transition sm:flex-none', temaBanco(nombreBanco).pdf]"
                                        >
                                            <i class="ti ti-printer" aria-hidden="true"></i>
                                            PDF banco
                                        </a>
                                    </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <article
                                        v-for="empleado in empleadosBanco"
                                        :key="`compacto-${empleado.id}`"
                                        :class="[
                                            'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-teal-200',
                                            tieneReglaEspecial(empleado) ? 'border-l-4 border-l-amber-400 bg-amber-50/20' : ''
                                        ]"
                                    >
                                        <div class="grid gap-3 border-b border-slate-100 bg-white p-3 lg:grid-cols-[minmax(260px,1.2fr)_minmax(210px,0.9fr)_minmax(170px,0.7fr)_auto] lg:items-center">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <input
                                                    type="checkbox"
                                                    :checked="empleadoSeleccionado(empleado.id)"
                                                    @change="toggleEmpleado(empleado.id, $event.target.checked)"
                                                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                                    :title="`Seleccionar ${empleado.nombre_completo}`"
                                                />
                                                <div :class="['flex h-10 min-w-10 max-w-16 items-center justify-center rounded-lg border px-2 text-xs font-bold', claseNumeroNomina(empleado)]">
                                                    {{ empleado.numero_empleado || 'S/N' }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-black uppercase text-slate-950">{{ empleado.nombre_completo }}</div>
                                                    <div class="text-xs font-semibold text-slate-500">{{ empleado.puesto || 'Sin puesto asignado' }}</div>
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

                                            <div>
                                                <div v-if="empleado.numero_cuenta" class="flex flex-col items-start">
                                                    <span class="text-[10px] font-black uppercase tracking-wide text-slate-500">{{ empleado.banco || 'Banco no especificado' }}</span>
                                                    <button
                                                        @click="copiarCuenta(empleado.banco, empleado.numero_cuenta)"
                                                        class="mt-1 inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700"
                                                        title="Copiar cuenta"
                                                        type="button"
                                                    >
                                                        <span class="truncate font-mono">{{ empleado.numero_cuenta }}</span>
                                                        <i class="ti ti-copy shrink-0" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                                <div v-else class="inline-flex items-center gap-1.5 rounded-md border border-rose-100 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600">
                                                    <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                                                    Sin cuenta
                                                </div>
                                            </div>

                                            <div>
                                                <div v-if="!empleado.nomina_generada" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-500">
                                                    Genera el recibo primero
                                                </div>
                                                <div v-else class="flex flex-wrap items-center gap-2">
                                                    <span :class="empleado.pagado ? 'status-success' : 'status-warning'" class="status-pill">
                                                        {{ empleado.pagado ? 'Liquidado' : 'Pendiente' }}
                                                    </span>
                                                    <button
                                                        @click="cambiarEstadoPago(empleado.nomina_id, empleado.pagado, empleado)"
                                                        class="text-xs font-semibold text-slate-500 underline decoration-slate-300 transition hover:text-teal-700 hover:decoration-teal-500"
                                                        type="button"
                                                    >
                                                        {{ empleado.pagado ? 'Revertir pago' : 'Marcar pagado y aplicar saldos' }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="flex flex-col gap-2 sm:flex-row lg:justify-end">
                                                <a
                                                    :href="route('nominas.excel-individual', parametrosNomina(empleado))"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-green-700 active:scale-95"
                                                    title="Descargar Recibo en Excel"
                                                >
                                                    <i class="ti ti-file-spreadsheet" aria-hidden="true"></i>
                                                    Excel
                                                </a>

                                                <a
                                                    :href="route('nominas.generar', parametrosNomina(empleado))"
                                                    target="_blank"
                                                    @click="marcarComoGenerado(empleado)"
                                                    :class="empleado.nomina_generada ? 'btn-warning text-xs' : 'btn-accent text-xs'"
                                                >
                                                    <i class="ti ti-printer" aria-hidden="true"></i>
                                                    {{ empleado.nomina_generada ? 'Regenerar' : 'Crear recibo' }}
                                                </a>
                                            </div>
                                        </div>

                                        <div v-if="ajustesNomina[empleado.id]" class="p-3">
                                            <div class="grid grid-cols-2 gap-2 text-xs lg:grid-cols-4">
                                                <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-emerald-800">
                                                    <span class="block text-[10px] font-black uppercase tracking-wide">Neto</span>
                                                    <span class="text-base font-black">${{ moneda(resumenNomina(empleado).pago_neto) }}</span>
                                                </div>
                                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-slate-700">
                                                    <span class="block text-[10px] font-black uppercase tracking-wide">Deuda actual</span>
                                                    <span class="text-base font-black">${{ moneda(resumenNomina(empleado).saldo_prestamo_actual ?? empleado.saldo_prestamo) }}</span>
                                                </div>
                                                <div class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-blue-800">
                                                    <span class="block text-[10px] font-black uppercase tracking-wide">Despues ajuste</span>
                                                    <span class="text-base font-black">${{ moneda(deudaDespues(empleado)) }}</span>
                                                </div>
                                                <div class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-amber-800">
                                                    <span class="block text-[10px] font-black uppercase tracking-wide">Horas adeudo</span>
                                                    <span class="text-base font-black">{{ horas(saldoHorasPreview(empleado)) }} h</span>
                                                </div>
                                            </div>

                                            <div class="mt-3 grid gap-3 xl:grid-cols-[0.82fr_1.7fr_1fr]">
                                                <section class="rounded-lg border border-blue-100 bg-blue-50/60 p-3">
                                                    <div class="mb-2 flex items-center gap-2 text-sm font-black text-blue-900">
                                                        <i class="ti ti-cash-banknote" aria-hidden="true"></i>
                                                        Prestamo
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2 xl:grid-cols-1">
                                                        <label class="block">
                                                            <span class="mb-1 block text-[10px] font-bold uppercase leading-tight text-blue-700">Compensacion</span>
                                                            <input v-model="ajustesNomina[empleado.id].prestamo_otorgado" type="number" step="0.01" min="0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block text-[10px] font-bold uppercase leading-tight text-blue-700">Adeudo</span>
                                                            <input v-model="ajustesNomina[empleado.id].prestamo_descuento" type="number" step="0.01" min="0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                        </label>
                                                    </div>
                                                </section>

                                                <section class="rounded-lg border border-amber-100 bg-amber-50/70 p-3">
                                                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2 text-sm font-black text-amber-900">
                                                            <i class="ti ti-calendar-exclamation" aria-hidden="true"></i>
                                                            Faltas y coberturas
                                                        </div>
                                                        <span class="rounded-full border border-rose-200 bg-white px-2 py-1 text-[11px] font-bold text-rose-700">
                                                            {{ resumenNomina(empleado).faltas_detectadas || 0 }} falta(s) reales
                                                        </span>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-2 text-[11px] font-bold md:grid-cols-4">
                                                        <span class="rounded-md bg-white px-2 py-1 text-rose-700">{{ faltasConDescuentoPreview(empleado) }} desc.</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-blue-700">{{ faltasPagadasPreview(empleado) }} con horas</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-emerald-700">{{ faltasCubiertasVacacionesPreview(empleado) }} vac.</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-violet-700">{{ faltasCubiertasIncapacidadPreview(empleado) }} incap.</span>
                                                    </div>

                                                    <div class="mt-2 grid grid-cols-2 gap-2 lg:grid-cols-4">
                                                        <label class="block">
                                                            <span class="mb-1 block min-h-6 text-[10px] font-bold uppercase leading-tight text-amber-700">Sin descuento / adeuda h</span>
                                                            <input v-model="ajustesNomina[empleado.id].faltas_pagadas" type="number" step="1" min="0" :max="resumenNomina(empleado).faltas_detectadas || 0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block min-h-6 text-[10px] font-bold uppercase leading-tight text-emerald-700">Pagar con vacaciones</span>
                                                            <input v-model="ajustesNomina[empleado.id].faltas_cubiertas_vacaciones" type="number" step="1" min="0" :max="Math.max(0, Number(resumenNomina(empleado).faltas_detectadas || 0) - faltasPagadasPreview(empleado))" class="field-input-soft px-2 py-1.5 text-xs" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block min-h-6 text-[10px] font-bold uppercase leading-tight text-violet-700">Pagar con incapacidad</span>
                                                            <input v-model="ajustesNomina[empleado.id].faltas_cubiertas_incapacidad" type="number" step="1" min="0" :max="Math.max(0, Number(resumenNomina(empleado).faltas_detectadas || 0) - faltasPagadasPreview(empleado) - faltasCubiertasVacacionesPreview(empleado))" class="field-input-soft px-2 py-1.5 text-xs" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block min-h-6 text-[10px] font-bold uppercase leading-tight text-amber-700">Hrs extra a tomar</span>
                                                            <input v-model="ajustesNomina[empleado.id].horas_adeudo_descontadas" type="number" step="0.5" min="0" :max="resumenNomina(empleado).horas_extra_detectadas || 0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                        </label>
                                                    </div>

                                                    <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] font-bold lg:grid-cols-4">
                                                        <span class="rounded-md bg-white px-2 py-1 text-slate-600">Genera {{ horas(horasAdeudoGeneradasPreview(empleado)) }} h</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-slate-600">Extra {{ horas(resumenNomina(empleado).horas_extra_detectadas) }} h</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-emerald-700">Paga {{ horas(horasExtraPagadasPreview(empleado)) }} h</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-amber-700">Saldo {{ horas(saldoHorasPreview(empleado)) }} h</span>
                                                    </div>
                                                    <div v-if="Number(resumenNomina(empleado).horas_extra_miercoles_anterior || 0) > 0" class="mt-2 text-[11px] font-semibold text-amber-800">
                                                        Incluye {{ horas(resumenNomina(empleado).horas_extra_miercoles_anterior) }} h del miercoles anterior.
                                                    </div>
                                                    <div v-if="horasAdeudoMiercolesAnterior(empleado) > 0" class="mt-2 text-[11px] font-semibold text-rose-700">
                                                        Adeuda {{ horas(horasAdeudoMiercolesAnterior(empleado)) }} h del miercoles anterior.
                                                    </div>
                                                </section>

                                                <section class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                                    <div class="mb-2 flex items-center gap-2 text-sm font-black text-slate-800">
                                                        <i class="ti ti-adjustments-dollar" aria-hidden="true"></i>
                                                        Otros
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2 xl:grid-cols-1">
                                                        <label class="block">
                                                            <span class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Desc. extra</span>
                                                            <input v-model="ajustesNomina[empleado.id].deduccion_manual" type="number" step="0.01" min="0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Vac. adicionales</span>
                                                            <input v-model="ajustesNomina[empleado.id].dias_vacaciones_adicionales" type="number" step="0.5" min="0" class="field-input-soft px-2 py-1.5 text-xs" />
                                                        </label>
                                                    </div>
                                                    <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] font-bold">
                                                        <span class="rounded-md bg-white px-2 py-1 text-rose-700">{{ faltasConDescuentoPreview(empleado) }} falta(s) desc.</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-amber-700">{{ resumenNomina(empleado).minutos_tarde_descontables || 0 }} min ret.</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-violet-700">{{ horas(diasIncapacidadPreview(empleado)) }} incap.</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-violet-700">${{ moneda(pagoIncapacidadPreview(empleado)) }}</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-blue-700">{{ horas(diasVacacionesPreview(empleado)) }} dia(s) vac.</span>
                                                        <span class="rounded-md bg-white px-2 py-1 text-emerald-700">${{ moneda(pagoVacacionesPreview(empleado)) }}</span>
                                                    </div>
                                                    <button type="button" @click="guardarAjustes(empleado)" class="mt-2 inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white transition hover:bg-slate-800">
                                                        <i class="ti ti-device-floppy" aria-hidden="true"></i>
                                                        {{ guardandoAjuste === empleado.id ? 'Guardando...' : 'Guardar ajustes' }}
                                                    </button>
                                                </section>
                                            </div>
                                        </div>
                                    </article>
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
