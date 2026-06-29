<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    empleados: Array,
    historial: Array,
    semanaActual: Number,
    semanasDisponibles: Array,
    fechaCorteActual: String
});
const page = usePage();
const canManage = computed(() => page.props.auth?.can?.['nominas.manage'] ?? false);
const canPay = computed(() => page.props.auth?.can?.['nominas.pay'] ?? false);
const canExport = computed(() => page.props.auth?.can?.['nominas.export'] ?? false);

const searchQuery = ref('');
const historialSearch = ref('');
const showToast = ref(false);
const toastTitle = ref('');
const toastMessage = ref('');

const ajustesNomina = ref({});
const guardandoAjuste = ref(null);
const guardandoImss = ref(null);
const pagandoMasivo = ref(null);
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

const monedaFirmada = (valor) => {
    const monto = numero(valor);
    const absoluto = Math.abs(monto).toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return `${monto < 0 ? '-' : ''}$${absoluto}`;
};

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
            deposito_imss: valorDecimal(ajuste.deposito_imss),
        };
    });

    ajustesNomina.value = actuales;
};

watch(() => props.empleados, inicializarAjustes, { immediate: true, deep: true });

const ajustesCalculoEmpleado = (empleado) => {
    const { deposito_imss, ...ajustesCalculo } = ajustesNomina.value[empleado.id] || {};

    return ajustesCalculo;
};

const parametrosNomina = (empleado) => ({
    empleado_id: empleado.id,
    fecha_corte: selectedCorte.value,
    ...ajustesCalculoEmpleado(empleado),
});

const resumenNomina = (empleado) => empleado.ajustes_nomina || {};
const asistenciaPendiente = (empleado) => Boolean(resumenNomina(empleado).asistencia_pendiente_captura);
const puedeCalcularNomina = (empleado) => !asistenciaPendiente(empleado);
const mensajeCapturaAsistencia = (empleado) => {
    const resumen = resumenNomina(empleado);
    return resumen.mensaje_captura_asistencia || 'Asistencia pendiente de captura.';
};

const depositoImssActual = (empleado) => {
    const ajuste = ajustesNomina.value[empleado.id] || {};

    return numero(ajuste.deposito_imss ?? resumenNomina(empleado).deposito_imss);
};

const diferenciaImssPreview = (empleado) => {
    const deposito = depositoImssActual(empleado);

    return deposito > 0 ? numero(resumenNomina(empleado).pago_neto) - deposito : 0;
};

const sumaTotalDepositosImssPreview = (empleado) => {
    const deposito = depositoImssActual(empleado);

    return deposito > 0 ? deposito + diferenciaImssPreview(empleado) : 0;
};

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
    const restantes = Math.max(0, numero(resumenNomina(empleado).faltas_detectadas) - faltasPagadasPreview(empleado) - faltasCubiertasVacacionesPreview(empleado));
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
    ...ajustesCalculoEmpleado(empleado),
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

const payloadDiferenciaImss = (empleado) => ({
    fecha_corte: selectedCorte.value,
    deposito_imss: depositoImssActual(empleado),
});

const guardarDiferenciaImss = (empleado) => {
    guardandoImss.value = empleado.id;
    router.put(route('nominas.diferencia-imss.update', empleado.id), payloadDiferenciaImss(empleado), {
        preserveScroll: true,
        onFinish: () => {
            guardandoImss.value = null;
        },
    });
};

