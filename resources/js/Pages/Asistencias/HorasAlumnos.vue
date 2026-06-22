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
const todosVisiblesSeleccionados = computed(() => {
    return idsVisibles.value.length > 0 && idsVisibles.value.every((id) => seleccionados.value.includes(id));
});

const fechaSeleccionadaLabel = computed(() => {
    const semana = props.semanas.find((item) => item.fecha_corte === fechaCorte.value);

    return semana?.etiqueta || props.rangoSemanaActual || 'Semana seleccionada';
});

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

const urlPdf = (todos = false) => {
    const parametros = {
        fecha_corte: fechaCorte.value,
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
                    <p class="text-sm font-semibold text-teal-700">Asistencias</p>
                    <h2 class="text-xl font-semibold text-slate-950 sm:text-2xl">Registro de Horas de Alumnos</h2>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-6">
                <section class="app-panel">
                    <div class="panel-header">
                        <div class="flex items-start gap-3">
                            <div class="soft-icon-blue">
                                <i class="ti ti-school text-xl" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h3 class="panel-title">Formato semanal de servicio</h3>
                                <p class="panel-subtitle">Genera el registro con 2 alumnos por hoja cuando selecciones varios.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 text-xs font-bold">
                            <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-blue-700">
                                {{ estudiantes.length }} alumno(s)
                            </span>
                            <span class="rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-teal-700">
                                {{ seleccionadosCount }} seleccionado(s)
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-4 p-5 sm:p-6 xl:grid-cols-[1fr_18rem] xl:items-end">
                        <label class="block">
                            <span class="field-label">Semana</span>
                            <select v-model="fechaCorte" class="field-input-soft">
                                <option v-for="semana in semanas" :key="semana.fecha_corte" :value="semana.fecha_corte">
                                    {{ semana.etiqueta }}
                                </option>
                            </select>
                        </label>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">
                            <span class="block uppercase text-slate-400">Corte</span>
                            {{ fechaSeleccionadaLabel }}
                        </div>
                    </div>
                </section>

                <section class="app-panel">
                    <div class="border-b border-slate-200 bg-white px-5 py-4 sm:px-6">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                            <div class="w-full lg:max-w-md">
                                <span class="field-label">Buscar alumno</span>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="ti ti-search" aria-hidden="true"></i>
                                    </div>
                                    <input v-model="busqueda" type="text" class="field-input-soft pl-9" placeholder="Nombre o numero..." />
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                <button type="button" class="btn-secondary justify-center text-xs" @click="alternarVisible">
                                    <i class="ti ti-checklist" aria-hidden="true"></i>
                                    {{ todosVisiblesSeleccionados ? 'Quitar visibles' : 'Seleccionar visibles' }}
                                </button>
                                <button type="button" class="btn-secondary justify-center text-xs" @click="seleccionarTodos">
                                    <i class="ti ti-list-check" aria-hidden="true"></i>
                                    Todos
                                </button>
                                <button type="button" class="btn-secondary justify-center text-xs" @click="limpiarSeleccion">
                                    <i class="ti ti-filter-x" aria-hidden="true"></i>
                                    Limpiar
                                </button>
                                <a
                                    v-if="seleccionadosCount > 0"
                                    :href="urlPdf(false)"
                                    target="_blank"
                                    class="btn-accent justify-center text-xs"
                                >
                                    <i class="ti ti-printer" aria-hidden="true"></i>
                                    PDF seleccionados
                                </a>
                                <button v-else type="button" class="btn-accent justify-center text-xs opacity-50" disabled>
                                    <i class="ti ti-printer" aria-hidden="true"></i>
                                    PDF seleccionados
                                </button>
                                <a
                                    v-if="estudiantes.length > 0"
                                    :href="urlPdf(true)"
                                    target="_blank"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800"
                                >
                                    <i class="ti ti-printer" aria-hidden="true"></i>
                                    PDF todos
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
                        <label
                            v-for="estudiante in estudiantesFiltrados"
                            :key="estudiante.id"
                            class="flex cursor-pointer items-start gap-3 rounded-lg border bg-white p-4 shadow-sm transition hover:border-teal-300 hover:bg-teal-50/40"
                            :class="seleccionados.includes(estudiante.id) ? 'border-teal-300 bg-teal-50/60' : 'border-slate-200'"
                        >
                            <input
                                v-model="seleccionados"
                                type="checkbox"
                                :value="estudiante.id"
                                class="mt-1 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                            />
                            <span class="min-w-0">
                                <span class="block text-sm font-black uppercase text-slate-950">
                                    #{{ numeroEmpleado(estudiante) || 'S/N' }} · {{ estudiante.nombre_completo }}
                                </span>
                                <span class="mt-1 block text-xs font-semibold text-slate-500">
                                    Ingreso: {{ formatoFecha(estudiante.fecha_ingreso) }}
                                </span>
                                <span class="mt-1 inline-flex rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700">
                                    Alumno
                                </span>
                            </span>
                        </label>

                        <div v-if="estudiantesFiltrados.length === 0" class="empty-state sm:col-span-2 xl:col-span-3">
                            No se encontraron alumnos con ese filtro.
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
