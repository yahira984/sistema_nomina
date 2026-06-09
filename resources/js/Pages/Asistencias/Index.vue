<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    empleados: {
        type: Array,
        default: () => [],
    },
    asistencias: {
        type: Array,
        default: () => [],
    },
    previewImportacion: {
        type: Object,
        default: null,
    },
});

const tiposAsistencia = ['Normal', 'Falta', 'Incapacidad', 'Vacaciones'];
const tabActiva = ref(props.previewImportacion ? 'revision' : 'captura');
const busquedaGlobal = ref('');
const busquedaEmpleadoManual = ref('');
const busquedaRevision = ref('');
const editando = ref(false);
const asistenciaId = ref(null);
const archivoInput = ref(null);

const form = useForm({
    empleado_id: '',
    fecha: new Date().toISOString().split('T')[0],
    tipo_asistencia: 'Normal',
    hora_entrada: '08:00',
    hora_salida: '17:00',
});

const formUpload = useForm({
    archivo_reloj: null,
    fecha_inicio: '',
    fecha_fin: '',
});

const formRevision = useForm({
    filas: [],
});

const crearUid = (fila, index) => {
    const empleado = fila.empleado_id || fila.numero_empleado || 'sin-empleado';
    return `${empleado}-${fila.fecha || 'sin-fecha'}-${fila.estado || 'fila'}-${index}`;
};

const clonarFilasRevision = (filas = []) => {
    return filas.map((fila, index) => ({
        aprobado: fila.aprobado ?? true,
        empleado_id: fila.empleado_id ?? null,
        numero_empleado: fila.numero_empleado ?? '',
        nombre_completo: fila.nombre_completo ?? '',
        csv_numero_empleado: fila.csv_numero_empleado ?? fila.numero_empleado ?? '',
        csv_nombre_completo: fila.csv_nombre_completo ?? fila.nombre_completo ?? '',
        fecha: fila.fecha ?? '',
        tipo_asistencia: fila.tipo_asistencia ?? 'Normal',
        hora_entrada: fila.hora_entrada ? fila.hora_entrada.substring(0, 5) : '',
        hora_salida: fila.hora_salida ? fila.hora_salida.substring(0, 5) : '',
        minutos_tarde: fila.minutos_tarde ?? 0,
        horas_trabajadas: fila.horas_trabajadas ?? 0,
        horas_extra: fila.horas_extra ?? 0,
        estado: fila.estado ?? 'detectada',
        mensaje: fila.mensaje ?? '',
        marcas: fila.marcas ?? 0,
        _uid: crearUid(fila, index),
    }));
};

const filasRevision = ref(clonarFilasRevision(props.previewImportacion?.filas || []));

watch(
    () => props.previewImportacion,
    (preview) => {
        filasRevision.value = clonarFilasRevision(preview?.filas || []);
        if (filasRevision.value.length > 0) {
            tabActiva.value = 'revision';
        }
    }
);

const empleadosPorId = computed(() => {
    return new Map(props.empleados.map((empleado) => [Number(empleado.id), empleado]));
});

const asistenciasFiltradas = computed(() => {
    if (!form.empleado_id) {
        return props.asistencias;
    }

    return props.asistencias.filter((asistencia) => Number(asistencia.empleado_id) === Number(form.empleado_id));
});

const empleadosFiltradosGlobal = computed(() => {
    if (!busquedaGlobal.value) return props.empleados;

    const term = busquedaGlobal.value.toLowerCase();
    return props.empleados.filter((empleado) => {
        return empleado.nombre_completo.toLowerCase().includes(term)
            || (empleado.numero_empleado && String(empleado.numero_empleado).toLowerCase().includes(term));
    });
});

const etiquetaEmpleado = (empleado) => {
    return `${empleado.numero_empleado ? '#' + empleado.numero_empleado + ' - ' : ''}${empleado.nombre_completo}`;
};

const empleadosFiltradosManual = computed(() => {
    const term = busquedaEmpleadoManual.value.toLowerCase().trim();
    let resultado = props.empleados;

    if (term) {
        resultado = props.empleados.filter((empleado) => {
            return empleado.nombre_completo.toLowerCase().includes(term)
                || (empleado.numero_empleado && String(empleado.numero_empleado).toLowerCase().includes(term));
        });
    }

    const limitados = resultado.slice(0, 30);
    const seleccionado = props.empleados.find((empleado) => Number(empleado.id) === Number(form.empleado_id));

    if (seleccionado && !limitados.some((empleado) => Number(empleado.id) === Number(seleccionado.id))) {
        return [seleccionado, ...limitados];
    }

    return limitados;
});