const empleadosFiltrados = computed(() => {
    let resultado = [...props.empleados];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        resultado = resultado.filter(emp => {
            return emp.nombre_completo.toLowerCase().includes(query) ||
                   (emp.numero_empleado && emp.numero_empleado.toLowerCase().includes(query));
        });
    }

    if (filtroEstado.value === 'pendiente') {
        resultado = resultado.filter(emp => !emp.pagado);
    } else if (filtroEstado.value === 'liquidado') {
        resultado = resultado.filter(emp => emp.pagado);
    }

    if (filtroBanco.value !== 'todos') {
        resultado = resultado.filter(emp => nombreBancoEmpleado(emp) === filtroBanco.value);
    }

    resultado.sort((a, b) => {
        if (criterioOrden.value === 'num_asc' || criterioOrden.value === 'num_desc') {
            const numA = parseInt(a.numero_empleado, 10);
            const numB = parseInt(b.numero_empleado, 10);

            if (isNaN(numA) && isNaN(numB)) return 0;
            if (isNaN(numA)) return 1;
            if (isNaN(numB)) return -1;

            return criterioOrden.value === 'num_asc' ? numA - numB : numB - numA;
        } 
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
const empleadosSeleccionados = computed(() => {
    const ids = new Set(selectedEmpleadoIds.value);
    return props.empleados.filter((empleado) => ids.has(empleado.id));
});
const seleccionadosPendientesCaptura = computed(() => empleadosSeleccionados.value.filter((empleado) => asistenciaPendiente(empleado)).length);
const seleccionadosConRecibo = computed(() => empleadosSeleccionados.value.filter((empleado) => empleado.nomina_generada && puedeCalcularNomina(empleado)));
const seleccionadosPendientes = computed(() => seleccionadosConRecibo.value.filter((empleado) => !empleado.pagado).length);
const seleccionadosLiquidados = computed(() => seleccionadosConRecibo.value.filter((empleado) => empleado.pagado).length);
const seleccionadosSinRecibo = computed(() => Math.max(0, seleccionadosCount.value - seleccionadosConRecibo.value.length));
const empleadosConImss = computed(() => props.empleados.filter((empleado) => puedeCalcularNomina(empleado) && numero(resumenNomina(empleado).deposito_imss) > 0).length);
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
    toggleEmpleadosGrupo(empleadosBanco, !empleadosGrupoSeleccionados(empleadosBanco));
};

const limpiarSeleccion = () => {
    selectedEmpleadoIds.value = [];
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

const urlDiferenciaImss = () => route('nominas.diferencia-imss', {
    semana: numeroSemanaSeleccionada.value,
    fecha_corte: selectedCorte.value,
});

watch(() => props.empleados, (empleados) => {
    const idsActuales = new Set(empleados.map((empleado) => empleado.id));
    selectedEmpleadoIds.value = selectedEmpleadoIds.value.filter((id) => idsActuales.has(id));
}, { deep: true });

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

const copiarTextoSeguro = async (texto) => {
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(texto);
        return true;
    }

    const textarea = document.createElement('textarea');
    textarea.value = texto;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '-9999px';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    try {
        return document.execCommand('copy');
    } finally {
        document.body.removeChild(textarea);
    }
};

const copiarCuenta = async (banco, cuenta) => {
    if (!cuenta) return;
    const texto = `${banco ? banco + ' - ' : ''}${cuenta}`;

    try {
        const copiado = await copiarTextoSeguro(cuenta);
        toastTitle.value = 'Cuenta copiada';
        toastMessage.value = copiado ? texto : 'No se pudo copiar automaticamente. Selecciona la cuenta manualmente.';
        showToast.value = true;
        setTimeout(() => { showToast.value = false; }, 3500);
    } catch (error) {
        toastTitle.value = 'No se pudo copiar';
        toastMessage.value = 'El navegador bloqueo el portapapeles. Selecciona la cuenta manualmente.';
        showToast.value = true;
        setTimeout(() => { showToast.value = false; }, 3500);
    }
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
        ? '¿Marcar esta nómina como pendiente?\n\nSe revertirán el préstamo y las vacaciones aplicadas.'
        : '¿Marcar esta nómina como pagada?\n\nSe aplicarán préstamo y vacaciones al saldo del empleado.';

    if (!confirm(mensaje)) {
        return;
    }

    const payload = !pagadoActual && empleado ? payloadAjustesEmpleado(empleado) : {};

    router.put(route('nominas.pagar', nominaId), payload, {
        preserveScroll: true
    });
};

const cambiarPagosMasivos = (accion) => {
    if (seleccionadosCount.value <= 0 || pagandoMasivo.value) {
        return;
    }

    const objetivo = accion === 'pagar' ? seleccionadosPendientes.value : seleccionadosLiquidados.value;

    if (objetivo <= 0) {
        alert(accion === 'pagar'
            ? 'No hay nominas pendientes generadas dentro de la seleccion.'
            : 'No hay nominas liquidadas dentro de la seleccion.'
        );
        return;
    }

    const textoAccion = accion === 'pagar' ? 'marcar como liquidadas' : 'revertir a pendientes';
    const detalleSinRecibo = seleccionadosSinRecibo.value > 0
        ? `\n\n${seleccionadosSinRecibo.value} empleado(s) no tienen recibo generado y se omitiran.`
        : '';

    if (!confirm(`Se van a ${textoAccion} ${objetivo} nomina(s).${detalleSinRecibo}\n\n¿Continuamos?`)) {
        return;
    }

    pagandoMasivo.value = accion;

    router.put(route('nominas.pagos-masivos'), {
        fecha_corte: selectedCorte.value,
        empleado_ids: selectedEmpleadoIds.value,
        accion,
    }, {
        preserveScroll: true,
        onFinish: () => {
            pagandoMasivo.value = null;
        },
    });
};
</script>

<template>
    <Head title="Control de Nóminas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-4">
                <Link :href="route('dashboard')" class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 transition-all hover:bg-blue-50 hover:text-blue-600 border border-slate-200">
                    <i class="ti ti-arrow-left text-2xl"></i>
                </Link>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Pagos y Recibos</p>
                    <h2 class="font-['Sora'] text-2xl font-extrabold text-slate-900">Control de Nóminas</h2>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <section class="rounded-3xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5 sm:px-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl shadow-inner text-2xl bg-teal-100 text-teal-600 border border-teal-200">
                            <i class="ti ti-calendar-stats"></i>
                        </div>
                        <div>
                            <h3 class="font-['Sora'] text-lg font-bold text-slate-900">Periodo a procesar</h3>
                            <p class="text-xs font-medium text-slate-500">Selecciona la semana y localiza empleados para emitir recibos.</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8 space-y-6 bg-white">
                    <div class="flex flex-col lg:flex-row gap-4">
                        
                        <div class="relative flex-1 min-w-[200px]">
                            <select v-model="selectedCorte" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-4 pr-10 text-sm font-bold text-slate-900 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 appearance-none transition-all cursor-pointer">
                                <option v-for="sem in semanasDisponibles" :key="sem.fecha_corte" :value="sem.fecha_corte">
                                    {{ sem.etiqueta }}
                                </option>
                            </select>
                            <i class="ti ti-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>

                        <div class="flex rounded-xl bg-slate-100/80 p-1">
                            <button @click="filtroEstado = 'todos'" :class="filtroEstado === 'todos' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 rounded-lg px-4 py-1.5 text-[11px] font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                                <i class="ti ti-layout-grid"></i> Todos
                            </button>
                            <button @click="filtroEstado = 'pendiente'" :class="filtroEstado === 'pendiente' ? 'bg-white text-amber-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 rounded-lg px-4 py-1.5 text-[11px] font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                                <i class="ti ti-clock-dollar"></i> Pendientes
                            </button>
                            <button @click="filtroEstado = 'liquidado'" :class="filtroEstado === 'liquidado' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 rounded-lg px-4 py-1.5 text-[11px] font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                                <i class="ti ti-circle-check"></i> Liquidados
                            </button>
                        </div>

                        <div class="relative flex-1 min-w-[200px]">
                            <select v-model="criterioOrden" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-4 pr-10 text-sm font-bold text-slate-900 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 appearance-none transition-all cursor-pointer">
                                <option value="asc">Nombre (A - Z)</option>
                                <option value="desc">Nombre (Z - A)</option>
                                <option value="num_asc">No. Emp. (Menor a Mayor)</option>
                                <option value="num_desc">No. Emp. (Mayor a Menor)</option>
                            </select>
                            <i class="ti ti-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>

                        <div class="relative flex-1 min-w-[200px]">
                            <select v-model="filtroBanco" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-4 pr-10 text-sm font-bold text-slate-900 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 appearance-none transition-all cursor-pointer">
                                <option value="todos">Todos los bancos</option>
                                <option v-for="banco in bancosDisponibles" :key="banco" :value="banco">
                                    {{ banco }}
                                </option>
                            </select>
                            <i class="ti ti-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="flex flex-col xl:flex-row justify-between gap-4 border-t border-slate-100 pt-6">
                        <div class="relative w-full xl:w-80">
                            <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                            <input v-model="searchQuery" type="text" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm font-semibold text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="Buscar empleado o ID..." />
                        </div>

                        <div class="flex flex-wrap gap-2 sm:gap-3">
                            <a v-if="canExport" :href="route('nominas.reporte', { semana: numeroSemanaSeleccionada, fecha_corte: selectedCorte })" target="_blank" class="flex items-center gap-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all">
                                <i class="ti ti-file-spreadsheet text-lg"></i> Excel Global
                            </a>

                            <a v-if="canExport" :href="urlDiferenciaImss()" target="_blank" class="flex items-center gap-2 rounded-xl bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all">
                                <i class="ti ti-report-money text-lg"></i> Diferencia IMSS
                                <span class="rounded-md bg-white px-1.5 py-0.5 text-[10px] text-cyan-600">{{ empleadosConImss }}</span>
                            </a>
                            
                            <a v-if="canExport && seleccionadosCount > 0" :href="urlRecibosMasivos(false)" target="_blank" class="flex items-center gap-2 rounded-xl bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100 px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all">
                                <i class="ti ti-printer text-lg"></i> PDF Seleccionados ({{ seleccionadosCount }})
                            </a>
                            <button v-else-if="canExport" disabled class="flex items-center gap-2 rounded-xl bg-slate-50 text-slate-400 border border-slate-200 px-4 py-2.5 text-xs font-black uppercase tracking-wider cursor-not-allowed">
                                <i class="ti ti-printer text-lg"></i> PDF Seleccionados
                            </button>

                            <button v-if="seleccionadosCount > 0" @click="limpiarSeleccion" type="button" class="flex items-center gap-2 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all">
                                <i class="ti ti-square-x text-lg"></i> Limpiar seleccion
                            </button>

                            <a v-if="canExport" :href="urlRecibosMasivos(true)" target="_blank" class="flex items-center gap-2 rounded-xl bg-slate-900 text-white border border-slate-800 hover:bg-slate-800 hover:shadow-lg hover:shadow-slate-900/20 px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all">
                                <i class="ti ti-printer text-lg"></i> PDF Todos
                            </a>
                        </div>
                    </div>

                    <div v-if="seleccionadosCount > 0" class="rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-emerald-50 p-4 shadow-sm">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                                    <i class="ti ti-checklist text-xl"></i>
                                </span>
                                <div>
                                    <p class="text-sm font-black text-slate-900">{{ seleccionadosCount }} empleado(s) seleccionados</p>
                                    <p class="text-xs font-semibold text-slate-500">
                                        {{ seleccionadosPendientes }} pendiente(s) · {{ seleccionadosLiquidados }} liquidado(s)
                                        <span v-if="seleccionadosPendientesCaptura > 0"> · {{ seleccionadosPendientesCaptura }} pendiente(s) de captura</span>
                                        <span v-if="seleccionadosSinRecibo > 0"> · {{ seleccionadosSinRecibo }} sin recibo</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-if="canPay"
                                    type="button"
                                    @click="cambiarPagosMasivos('pagar')"
                                    :disabled="seleccionadosPendientes <= 0 || pagandoMasivo"
                                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-600 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-sm transition-all hover:-translate-y-0.5 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400 disabled:hover:translate-y-0"
                                >
                                    <i class="ti ti-circle-check text-lg"></i>
                                    {{ pagandoMasivo === 'pagar' ? 'Liquidando...' : 'Liquidar selección' }}
                                </button>
                                <button
                                    v-if="canPay"
                                    type="button"
                                    @click="cambiarPagosMasivos('revertir')"
                                    :disabled="seleccionadosLiquidados <= 0 || pagandoMasivo"
                                    class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-white px-4 py-2.5 text-xs font-black uppercase tracking-wider text-amber-700 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-amber-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400 disabled:hover:translate-y-0"
                                >
                                    <i class="ti ti-rotate-clockwise text-lg"></i>
                                    {{ pagandoMasivo === 'revertir' ? 'Revirtiendo...' : 'Revertir selección' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50/50 p-6 sm:p-8 border-t border-slate-100">
                    <div v-if="Object.keys(empleadosAgrupados).length === 0" class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-slate-400">
                        <i class="ti ti-file-x text-5xl mb-3 text-slate-300"></i>
                        <p class="font-bold">No se encontraron empleados para ese filtro.</p>
                    </div>

                    <div v-else>
                        <div v-for="(empleadosBanco, nombreBanco) in empleadosAgrupados" :key="nombreBanco" class="mb-12 last:mb-0">
                            
                            <div :class="['sticky top-4 z-20 mb-6 overflow-hidden rounded-2xl border px-5 py-4 shadow-md backdrop-blur-md transition-all', temaBanco(nombreBanco).header]">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div :class="['flex h-12 w-12 items-center justify-center rounded-xl shadow-sm border text-2xl', temaBanco(nombreBanco).icon]">
                                            <i class="ti ti-building-bank"></i>
                                        </div>
                                        <div>
                                            <h4 :class="['text-xl font-black tracking-tight', temaBanco(nombreBanco).title]">{{ nombreBanco }}</h4>
                                            <div class="flex items-center gap-2 mt-1">
                                                <div :class="['h-1.5 w-16 rounded-full', temaBanco(nombreBanco).stripe]"></div>
                                                <span :class="['rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider', temaBanco(nombreBanco).badge]">
                                                    {{ empleadosBanco.length }} cuenta(s)
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="seleccionarBanco(empleadosBanco)" :class="['flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black uppercase tracking-wider shadow-sm transition-all hover:-translate-y-0.5', temaBanco(nombreBanco).button]">
                                            <i :class="['ti text-base', empleadosGrupoSeleccionados(empleadosBanco) ? 'ti-square-x' : 'ti-checks']"></i>
                                            {{ empleadosGrupoSeleccionados(empleadosBanco) ? 'Quitar grupo' : 'Seleccionar grupo' }}
                                        </button>
                                        <a v-if="canExport" :href="urlRecibosGrupo(empleadosBanco)" target="_blank" :class="['flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black uppercase tracking-wider shadow-sm transition-all hover:-translate-y-0.5', temaBanco(nombreBanco).pdf]">
                                            <i class="ti ti-printer text-base"></i> PDF Grupo
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                                <article v-for="empleado in empleadosBanco" :key="`compacto-${empleado.id}`" 
                                    :class="['rounded-3xl border bg-white shadow-sm transition-all duration-300 hover:shadow-xl hover:border-blue-200 overflow-hidden flex flex-col', tieneReglaEspecial(empleado) ? 'border-amber-200' : 'border-slate-200/60']">
                                    
                                    <div class="border-b border-slate-100 bg-slate-50/50 p-5 flex flex-wrap gap-4 items-start justify-between">
                                        <div class="flex items-start gap-4">
                                            <div class="pt-1">
                                                <input type="checkbox" :checked="empleadoSeleccionado(empleado.id)" @change="toggleEmpleado(empleado.id, $event.target.checked)" class="h-5 w-5 rounded-md border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer shadow-sm" :title="`Seleccionar ${empleado.nombre_completo}`" />
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-3 mb-1">
                                                    <span :class="['flex h-6 items-center justify-center rounded-lg border px-2 text-[10px] font-black uppercase tracking-widest', claseNumeroNomina(empleado)]">
                                                        #{{ empleado.numero_empleado || 'S/N' }}
                                                    </span>
                                                    <h4 class="text-base font-black uppercase text-slate-900 leading-tight">{{ empleado.nombre_completo }}</h4>
                                                </div>
                                                <p class="text-xs font-bold text-slate-500 mb-2">{{ empleado.puesto || 'Sin puesto asignado' }}</p>
                                                
                                                <div v-if="tieneReglaEspecial(empleado)" class="flex flex-wrap gap-1">
                                                    <span v-for="regla in reglasEspecialesEmpleado(empleado)" :key="regla.texto" :class="['rounded-md border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm', regla.clase]">
                                                        {{ regla.texto }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-end gap-2">
                                            <div v-if="asistenciaPendiente(empleado)" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-amber-700 shadow-sm">
                                                <i class="ti ti-clipboard-list"></i> Pendiente captura
                                            </div>
                                            <div v-else-if="!empleado.nomina_generada" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400 shadow-sm">
                                                <i class="ti ti-clock-pause"></i> No generada
                                            </div>
                                            <div v-else class="flex flex-col items-end gap-1">
                                                <span :class="['rounded-lg px-3 py-1.5 text-[10px] font-black uppercase tracking-wider shadow-sm flex items-center gap-1.5 border', empleado.pagado ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200']">
                                                    <i :class="empleado.pagado ? 'ti ti-circle-check' : 'ti ti-clock-dollar'"></i>
                                                    {{ empleado.pagado ? 'Liquidado' : 'Pendiente' }}
                                                </span>
                                                <button v-if="canPay" @click="cambiarEstadoPago(empleado.nomina_id, empleado.pagado, empleado)" class="text-[9px] font-bold text-slate-400 hover:text-blue-600 underline underline-offset-2 transition-colors">
                                                    {{ empleado.pagado ? 'Revertir pago' : 'Marcar pagado y saldar' }}
                                                </button>
                                            </div>
                                            <button v-if="canPay && asistenciaPendiente(empleado) && empleado.pagado" @click="cambiarEstadoPago(empleado.nomina_id, empleado.pagado, empleado)" class="text-[9px] font-bold text-amber-600 hover:text-amber-700 underline underline-offset-2 transition-colors">
                                                Revertir pago anterior
                                            </button>
                                        </div>
                                    </div>

                                    <div class="p-5 flex-1 flex flex-col">
                                        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                                            <div v-if="empleado.numero_cuenta" class="flex flex-col items-start">
                                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">{{ empleado.banco }}</span>
                                                <button @click="copiarCuenta(empleado.banco, empleado.numero_cuenta)" class="mt-0.5 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 group" title="Copiar cuenta">
                                                    <span class="font-mono">{{ empleado.numero_cuenta }}</span>
                                                    <i class="ti ti-copy text-slate-400 group-hover:text-blue-600 transition-colors"></i>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600">
                                                <i class="ti ti-alert-triangle text-base"></i> Sin cuenta
                                            </div>

                                            <div class="flex items-center gap-2 ml-auto">
                                                <button v-if="canExport && asistenciaPendiente(empleado)" type="button" disabled class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-300" title="Captura asistencia primero">
                                                    <i class="ti ti-file-spreadsheet text-lg"></i>
                                                </button>
                                                <a v-else-if="canExport" :href="route('nominas.excel-individual', parametrosNomina(empleado))" class="flex h-9 w-9 items-center justify-center rounded-xl bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 transition-all" title="Descargar Excel">
                                                    <i class="ti ti-file-spreadsheet text-lg"></i>
                                                </a>
                                                <button v-if="canManage && asistenciaPendiente(empleado)" type="button" disabled class="flex cursor-not-allowed items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-black uppercase tracking-wider text-amber-700 shadow-sm">
                                                    <i class="ti ti-clipboard-list text-base"></i>
                                                    Captura pendiente
                                                </button>
                                                <a v-else-if="canManage" :href="route('nominas.generar', parametrosNomina(empleado))" target="_blank" @click="marcarComoGenerado(empleado)" :class="['flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black uppercase tracking-wider shadow-sm transition-all', empleado.nomina_generada ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' : 'bg-slate-900 text-white border border-slate-800 hover:bg-slate-800 hover:shadow-md']">
                                                    <i class="ti ti-printer text-base"></i>
                                                    {{ empleado.nomina_generada ? 'Regenerar' : 'Crear recibo' }}
                                                </a>
                                            </div>
                                        </div>

                                        <div v-if="ajustesNomina[empleado.id]" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 flex-1">
                                            <div v-if="asistenciaPendiente(empleado)" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-800">
                                                <div class="flex items-start gap-2">
                                                    <i class="ti ti-alert-triangle mt-0.5 text-base"></i>
                                                    <div>
                                                        <p class="font-black uppercase tracking-wider">Nomina sin calcular</p>
                                                        <p class="mt-1">{{ mensajeCapturaAsistencia(empleado) }}</p>
                                                        <p class="mt-1 text-[10px] uppercase tracking-wider text-amber-700">
                                                            Sin registros de asistencia en el periodo.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
                                                <div class="rounded-xl border border-emerald-100 bg-white p-2.5 shadow-sm text-center">
                                                    <span class="block text-[9px] font-black uppercase tracking-wider text-emerald-500 mb-0.5">Pago Neto</span>
                                                    <span v-if="asistenciaPendiente(empleado)" class="text-sm font-black text-amber-700">Pendiente</span>
                                                    <span v-else class="text-sm font-black text-emerald-700">${{ moneda(resumenNomina(empleado).pago_neto) }}</span>
                                                </div>
                                                <div class="rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm text-center">
                                                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">Deuda Actual</span>
                                                    <span class="text-sm font-black text-slate-700">${{ moneda(resumenNomina(empleado).saldo_prestamo_actual ?? empleado.saldo_prestamo) }}</span>
                                                </div>
                                                <div class="rounded-xl border border-blue-100 bg-white p-2.5 shadow-sm text-center">
                                                    <span class="block text-[9px] font-black uppercase tracking-wider text-blue-500 mb-0.5">Deuda después</span>
                                                    <span class="text-sm font-black text-blue-700">${{ moneda(deudaDespues(empleado)) }}</span>
                                                </div>
                                                <div class="rounded-xl border border-amber-100 bg-white p-2.5 shadow-sm text-center">
                                                    <span class="block text-[9px] font-black uppercase tracking-wider text-amber-500 mb-0.5">Horas pendientes</span>
                                                    <span class="text-sm font-black text-amber-700">{{ horas(saldoHorasPreview(empleado)) }} h</span>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                                <section class="rounded-xl border border-blue-100 bg-blue-50/50 p-3">
                                                    <div class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-wider text-blue-800">
                                                        <i class="ti ti-cash-banknote text-base"></i> Préstamo
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-3">
                                                        <label class="block">
                                                            <span class="mb-1 block text-[9px] font-bold uppercase tracking-wider text-blue-600">Entregar hoy</span>
                                                            <input v-model="ajustesNomina[empleado.id].prestamo_otorgado" type="number" step="0.01" min="0" class="w-full rounded-lg border border-blue-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all shadow-sm" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block text-[9px] font-bold uppercase tracking-wider text-blue-600">Descontar hoy</span>
                                                            <input v-model="ajustesNomina[empleado.id].prestamo_descuento" type="number" step="0.01" min="0" class="w-full rounded-lg border border-blue-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all shadow-sm" />
                                                        </label>
                                                    </div>
                                                </section>

                                                <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                                    <div class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-wider text-slate-700">
                                                        <i class="ti ti-adjustments-dollar text-base"></i> Otros Ajustes
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-3">
                                                        <label class="block">
                                                            <span class="mb-1 block text-[9px] font-bold uppercase tracking-wider text-slate-500">Desc. manual</span>
                                                            <input v-model="ajustesNomina[empleado.id].deduccion_manual" type="number" step="0.01" min="0" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all shadow-sm" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block text-[9px] font-bold uppercase tracking-wider text-slate-500">+ Días vac.</span>
                                                            <input v-model="ajustesNomina[empleado.id].dias_vacaciones_adicionales" type="number" step="0.5" min="0" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all shadow-sm" />
                                                        </label>
                                                    </div>
                                                </section>

                                                <section class="lg:col-span-2 rounded-xl border border-cyan-100 bg-cyan-50/50 p-3">
                                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-cyan-800">
                                                            <i class="ti ti-report-money text-base"></i> Diferencia IMSS
                                                        </div>
                                                        <span class="rounded-md bg-white border border-cyan-100 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-cyan-700 shadow-sm">
                                                            Solo Excel
                                                        </span>
                                                    </div>

                                                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                                        <label class="block min-w-0">
                                                            <span class="mb-1 block text-[9px] font-bold uppercase tracking-wider text-cyan-700">Depósito IMSS</span>
                                                            <input v-model="ajustesNomina[empleado.id].deposito_imss" type="number" step="0.01" min="0" :disabled="!canManage || asistenciaPendiente(empleado)" class="w-full min-w-0 rounded-lg border border-cyan-200 bg-white px-3 py-2 text-sm font-bold text-slate-800 shadow-sm transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 disabled:bg-slate-100 disabled:text-slate-400" />
                                                        </label>
                                                        <div class="min-w-0 rounded-lg border border-cyan-100 bg-white px-3 py-2.5 shadow-sm">
                                                            <span class="block text-[9px] font-black uppercase tracking-wider text-cyan-500">Diferencia semana</span>
                                                            <span class="block truncate text-base font-black leading-tight text-cyan-800">{{ monedaFirmada(diferenciaImssPreview(empleado)) }}</span>
                                                        </div>
                                                        <div class="min-w-0 rounded-lg border border-cyan-100 bg-white px-3 py-2.5 shadow-sm sm:col-span-2 xl:col-span-1">
                                                            <span class="block text-[9px] font-black uppercase tracking-wider text-cyan-500">Suma total</span>
                                                            <span class="block truncate text-base font-black leading-tight text-cyan-800">{{ monedaFirmada(sumaTotalDepositosImssPreview(empleado)) }}</span>
                                                        </div>
                                                        <button
                                                            v-if="canManage"
                                                            type="button"
                                                            @click="guardarDiferenciaImss(empleado)"
                                                            :disabled="guardandoImss === empleado.id || asistenciaPendiente(empleado)"
                                                            class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-cyan-200 bg-white px-3 py-2 text-[10px] font-black uppercase tracking-wider text-cyan-700 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-cyan-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 disabled:hover:translate-y-0 sm:col-span-2 xl:col-span-3"
                                                        >
                                                            <i class="ti ti-device-floppy text-base"></i>
                                                            {{ guardandoImss === empleado.id ? 'Guardando...' : 'Guardar IMSS' }}
                                                        </button>
                                                    </div>
                                                </section>

                                                <section class="lg:col-span-2 rounded-xl border border-amber-100 bg-amber-50/40 p-3">
                                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-amber-800">
                                                            <i class="ti ti-calendar-exclamation text-base"></i> Faltas y forma de pago
                                                        </div>
                                                        <span :class="['rounded-md bg-white px-2.5 py-1 text-[9px] font-black uppercase tracking-wider shadow-sm border', asistenciaPendiente(empleado) ? 'border-amber-200 text-amber-700' : 'border-rose-100 text-rose-600']">
                                                            {{ asistenciaPendiente(empleado) ? 'Sin registros en periodo' : `Reloj detectó ${resumenNomina(empleado).faltas_detectadas || 0} falta(s)` }}
                                                        </span>
                                                    </div>

                                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                                                        <span class="rounded-lg bg-white border border-rose-100 px-2 py-1.5 text-center text-[10px] font-bold text-rose-600 shadow-sm">{{ faltasConDescuentoPreview(empleado) }} falta(s) se descuentan</span>
                                                        <span class="rounded-lg bg-white border border-blue-100 px-2 py-1.5 text-center text-[10px] font-bold text-blue-600 shadow-sm">{{ faltasPagadasPreview(empleado) }} se pagan con horas</span>
                                                        <span class="rounded-lg bg-white border border-emerald-100 px-2 py-1.5 text-center text-[10px] font-bold text-emerald-600 shadow-sm">{{ faltasCubiertasVacacionesPreview(empleado) }} con vacaciones</span>
                                                        <span class="rounded-lg bg-white border border-violet-100 px-2 py-1.5 text-center text-[10px] font-bold text-violet-600 shadow-sm">{{ faltasCubiertasIncapacidadPreview(empleado) }} con incapacidad</span>
                                                    </div>

                                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                                                        <label class="block">
                                                            <span class="mb-1 block text-[9px] font-bold uppercase tracking-wider text-amber-700 leading-tight">Pagar hoy y deber horas</span>
                                                            <input v-model="ajustesNomina[empleado.id].faltas_pagadas" type="number" step="1" min="0" :max="resumenNomina(empleado).faltas_detectadas || 0" class="w-full rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition-all shadow-sm" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block text-[9px] font-bold uppercase tracking-wider text-emerald-700 leading-tight">Cubrir con vacaciones</span>
                                                            <input v-model="ajustesNomina[empleado.id].faltas_cubiertas_vacaciones" type="number" step="1" min="0" :max="Math.max(0, Number(resumenNomina(empleado).faltas_detectadas || 0) - faltasPagadasPreview(empleado))" class="w-full rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all shadow-sm" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block text-[9px] font-bold uppercase tracking-wider text-violet-700 leading-tight">Cubrir con incapacidad</span>
                                                            <input v-model="ajustesNomina[empleado.id].faltas_cubiertas_incapacidad" type="number" step="1" min="0" :max="Math.max(0, Number(resumenNomina(empleado).faltas_detectadas || 0) - faltasPagadasPreview(empleado) - faltasCubiertasVacacionesPreview(empleado))" class="w-full rounded-lg border border-violet-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 transition-all shadow-sm" />
                                                        </label>
                                                        <label class="block">
                                                            <span class="mb-1 block text-[9px] font-bold uppercase tracking-wider text-amber-700 leading-tight">Usar horas extra</span>
                                                            <input v-model="ajustesNomina[empleado.id].horas_adeudo_descontadas" type="number" step="0.5" min="0" :max="resumenNomina(empleado).horas_extra_detectadas || 0" class="w-full rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition-all shadow-sm" />
                                                        </label>
                                                    </div>

                                                    <div class="flex flex-wrap gap-2 text-[9px] font-black uppercase tracking-wider mt-2">
                                                        <span class="rounded-md bg-white border border-slate-200 px-2 py-1 text-slate-500 shadow-sm">Horas que debe: {{ horas(horasAdeudoGeneradasPreview(empleado)) }}h</span>
                                                        <span class="rounded-md bg-white border border-slate-200 px-2 py-1 text-slate-500 shadow-sm">Horas extra detectadas: {{ horas(resumenNomina(empleado).horas_extra_detectadas) }}h</span>
                                                        <span class="rounded-md bg-white border border-emerald-200 px-2 py-1 text-emerald-600 shadow-sm">Horas extra a pagar: {{ horas(horasExtraPagadasPreview(empleado)) }}h</span>
                                                        <span v-if="Number(resumenNomina(empleado).pago_festivo_trabajado || 0) > 0" class="rounded-md bg-white border border-teal-200 px-2 py-1 text-teal-700 shadow-sm">
                                                            Festivo trabajado: ${{ moneda(resumenNomina(empleado).pago_festivo_trabajado) }}
                                                        </span>
                                                        <span v-if="Number(resumenNomina(empleado).dias_festivos_no_trabajados || 0) > 0" class="rounded-md bg-white border border-teal-100 px-2 py-1 text-teal-600 shadow-sm">
                                                            Festivo pagado normal: {{ resumenNomina(empleado).dias_festivos_no_trabajados }}
                                                        </span>
                                                        <span class="rounded-md bg-white border border-amber-200 px-2 py-1 text-amber-600 shadow-sm">Horas pendientes finales: {{ horas(saldoHorasPreview(empleado)) }}h</span>
                                                    </div>

                                                    <div v-if="Number(resumenNomina(empleado).horas_extra_miercoles_anterior || 0) > 0" class="mt-2 text-[10px] font-bold text-amber-700">
                                                        <i class="ti ti-info-circle"></i> Incluye {{ horas(resumenNomina(empleado).horas_extra_miercoles_anterior) }}h del miércoles anterior.
                                                    </div>
                                                    <div v-if="horasAdeudoMiercolesAnterior(empleado) > 0" class="mt-1 text-[10px] font-bold text-rose-600">
                                                        <i class="ti ti-alert-circle"></i> Adeuda {{ horas(horasAdeudoMiercolesAnterior(empleado)) }}h del miércoles anterior.
                                                    </div>
                                                </section>
                                            </div>

                                            <button v-if="canManage" type="button" @click="guardarAjustes(empleado)" :disabled="asistenciaPendiente(empleado)" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-slate-900/20 transition-all hover:bg-slate-800 hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500 disabled:shadow-none disabled:hover:translate-y-0">
                                                <i class="ti ti-device-floppy text-base"></i>
                                                {{ asistenciaPendiente(empleado) ? 'Captura asistencia para calcular' : (guardandoAjuste === empleado.id ? 'Guardando cambios...' : 'Guardar y recalcular ajustes') }}
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5 sm:px-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl shadow-inner text-2xl bg-indigo-100 text-indigo-600 border border-indigo-200">
                            <i class="ti ti-history"></i>
                        </div>
                        <div>
                            <h3 class="font-['Sora'] text-lg font-bold text-slate-900">Historial de Recibos</h3>
                            <p class="text-xs font-medium text-slate-500">Consulta pagos anteriores, estatus y archivos PDF.</p>
                        </div>
                    </div>

                    <div class="relative w-full sm:w-72">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                        <input v-model="historialSearch" type="text" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm font-semibold text-slate-800 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all" placeholder="Buscar empleado o semana..." />
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4">Periodo</th>
                                <th class="px-6 py-4">Colaborador</th>
                                <th class="px-6 py-4 text-center">Estado</th>
                                <th class="px-6 py-4">Monto Neto</th>
                                <th class="px-6 py-4 text-right">Archivo PDF</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="registro in historialFiltrado" :key="registro.id" class="transition-colors hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <div class="font-black text-slate-800">Semana {{ registro.numero_semana }}</div>
                                    <div class="text-[10px] font-bold uppercase text-slate-400 mt-0.5">{{ registro.fecha_inicio }} al {{ registro.fecha_fin }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 uppercase">{{ registro.empleado?.nombre_completo || 'Sin empleado' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col items-center justify-center gap-1.5">
                                        <span :class="['rounded-lg px-3 py-1.5 text-[10px] font-black uppercase tracking-wider shadow-sm flex items-center gap-1.5 border', registro.pagado ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200']">
                                            <i :class="registro.pagado ? 'ti ti-circle-check' : 'ti ti-clock-dollar'"></i>
                                            {{ registro.pagado ? 'Liquidado' : 'Pendiente' }}
                                        </span>
                                        <button v-if="canPay" @click="cambiarEstadoPago(registro.id, registro.pagado)" class="text-[9px] font-bold text-slate-400 hover:text-indigo-600 underline underline-offset-2 transition-colors" type="button">
                                            {{ registro.pagado ? 'Revertir pago' : 'Marcar pagado' }}
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-lg bg-slate-100 border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-800 shadow-sm">
                                        ${{ Number(registro.pago_neto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a v-if="canExport" :href="route('nominas.descargar', registro.id)" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 shadow-sm transition-all hover:bg-rose-100 hover:border-rose-300">
                                        <i class="ti ti-file-type-pdf text-lg"></i> Recibo
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="historialFiltrado.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i class="ti ti-receipt-off text-5xl mb-3 text-slate-300"></i>
                                        <p class="font-bold">El historial de nóminas está vacío o no hay coincidencias.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="fixed inset-x-4 bottom-4 z-50 transition-all duration-500 sm:inset-x-auto sm:bottom-6 sm:right-6"
             :class="showToast ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0 pointer-events-none'">
            <div class="flex items-center gap-4 rounded-2xl border border-emerald-500/20 bg-slate-900/95 px-5 py-4 text-white shadow-2xl backdrop-blur-md">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400">
                    <i class="ti ti-check text-2xl"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-white">{{ toastTitle }}</h4>
                    <p class="mt-0.5 text-xs font-medium text-slate-300">{{ toastMessage }}</p>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Para esconder las flechitas feas de los inputs tipo number */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  -moz-appearance: textfield;
}
</style>
