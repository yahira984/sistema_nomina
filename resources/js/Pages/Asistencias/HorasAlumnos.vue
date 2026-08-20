<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    estudiantes: {
        type: Array,
        default: () => [],
    },
    semanas: {
        type: Array,
        default: () => [],
    },
    fechaCorteActual: {
        type: String,
        default: '',
    },
    numeroSemanaActual: {
        type: Number,
        default: null,
    },
    rangoSemanaActual: {
        type: String,
        default: '',
    },
});

const fechaCorte = ref(props.fechaCorteActual);
const busqueda = ref('');
const seleccionados = ref(props.estudiantes.map((estudiante) => estudiante.id));

const normalizarNumeroEmpleado = (numero) => {
    const texto = String(numero || '').trim();
    const sinCeros = texto.replace(/^0+/, '');

    return sinCeros || texto || '';
};

const numeroEmpleado = (empleado) => normalizarNumeroEmpleado(empleado?.numero_empleado || empleado?.numero_empleado_baja);
const horas = (valor) => Number(valor || 0).toLocaleString('es-MX', { maximumFractionDigits: 2 });

const estudiantesFiltrados = computed(() => {
    const termino = busqueda.value.toLowerCase().trim();

    if (!termino) {
        return props.estudiantes;
    }

    return props.estudiantes.filter((estudiante) => {
        return String(estudiante.nombre_completo || '').toLowerCase().includes(termino)
            || String(estudiante.numero_empleado || '').toLowerCase().includes(termino)
            || String(estudiante.numero_empleado_baja || '').toLowerCase().includes(termino);
    });
});

const seleccionadosCount = computed(() => seleccionados.value.length);
const idsVisibles = computed(() => estudiantesFiltrados.value.map((estudiante) => estudiante.id));
const visiblesCount = computed(() => estudiantesFiltrados.value.length);
const todosVisiblesSeleccionados = computed(() => {
    return idsVisibles.value.length > 0 && idsVisibles.value.every((id) => seleccionados.value.includes(id));
});

const fechaSeleccionadaLabel = computed(() => {
    const semana = props.semanas.find((item) => item.fecha_corte === fechaCorte.value);

    return semana?.etiqueta || props.rangoSemanaActual || 'Semana seleccionada';
});

const semanaSeleccionada = computed(() => props.semanas.find((item) => item.fecha_corte === fechaCorte.value));

const limpiarBusqueda = () => {
    busqueda.value = '';
};