const empleadoSeleccionado = computed(() => {
    if (!form.empleado_id) return null;
    return props.empleados.find((empleado) => Number(empleado.id) === Number(form.empleado_id));
});

const sincronizarBusquedaManual = () => {
    if (empleadoSeleccionado.value) {
        busquedaEmpleadoManual.value = etiquetaEmpleado(empleadoSeleccionado.value);
    }
};

const filasRevisionFiltradas = computed(() => {
    if (!busquedaRevision.value) return filasRevision.value;

    const term = busquedaRevision.value.toLowerCase();
    return filasRevision.value.filter((fila) => {
        return String(fila.numero_empleado || '').toLowerCase().includes(term)
            || String(fila.nombre_completo || '').toLowerCase().includes(term)
            || String(fila.fecha || '').toLowerCase().includes(term)
            || String(fila.estado || '').toLowerCase().includes(term);
    });
});

const resumenRevision = computed(() => {
    const filas = filasRevision.value;

    return {
        total: filas.length,
        seleccionadas: filas.filter((fila) => fila.aprobado && fila.empleado_id).length,
        sinRegistro: filas.filter((fila) => fila.estado === 'sin_registro').length,
        noEncontradas: filas.filter((fila) => fila.estado === 'no_encontrado').length,
        actualiza: filas.filter((fila) => fila.estado === 'actualiza').length,
    };
});

const formatoReloj = (horasDecimales) => {
    const valor = Number(horasDecimales || 0);
    const horas = Math.floor(valor);
    const minutos = Math.round((valor - horas) * 60);
    return `${horas} h ${String(minutos).padStart(2, '0')} m`;
};

const formatoFecha = (fecha) => {
    if (!fecha) return '--';
    return new Date(`${fecha}T00:00:00`).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatoHora = (hora) => {
    return hora ? hora.substring(0, 5) : '--';
};

const claseTipo = (tipo) => {
    if (tipo === 'Falta') return 'border-rose-200 bg-rose-50 text-rose-700';
    if (tipo === 'Incapacidad') return 'border-amber-200 bg-amber-50 text-amber-700';
    if (tipo === 'Vacaciones') return 'border-teal-200 bg-teal-50 text-teal-700';
    return 'border-blue-200 bg-blue-50 text-blue-700';
};

const claseEstadoRevision = (estado) => {
    if (estado === 'sin_registro') return 'border-rose-200 bg-rose-50 text-rose-700';
    if (estado === 'no_encontrado') return 'border-slate-300 bg-slate-100 text-slate-700';
    if (estado === 'actualiza') return 'border-amber-200 bg-amber-50 text-amber-700';
    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
};

const textoEstadoRevision = (estado) => {
    if (estado === 'sin_registro') return 'Sin registro';
    if (estado === 'no_encontrado') return 'Sin empleado';
    if (estado === 'actualiza') return 'Actualiza';
    return 'Detectada';
};

const seleccionarArchivo = (event) => {
    formUpload.archivo_reloj = event.target.files[0] || null;
};

const subirArchivo = () => {
    if (!formUpload.archivo_reloj) {
        alert('Selecciona un archivo CSV antes de analizar.');
        return;
    }

    formUpload.post(route('asistencias.importar'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            tabActiva.value = 'revision';
            formUpload.reset('archivo_reloj');
            if (archivoInput.value) {
                archivoInput.value.value = null;
            }
        },
    });
};

const guardarAsistencia = () => {
    if (editando.value) {
        form.put(route('asistencias.update', asistenciaId.value), {
            onSuccess: () => cancelarEdicion(),
        });
        return;
    }

    form.post(route('asistencias.store'), {
        onSuccess: () => {
            form.reset('hora_entrada', 'hora_salida', 'fecha', 'tipo_asistencia');
        },
    });
};

const editarAsistencia = (asistencia) => {
    editando.value = true;
    asistenciaId.value = asistencia.id;
    form.empleado_id = asistencia.empleado_id;
    const empleado = props.empleados.find((item) => Number(item.id) === Number(asistencia.empleado_id));
    busquedaEmpleadoManual.value = empleado ? etiquetaEmpleado(empleado) : '';
    form.fecha = asistencia.fecha;
    form.tipo_asistencia = asistencia.tipo_asistencia || 'Normal';
    form.hora_entrada = asistencia.hora_entrada ? asistencia.hora_entrada.substring(0, 5) : '08:00';
    form.hora_salida = asistencia.hora_salida ? asistencia.hora_salida.substring(0, 5) : '17:00';
    tabActiva.value = 'captura';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelarEdicion = () => {
    editando.value = false;
    asistenciaId.value = null;
    form.reset('hora_entrada', 'hora_salida', 'tipo_asistencia');
};

const eliminarAsistencia = (id) => {
    if (confirm('Estas seguro de eliminar este registro? Puede afectar calculos ya generados.')) {
        router.delete(route('asistencias.destroy', id));
    }
};

const sincronizarEmpleadoFila = (fila) => {
    const empleado = empleadosPorId.value.get(Number(fila.empleado_id));

    if (!empleado) {
        fila.numero_empleado = '';
        fila.nombre_completo = '';
        fila.aprobado = false;
        return;
    }

    fila.numero_empleado = empleado.numero_empleado || '';
    fila.nombre_completo = empleado.nombre_completo;
    fila.aprobado = true;
};

const calcularHorasFilaRevision = (fila) => {
    if (fila.tipo_asistencia !== 'Normal' || !fila.fecha || !fila.hora_entrada || !fila.hora_salida) {
        fila.minutos_tarde = 0;
        fila.horas_trabajadas = 0;
        fila.horas_extra = 0;
        return;
    }

    const entrada = new Date(`${fila.fecha}T${fila.hora_entrada}:00`);
    const salida = new Date(`${fila.fecha}T${fila.hora_salida}:00`);
    const horaOficial = new Date(`${fila.fecha}T08:00:00`);

    if (Number.isNaN(entrada.getTime()) || Number.isNaN(salida.getTime()) || salida <= entrada) {
        fila.minutos_tarde = 0;
        fila.horas_trabajadas = 0;
        fila.horas_extra = 0;
        return;
    }

    fila.minutos_tarde = entrada > horaOficial ? Math.round((entrada - horaOficial) / 60000) : 0;

    if (entrada.getDay() === 6) {
        fila.horas_trabajadas = 0;
        fila.horas_extra = (salida - entrada) / 3600000;
        return;
    }

    const limiteNormal = new Date(`${fila.fecha}T17:30:00`);

    if (salida > limiteNormal) {
        fila.horas_trabajadas = Math.max(0, (limiteNormal - entrada) / 3600000);
        fila.horas_extra = (salida - limiteNormal) / 3600000;
        return;
    }

    fila.horas_trabajadas = (salida - entrada) / 3600000;
    fila.horas_extra = 0;
};

const prepararCambioTipo = (fila) => {
    if (fila.tipo_asistencia !== 'Normal') {
        fila.hora_entrada = '';
        fila.hora_salida = '';
        calcularHorasFilaRevision(fila);
        return;
    }

    fila.hora_entrada = fila.hora_entrada || '08:00';
    fila.hora_salida = fila.hora_salida || '17:00';
    calcularHorasFilaRevision(fila);
};

const descartarFilaRevision = (uid) => {
    filasRevision.value = filasRevision.value.filter((fila) => fila._uid !== uid);
};

const seleccionarRevision = (seleccionar) => {
    filasRevision.value.forEach((fila) => {
        fila.aprobado = seleccionar && Boolean(fila.empleado_id);
    });
};

const aprobarRevision = () => {
    const filas = filasRevision.value
        .filter((fila) => fila.aprobado && fila.empleado_id)
        .map((fila) => ({
            aprobado: true,
            empleado_id: fila.empleado_id,
            fecha: fila.fecha,
            tipo_asistencia: fila.tipo_asistencia,
            hora_entrada: fila.tipo_asistencia === 'Normal' ? (fila.hora_entrada || null) : null,
            hora_salida: fila.tipo_asistencia === 'Normal' ? (fila.hora_salida || null) : null,
        }));

    if (filas.length === 0) {
        alert('No hay filas seleccionadas para aprobar.');
        return;
    }

    formRevision.filas = filas;
    formRevision.post(route('asistencias.importar.aprobar'), {
        preserveScroll: true,
        onSuccess: () => {
            filasRevision.value = [];
            tabActiva.value = 'captura';
        },
    });
};

const descartarRevision = () => {
    if (!confirm('Descartar la revision actual del CSV?')) {
        return;
    }

    router.delete(route('asistencias.importar.descartar'), {
        preserveScroll: true,
        onSuccess: () => {
            filasRevision.value = [];
            tabActiva.value = 'captura';
        },
    });
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
                <div class="tab-strip md:grid-cols-4">
                    <button
                        @click="tabActiva = 'captura'"
                        :class="tabActiva === 'captura' ? 'bg-white text-teal-700 shadow font-bold' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-800'"
                        class="tab-button"
                        type="button"
                    >
                        <i class="ti ti-clock-plus" aria-hidden="true"></i>
                        Captura y Reloj
                    </button>
                    <button
                        @click="tabActiva = 'revision'"
                        :class="tabActiva === 'revision' ? 'bg-white text-blue-700 shadow font-bold' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-800'"
                        class="tab-button"
                        type="button"
                    >
                        <i class="ti ti-file-search" aria-hidden="true"></i>
                        Revision CSV
                        <span v-if="filasRevision.length" class="ml-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                            {{ filasRevision.length }}
                        </span>
                    </button>
                    <button
                        @click="tabActiva = 'vacaciones'"
                        :class="tabActiva === 'vacaciones' ? 'bg-white text-teal-700 shadow font-bold' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-800'"
                        class="tab-button"
                        type="button"
                    >
                        <i class="ti ti-beach" aria-hidden="true"></i>
                        Control Vacaciones
                    </button>
                    <button
                        @click="tabActiva = 'faltas'"
                        :class="tabActiva === 'faltas' ? 'bg-white text-rose-700 shadow font-bold' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-800'"
                        class="tab-button"
                        type="button"
                    >
                        <i class="ti ti-user-x" aria-hidden="true"></i>
                        Control Faltas
                    </button>
                </div>

                <div v-show="tabActiva === 'captura'" class="space-y-8 animate-fade-in">
                    <section class="app-panel border border-emerald-200 bg-emerald-50/50">
                        <form @submit.prevent="subirArchivo" class="grid gap-4 p-5 sm:p-6 lg:grid-cols-[auto_1.5fr_1fr_1fr_auto] lg:items-end">
                            <div class="hidden h-14 w-14 items-center justify-center rounded-xl border border-emerald-200 bg-white text-2xl text-emerald-600 shadow-sm lg:flex">
                                <i class="ti ti-file-spreadsheet" aria-hidden="true"></i>
                            </div>
                            <div>
                                <label class="field-label">Archivo CSV del reloj</label>
                                <input
                                    ref="archivoInput"
                                    type="file"
                                    accept=".csv,.txt"
                                    :disabled="formUpload.processing"
                                    @change="seleccionarArchivo"
                                    class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-700"
                                />
                                <div v-if="formUpload.errors.archivo_reloj" class="mt-2 text-sm font-medium text-rose-600">
                                    {{ formUpload.errors.archivo_reloj }}
                                </div>
                            </div>

                            <div>
                                <label class="field-label">Inicio semana</label>
                                <input v-model="formUpload.fecha_inicio" type="date" class="field-input-soft" />
                            </div>

                            <div>
                                <label class="field-label">Fin semana</label>
                                <input v-model="formUpload.fecha_fin" type="date" class="field-input-soft" />
                                <div v-if="formUpload.errors.fecha_fin" class="mt-2 text-sm font-medium text-rose-600">
                                    {{ formUpload.errors.fecha_fin }}
                                </div>
                            </div>

                            <button type="submit" :disabled="formUpload.processing" class="btn-accent">
                                <svg v-if="!formUpload.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1M12 4v12m0-12 4 4m-4-4-4 4" />
                                </svg>
                                {{ formUpload.processing ? 'Analizando...' : 'Analizar CSV' }}
                            </button>

                            <progress v-if="formUpload.progress" :value="formUpload.progress.percentage" max="100" class="lg:col-span-5 w-full"></progress>
                        </form>
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
                            <button v-if="editando" @click="cancelarEdicion" class="btn-secondary" type="button">Cancelar edicion</button>
                        </div>

                        <div class="bg-slate-50/50 p-5 sm:p-6">
                            <div class="mb-6 grid gap-3 lg:grid-cols-[1fr_1.2fr]">
                                <div>
                                    <label class="field-label text-base">Buscar empleado <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                            <i class="ti ti-search" aria-hidden="true"></i>
                                        </div>
                                        <input
                                            v-model="busquedaEmpleadoManual"
                                            type="text"
                                            :disabled="editando"
                                            class="field-input-soft pl-9 text-base font-semibold text-slate-800"
                                            placeholder="Numero o nombre..."
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label text-base">Selecciona un empleado <span class="text-rose-500">*</span></label>
                                    <select v-model="form.empleado_id" @change="sincronizarBusquedaManual" required :disabled="editando" class="field-input-soft text-base font-bold text-slate-800">
                                        <option value="" disabled>Selecciona un trabajador...</option>
                                        <option v-for="empleado in empleadosFiltradosManual" :key="empleado.id" :value="empleado.id">
                                            {{ etiquetaEmpleado(empleado) }}
                                        </option>
                                    </select>
                                    <p v-if="busquedaEmpleadoManual && empleadosFiltradosManual.length === 0" class="mt-2 text-sm font-semibold text-rose-600">
                                        Sin resultados para esa busqueda.
                                    </p>
                                </div>
                            </div>

                            <div v-if="empleadoSeleccionado" class="mb-6 grid grid-cols-2 gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
                                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Antiguedad</p>
                                    <p class="text-lg font-bold text-slate-800">{{ empleadoSeleccionado.antiguedad_anios }} anio(s)</p>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Vacaciones totales</p>
                                    <p class="text-lg font-bold text-teal-700">
                                        {{ empleadoSeleccionado.fecha_ingreso ? empleadoSeleccionado.dias_vacaciones_totales + ' dias' : 'Falta fecha ing.' }}
                                    </p>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Vacaciones restantes</p>
                                    <p class="text-lg font-bold" :class="empleadoSeleccionado.dias_vacaciones_restantes > 0 ? 'text-emerald-600' : 'text-slate-400'">
                                        {{ empleadoSeleccionado.fecha_ingreso ? empleadoSeleccionado.dias_vacaciones_restantes + ' disp.' : '--' }}
                                    </p>
                                </div>
                                <div class="rounded-lg border border-rose-100 bg-rose-50 p-3">
                                    <p class="text-xs font-semibold uppercase text-rose-600">Faltas totales</p>
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
                                    <input
                                        v-model="form.hora_entrada"
                                        type="time"
                                        :required="form.tipo_asistencia === 'Normal'"
                                        :disabled="form.tipo_asistencia !== 'Normal'"
                                        class="field-input-soft"
                                        :class="form.tipo_asistencia !== 'Normal' ? 'cursor-not-allowed opacity-50' : ''"
                                    />
                                </div>

                                <div>
                                    <label class="field-label" :class="form.tipo_asistencia !== 'Normal' ? 'text-slate-400' : ''">Salida</label>
                                    <input
                                        v-model="form.hora_salida"
                                        type="time"
                                        :required="form.tipo_asistencia === 'Normal'"
                                        :disabled="form.tipo_asistencia !== 'Normal'"
                                        class="field-input-soft"
                                        :class="form.tipo_asistencia !== 'Normal' ? 'cursor-not-allowed opacity-50' : ''"
                                    />
                                </div>

                                <div class="flex justify-end md:col-span-4">
                                    <button type="submit" :disabled="form.processing" :class="editando ? 'btn-warning' : 'btn-accent'">
                                        <svg v-if="!form.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
                                        </svg>
                                        {{ form.processing ? 'Procesando...' : (editando ? 'Actualizar registro' : 'Guardar asistencia') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="app-panel">
                        <div class="panel-header">
                            <div class="flex items-start gap-3">
                                <div class="soft-icon-teal">
                                    <i class="ti ti-list-check text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                <h3 class="panel-title">Ultimos registros</h3>
                                <p class="panel-subtitle">{{ asistenciasFiltradas.length }} asistencia(s) visibles</p>
                                </div>
                            </div>
                            <div v-if="form.empleado_id" class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                                Filtrando a: {{ empleados.find((empleado) => Number(empleado.id) === Number(form.empleado_id))?.nombre_completo }}
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table-premium">
                                <thead>
                                    <tr>
                                        <th>Num</th>
                                        <th>Empleado</th>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th class="text-right">Tarde</th>
                                        <th class="text-right">Normales</th>
                                        <th class="text-right">Extra</th>
                                        <th class="text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="asistencia in asistenciasFiltradas" :key="asistencia.id">
                                        <td class="whitespace-nowrap font-bold text-slate-500">{{ asistencia.empleado?.numero_empleado || 'S/N' }}</td>
                                        <td class="min-w-56 whitespace-nowrap font-semibold text-slate-900">{{ asistencia.empleado?.nombre_completo || 'Sin empleado' }}</td>
                                        <td class="whitespace-nowrap text-slate-600">{{ formatoFecha(asistencia.fecha) }}</td>
                                        <td class="whitespace-nowrap">
                                            <span class="status-pill" :class="claseTipo(asistencia.tipo_asistencia)">
                                                {{ asistencia.tipo_asistencia }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap">{{ formatoHora(asistencia.hora_entrada) }}</td>
                                        <td class="whitespace-nowrap">{{ formatoHora(asistencia.hora_salida) }}</td>
                                        <td class="whitespace-nowrap text-right">{{ asistencia.minutos_tarde }} min</td>
                                        <td class="whitespace-nowrap text-right">{{ formatoReloj(asistencia.horas_trabajadas) }}</td>
                                        <td class="whitespace-nowrap text-right">{{ formatoReloj(asistencia.horas_extra) }}</td>
                                        <td class="whitespace-nowrap text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <button @click="editarAsistencia(asistencia)" class="icon-button" type="button" title="Editar">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                    </svg>
                                                </button>
                                                <button @click="eliminarAsistencia(asistencia.id)" class="icon-button-danger" type="button" title="Eliminar">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M19.228 5.79 18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="asistenciasFiltradas.length === 0">
                                        <td colspan="10" class="empty-state">No hay asistencias registradas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div v-show="tabActiva === 'revision'" class="space-y-6 animate-fade-in">
                    <section class="app-panel">
                        <div class="panel-header">
                            <div class="flex items-start gap-3">
                                <div class="soft-icon-blue">
                                    <i class="ti ti-file-analytics text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                <h3 class="panel-title">Revision de importacion CSV</h3>
                                <p class="panel-subtitle">
                                    {{ filasRevision.length ? 'Ajusta las filas detectadas antes de aprobarlas.' : 'Sube un CSV desde Captura y Reloj.' }}
                                </p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <button @click="seleccionarRevision(true)" :disabled="!filasRevision.length" class="btn-secondary" type="button">
                                    <i class="ti ti-checks" aria-hidden="true"></i>
                                    Seleccionar todo
                                </button>
                                <button @click="seleccionarRevision(false)" :disabled="!filasRevision.length" class="btn-secondary" type="button">
                                    <i class="ti ti-square-x" aria-hidden="true"></i>
                                    Quitar seleccion
                                </button>
                            </div>
                        </div>

                        <div v-if="filasRevision.length" class="space-y-5 p-5 sm:p-6">
                            <div class="grid gap-3 md:grid-cols-5">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <p class="metric-label">Total</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ resumenRevision.total }}</p>
                                </div>
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                                    <p class="metric-label text-emerald-700">Seleccionadas</p>
                                    <p class="mt-2 text-2xl font-bold text-emerald-700">{{ resumenRevision.seleccionadas }}</p>
                                </div>
                                <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">
                                    <p class="metric-label text-rose-700">Sin registro</p>
                                    <p class="mt-2 text-2xl font-bold text-rose-700">{{ resumenRevision.sinRegistro }}</p>
                                </div>
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <p class="metric-label text-amber-700">Actualizan</p>
                                    <p class="mt-2 text-2xl font-bold text-amber-700">{{ resumenRevision.actualiza }}</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white p-4">
                                    <p class="metric-label">Sin empleado</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-700">{{ resumenRevision.noEncontradas }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="text-sm font-medium text-slate-600">
                                    Rango:
                                    <span class="font-bold text-slate-900">
                                        {{ previewImportacion?.resumen?.fecha_inicio ? formatoFecha(previewImportacion.resumen.fecha_inicio) : '--' }}
                                        -
                                        {{ previewImportacion?.resumen?.fecha_fin ? formatoFecha(previewImportacion.resumen.fecha_fin) : '--' }}
                                    </span>
                                </div>
                                <input v-model="busquedaRevision" type="text" class="field-input-soft lg:w-96" placeholder="Buscar en revision..." />
                            </div>

                            <div class="overflow-x-auto rounded-lg border border-slate-200">
                                <table class="table-premium">
                                    <thead>
                                        <tr>
                                            <th class="w-12 text-center">OK</th>
                                            <th>Estado</th>
                                            <th>Empleado</th>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Entrada</th>
                                            <th>Salida</th>
                                            <th class="text-right">Marcas</th>
                                            <th class="text-right">Normales</th>
                                            <th class="text-right">Extra</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="fila in filasRevisionFiltradas" :key="fila._uid" :class="!fila.aprobado ? 'bg-slate-50/80 opacity-75' : ''">
                                            <td class="text-center">
                                                <input
                                                    v-model="fila.aprobado"
                                                    type="checkbox"
                                                    :disabled="!fila.empleado_id"
                                                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                                />
                                            </td>
                                            <td class="min-w-40">
                                                <span class="status-pill" :class="claseEstadoRevision(fila.estado)">
                                                    {{ textoEstadoRevision(fila.estado) }}
                                                </span>
                                                <p class="mt-1 max-w-48 text-xs text-slate-500">{{ fila.mensaje }}</p>
                                            </td>
                                            <td class="min-w-72">
                                                <select v-model.number="fila.empleado_id" @change="sincronizarEmpleadoFila(fila)" class="field-input-soft min-w-64">
                                                    <option :value="null">Sin empleado</option>
                                                    <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">
                                                        {{ empleado.numero_empleado ? '#' + empleado.numero_empleado + ' - ' : '' }}{{ empleado.nombre_completo }}
                                                    </option>
                                                </select>
                                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                                    CSV: {{ fila.csv_numero_empleado || 'S/N' }} - {{ fila.csv_nombre_completo || 'Sin nombre' }}
                                                </p>
                                            </td>
                                            <td class="min-w-40">
                                                <input v-model="fila.fecha" type="date" class="field-input-soft min-w-36" @change="calcularHorasFilaRevision(fila)" />
                                            </td>
                                            <td class="min-w-44">
                                                <select v-model="fila.tipo_asistencia" @change="prepararCambioTipo(fila)" class="field-input-soft min-w-40">
                                                    <option v-for="tipo in tiposAsistencia" :key="tipo" :value="tipo">{{ tipo }}</option>
                                                </select>
                                            </td>
                                            <td class="min-w-32">
                                                <input
                                                    v-model="fila.hora_entrada"
                                                    type="time"
                                                    :disabled="fila.tipo_asistencia !== 'Normal'"
                                                    @change="calcularHorasFilaRevision(fila)"
                                                    class="field-input-soft min-w-28"
                                                    :class="fila.tipo_asistencia !== 'Normal' ? 'cursor-not-allowed opacity-50' : ''"
                                                />
                                            </td>
                                            <td class="min-w-32">
                                                <input
                                                    v-model="fila.hora_salida"
                                                    type="time"
                                                    :disabled="fila.tipo_asistencia !== 'Normal'"
                                                    @change="calcularHorasFilaRevision(fila)"
                                                    class="field-input-soft min-w-28"
                                                    :class="fila.tipo_asistencia !== 'Normal' ? 'cursor-not-allowed opacity-50' : ''"
                                                />
                                            </td>
                                            <td class="whitespace-nowrap text-right font-semibold">{{ fila.marcas }}</td>
                                            <td class="whitespace-nowrap text-right">{{ formatoReloj(fila.horas_trabajadas) }}</td>
                                            <td class="whitespace-nowrap text-right">{{ formatoReloj(fila.horas_extra) }}</td>
                                            <td class="whitespace-nowrap text-right">
                                                <button @click="descartarFilaRevision(fila._uid)" class="icon-button-danger" type="button" title="Quitar fila">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="filasRevisionFiltradas.length === 0">
                                            <td colspan="11" class="empty-state">No se encontraron filas en la revision.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                                <button @click="descartarRevision" :disabled="formRevision.processing" class="btn-secondary" type="button">
                                    <i class="ti ti-trash" aria-hidden="true"></i>
                                    Descartar revision
                                </button>
                                <button @click="aprobarRevision" :disabled="formRevision.processing || resumenRevision.seleccionadas === 0" class="btn-accent" type="button">
                                    <svg v-if="!formRevision.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
                                    </svg>
                                    {{ formRevision.processing ? 'Guardando...' : `Aprobar ${resumenRevision.seleccionadas} seleccionada(s)` }}
                                </button>
                            </div>
                        </div>

                        <div v-else class="empty-state">
                            No hay una revision CSV pendiente.
                        </div>
                    </section>
                </div>

                <div v-show="tabActiva === 'vacaciones'" class="space-y-6 animate-fade-in">
                    <section class="app-panel">
                        <div class="panel-header">
                            <div class="flex items-start gap-3">
                                <div class="soft-icon-emerald">
                                    <i class="ti ti-beach text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                <h3 class="panel-title">Cuadro de Vacaciones</h3>
                                <p class="panel-subtitle">Control global de dias correspondientes, tomados y restantes.</p>
                                </div>
                            </div>
                            <input v-model="busquedaGlobal" type="text" class="field-input-soft lg:w-96" placeholder="Buscar trabajador..." />
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table-premium">
                                <thead>
                                    <tr>
                                        <th>Num</th>
                                        <th>Nombre del Trabajador</th>
                                        <th>Fecha de Ingreso</th>
                                        <th class="text-center">Años Cumplidos</th>
                                        <th class="text-center">Dias por Ley</th>
                                        <th class="text-center">Dias Tomados</th>
                                        <th class="text-center">Desc. años pasados</th>
                                        <th class="text-center">Dias Pendientes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="empleado in empleadosFiltradosGlobal" :key="empleado.id">
                                        <td class="whitespace-nowrap font-bold text-slate-500">{{ empleado.numero_empleado || 'S/N' }}</td>
                                        <td class="whitespace-nowrap font-semibold text-slate-900">{{ empleado.nombre_completo }}</td>
                                        <td class="whitespace-nowrap text-slate-600">{{ empleado.fecha_ingreso || 'No registrada' }}</td>
                                        <td class="whitespace-nowrap text-center font-medium">{{ empleado.antiguedad_anios }}</td>
                                        <td class="whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-3 py-1 font-bold text-slate-700">
                                                {{ empleado.dias_vacaciones_totales }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1 font-bold text-amber-700">
                                                {{ empleado.dias_vacaciones_tomados }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center justify-center rounded-lg border px-3 py-1 font-bold"
                                                :class="empleado.ajuste_vacaciones < 0 ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-400'"
                                            >
                                                {{ empleado.ajuste_vacaciones < 0 ? Math.abs(empleado.ajuste_vacaciones) : 0 }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center justify-center rounded-lg border px-3 py-1 font-bold"
                                                :class="empleado.dias_vacaciones_restantes > 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400'"
                                            >
                                                {{ empleado.dias_vacaciones_restantes }}
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
                            <div class="flex items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700">
                                    <i class="ti ti-user-x text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                <h3 class="panel-title text-rose-900">Control de Faltas Injustificadas</h3>
                                <p class="panel-subtitle">Acumulado anual de ausencias por empleado.</p>
                                </div>
                            </div>
                            <input v-model="busquedaGlobal" type="text" class="field-input-soft lg:w-96 focus:border-rose-400 focus:ring-rose-500/20" placeholder="Buscar trabajador..." />
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
                                    <tr v-for="empleado in empleadosFiltradosGlobal" :key="empleado.id" class="hover:bg-rose-50/30">
                                        <td class="whitespace-nowrap font-bold text-slate-500">{{ empleado.numero_empleado || 'S/N' }}</td>
                                        <td class="whitespace-nowrap font-semibold text-slate-900">{{ empleado.nombre_completo }}</td>
                                        <td class="whitespace-nowrap text-center">
                                            <span v-if="empleado.dias_faltas_totales === 0" class="status-pill status-success">Asistencia Perfecta</span>
                                            <span v-else-if="empleado.dias_faltas_totales < 3" class="status-pill status-warning">Alerta Minima</span>
                                            <span v-else class="status-pill border-rose-200 bg-rose-50 text-rose-700">Problema de Ausentismo</span>
                                        </td>
                                        <td class="whitespace-nowrap text-right">
                                            <span class="inline-flex items-center justify-center text-lg font-black" :class="empleado.dias_faltas_totales > 0 ? 'text-rose-600' : 'text-slate-300'">
                                                {{ empleado.dias_faltas_totales }}
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
    from {
        opacity: 0;
        transform: translateY(5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