const formatoFecha = (fecha) => {
    if (!fecha) return 'Sin fecha';

    return new Date(`${fecha}T00:00:00`).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const alternarVisible = () => {
    const visibles = new Set(idsVisibles.value);

    if (todosVisiblesSeleccionados.value) {
        seleccionados.value = seleccionados.value.filter((id) => !visibles.has(id));
        return;
    }

    seleccionados.value = [...new Set([...seleccionados.value, ...idsVisibles.value])];
};

const seleccionarTodos = () => {
    seleccionados.value = props.estudiantes.map((estudiante) => estudiante.id);
};

const limpiarSeleccion = () => {
    seleccionados.value = [];
};

const urlPdf = (todos = false, tipo = 'semanal') => {
    const parametros = {
        fecha_corte: fechaCorte.value,
        tipo,
    };

    if (!todos) {
        parametros.empleado_ids = seleccionados.value;
    }

    return route('asistencias.alumnos-horas.pdf', parametros);
};
</script>

<template>
    <Head title="Horas de Alumnos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <Link :href="route('asistencias.index')" class="icon-button" aria-label="Volver a asistencias">
                    <i class="ti ti-arrow-left" aria-hidden="true"></i>
                </Link>
                <div class="min-w-0">
                    <p class="text-sm font-black uppercase text-teal-700">Registro y control</p>
                    <h2 class="text-xl font-black text-slate-950 sm:text-2xl">Horas de alumnos</h2>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-5">
                <nav class="tab-strip sm:grid-cols-2 md:grid-cols-5" aria-label="Secciones de asistencias">
                    <Link :href="route('asistencias.index', { tab: 'captura' })" class="tab-button text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                        <i class="ti ti-clock-plus" aria-hidden="true"></i>Captura y Reloj
                    </Link>
                    <Link :href="route('asistencias.index', { tab: 'revision' })" class="tab-button text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                        <i class="ti ti-file-search" aria-hidden="true"></i>Revision CSV
                    </Link>
                    <Link :href="route('asistencias.index', { tab: 'vacaciones' })" class="tab-button text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                        <i class="ti ti-beach" aria-hidden="true"></i>Control Vacaciones
                    </Link>
                    <Link :href="route('asistencias.index', { tab: 'faltas' })" class="tab-button text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                        <i class="ti ti-user-x" aria-hidden="true"></i>Control Faltas
                    </Link>
                    <span class="tab-button bg-blue-50 font-black text-blue-700 shadow-sm ring-1 ring-blue-200/70" aria-current="page">
                        <i class="ti ti-school" aria-hidden="true"></i>Horas Alumnos
                    </span>
                </nav>

                <section class="app-panel overflow-hidden">
                    <div class="panel-header gap-4">
                        <div class="flex items-start gap-3">
                            <div class="soft-icon-blue"><i class="ti ti-school text-xl" aria-hidden="true"></i></div>
                            <div>
                                <h3 class="panel-title">Formatos semanales de alumnos</h3>
                                <p class="panel-subtitle">Elige el periodo, selecciona alumnos y genera sus registros con dos formatos por hoja.</p>
                            </div>
                        </div>
                        <div class="grid w-full gap-2 sm:w-auto sm:grid-cols-2">
                            <span class="status-pill status-info justify-center">2 alumnos por hoja</span>
                            <span class="status-pill justify-center border-emerald-200 bg-emerald-50 text-emerald-700">Semanal</span>
                            <a v-if="seleccionadosCount > 0" :href="urlPdf(false)" target="_blank" class="btn-accent justify-center text-xs">
                                <i class="ti ti-file-type-pdf" aria-hidden="true"></i>Semanal seleccionados ({{ seleccionadosCount }})
                            </a>
                            <button v-else type="button" class="btn-accent justify-center text-xs opacity-50" disabled>
                                <i class="ti ti-file-type-pdf" aria-hidden="true"></i>Semanal seleccionados
                            </button>
                            <a v-if="estudiantes.length > 0" :href="urlPdf(true)" target="_blank" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">
                                <i class="ti ti-files" aria-hidden="true"></i>Semanal de todos ({{ estudiantes.length }})
                            </a>
                            <a v-if="seleccionadosCount > 0" :href="urlPdf(false, 'acumulado')" target="_blank" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100">
                                <i class="ti ti-history" aria-hidden="true"></i>Total seleccionados ({{ seleccionadosCount }})
                            </a>
                            <a v-if="estudiantes.length > 0" :href="urlPdf(true, 'acumulado')" target="_blank" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800">
                                <i class="ti ti-report" aria-hidden="true"></i>Total de todos ({{ estudiantes.length }})
                            </a>
                        </div>
                    </div>

                    <div class="grid divide-y divide-slate-200 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.7fr)] lg:divide-x lg:divide-y-0">
                        <div class="p-5 sm:p-6">
                            <div class="mb-3 flex items-center gap-2 text-sm font-black text-slate-900">
                                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-blue-100 text-blue-700">1</span>
                                Semana del formato
                            </div>
                            <label class="block">
                                <span class="field-label">Periodo disponible</span>
                                <select v-model="fechaCorte" class="field-input-soft">
                                    <option v-for="semana in semanas" :key="semana.fecha_corte" :value="semana.fecha_corte">{{ semana.etiqueta }}</option>
                                </select>
                            </label>
                        </div>
                        <div class="flex items-center gap-4 bg-slate-50 p-5 sm:p-6">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-white text-xl text-blue-700 shadow-sm">
                                <i class="ti ti-calendar-week" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="field-label">Periodo que se imprimirá</p>
                                <p class="mt-1 font-black text-slate-950">{{ fechaSeleccionadaLabel }}</p>
                                <p v-if="semanaSeleccionada" class="mt-1 text-xs font-semibold text-slate-500">Corte: {{ formatoFecha(fechaCorte) }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="app-panel overflow-hidden">
                    <div class="border-b border-slate-200 bg-white p-5 sm:p-6">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2 text-sm font-black text-slate-900">
                                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-teal-100 text-teal-700">2</span>
                                Selecciona los alumnos
                            </div>
                            <p class="text-xs font-bold text-slate-500">{{ visiblesCount }} visibles · {{ seleccionadosCount }} seleccionados de {{ estudiantes.length }}</p>
                        </div>

                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                            <label class="relative block w-full xl:max-w-xl">
                                <span class="sr-only">Buscar alumno</span>
                                <i class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400" aria-hidden="true"></i>
                                <input v-model="busqueda" type="search" class="field-input-soft h-11 pl-10 pr-10" placeholder="Buscar por nombre o número de empleado" />
                                <button v-if="busqueda" type="button" class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100" title="Limpiar búsqueda" aria-label="Limpiar búsqueda" @click="limpiarBusqueda">
                                    <i class="ti ti-x" aria-hidden="true"></i>
                                </button>
                            </label>

                            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                                <button type="button" class="btn-secondary justify-center text-xs" @click="alternarVisible">
                                    <i class="ti ti-checklist" aria-hidden="true"></i>{{ todosVisiblesSeleccionados ? 'Quitar visibles' : 'Seleccionar visibles' }}
                                </button>
                                <button type="button" class="btn-secondary justify-center text-xs" @click="seleccionarTodos">
                                    <i class="ti ti-list-check" aria-hidden="true"></i>Seleccionar todos
                                </button>
                                <button type="button" class="btn-secondary col-span-2 justify-center text-xs" :disabled="seleccionadosCount === 0" :class="{ 'opacity-50': seleccionadosCount === 0 }" @click="limpiarSeleccion">
                                    <i class="ti ti-x" aria-hidden="true"></i>Limpiar selección
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
                        <label
                            v-for="estudiante in estudiantesFiltrados"
                            :key="estudiante.id"
                            class="group flex min-h-40 cursor-pointer items-start gap-3 rounded-lg border p-4 transition"
                            :class="seleccionados.includes(estudiante.id) ? 'border-teal-300 bg-teal-50 ring-1 ring-teal-200' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                        >
                            <input
                                v-model="seleccionados"
                                type="checkbox"
                                :value="estudiante.id"
                                class="mt-1 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="mb-2 inline-flex rounded-md bg-slate-100 px-2 py-1 text-[11px] font-black text-slate-700">Núm. {{ numeroEmpleado(estudiante) || 'S/N' }}</span>
                                <span class="block break-words text-sm font-black uppercase leading-5 text-slate-950">{{ estudiante.nombre_completo }}</span>
                                <span class="mt-1 block text-xs font-semibold text-slate-500">
                                    {{ estudiante.universidad || 'Universidad pendiente de registrar' }}
                                </span>
                                <span class="mt-3 block h-2 overflow-hidden rounded-full bg-slate-200"><span class="block h-full rounded-full bg-teal-500" :style="{ width: `${Math.min(100, estudiante.resumen_servicio?.porcentaje || 0)}%` }"></span></span>
                                <span class="mt-2 grid grid-cols-3 gap-2 text-[11px] font-bold text-slate-600">
                                    <span><b class="block text-emerald-700">{{ horas(estudiante.resumen_servicio?.horas_cumplidas) }}</b>Cumplidas</span>
                                    <span><b class="block text-blue-700">{{ horas(estudiante.resumen_servicio?.horas_requeridas) }}</b>Requeridas</span>
                                    <span><b class="block text-amber-700">{{ horas(estudiante.resumen_servicio?.horas_restantes) }}</b>Restantes</span>
                                </span>
                            </span>
                            <i v-if="seleccionados.includes(estudiante.id)" class="ti ti-circle-check-filled text-xl text-teal-600" aria-hidden="true"></i>
                        </label>

                        <div v-if="estudiantesFiltrados.length === 0" class="empty-state sm:col-span-2 xl:col-span-3">
                            <i class="ti ti-user-search mb-2 text-3xl text-slate-300" aria-hidden="true"></i>
                            <p class="font-bold text-slate-700">No encontramos alumnos</p>
                            <button v-if="busqueda" type="button" class="btn-secondary mt-3 text-xs" @click="limpiarBusqueda">Limpiar búsqueda</button>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
