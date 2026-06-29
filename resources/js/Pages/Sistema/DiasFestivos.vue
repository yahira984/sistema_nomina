<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    anio: Number,
    aniosDisponibles: {
        type: Array,
        default: () => [],
    },
    diasFestivos: {
        type: Array,
        default: () => [],
    },
    estadisticas: {
        type: Object,
        default: () => ({ total: 0, activos: 0, oficiales: 0, manuales: 0 }),
    },
    fuenteOficial: String,
});

const page = usePage();
const mensajeExito = computed(() => page.props.flash?.success);
const anioSeleccionado = ref(props.anio);
const editando = ref(null);
const generando = ref(false);

const form = useForm({
    fecha: '',
    nombre: '',
    tipo: 'manual',
    descripcion: '',
    es_oficial: false,
    activo: true,
});

watch(() => props.anio, (nuevoAnio) => {
    anioSeleccionado.value = nuevoAnio;
});

const diasOrdenados = computed(() => props.diasFestivos ?? []);
const festivosActivos = computed(() => diasOrdenados.value.filter((dia) => dia.activo));
const siguienteFestivo = computed(() => festivosActivos.value.find((dia) => Number(dia.dias_restantes ?? 0) >= 0));

const fechaInput = (fecha) => String(fecha || '').slice(0, 10);

const cambiarAnio = () => {
    router.get(route('dias-festivos.index'), { anio: anioSeleccionado.value }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const limpiarFormulario = () => {
    editando.value = null;
    form.clearErrors();
    form.reset();
};

const editarDia = (dia) => {
    editando.value = dia;
    form.clearErrors();
    form.fecha = fechaInput(dia.fecha);
    form.nombre = dia.nombre || '';
    form.tipo = dia.tipo || 'manual';
    form.descripcion = dia.descripcion || '';
    form.es_oficial = Boolean(dia.es_oficial);
    form.activo = Boolean(dia.activo);
};

const esManual = (dia) => dia.origen === 'manual';

const guardarDia = () => {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => limpiarFormulario(),
    };

    if (editando.value) {
        form.put(route('dias-festivos.update', editando.value.id), opciones);
        return;
    }

    form.post(route('dias-festivos.store'), opciones);
};

const quitarDia = (dia) => {
    const accion = esManual(dia) ? 'borrar definitivamente' : 'desactivar';

    if (!confirm(`¿${accion} ${dia.nombre}?`)) {
        return;
    }

    router.delete(route('dias-festivos.destroy', dia.id), {
        preserveScroll: true,
    });
};

const generarOficiales = () => {
    generando.value = true;
    router.post(route('dias-festivos.generar'), { anio: anioSeleccionado.value }, {
        preserveScroll: true,
        onFinish: () => {
            generando.value = false;
        },
    });
};

const claseTipo = (dia) => {
    if (!dia.activo) return 'border-slate-200 bg-slate-100 text-slate-500';
    if (dia.tipo === 'electoral') return 'border-violet-200 bg-violet-50 text-violet-700';
    if (dia.tipo === 'empresa') return 'border-blue-200 bg-blue-50 text-blue-700';
    if (dia.es_oficial) return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    return 'border-amber-200 bg-amber-50 text-amber-700';
};

const etiquetaTipo = (dia) => {
    if (!dia.activo) return 'Inactivo';
    if (dia.tipo === 'electoral') return 'Electoral';
    if (dia.tipo === 'empresa') return 'Empresa';
    return dia.es_oficial ? 'Oficial' : 'Manual';
};
</script>

<template>
    <Head title="Dias Festivos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <Link :href="route('dashboard')" class="icon-button" aria-label="Volver al panel">
                    <i class="ti ti-arrow-left" aria-hidden="true"></i>
                </Link>
                <div class="min-w-0">
                    <p class="page-kicker">
                        <i class="ti ti-calendar-event" aria-hidden="true"></i>
                        Sistema
                    </p>
                    <h2 class="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">Dias Festivos</h2>
                    <p class="mt-1 text-sm text-slate-500">Genera los festivos oficiales de Mexico y ajusta fechas especiales de la empresa.</p>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-6">
                <div v-if="mensajeExito" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700">
                    <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
                    {{ mensajeExito }}
                </div>

                <section class="grid gap-4 md:grid-cols-4">
                    <div class="metric-card">
                        <p class="metric-label">Año</p>
                        <p class="metric-value text-2xl">{{ anio }}</p>
                        <p class="metric-note">Calendario activo</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Activos</p>
                        <p class="metric-value text-2xl">{{ estadisticas.activos }}</p>
                        <p class="metric-note">Se muestran en dashboard</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Oficiales</p>
                        <p class="metric-value text-2xl">{{ estadisticas.oficiales }}</p>
                        <p class="metric-note">Base LFT Mexico</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Editados</p>
                        <p class="metric-value text-2xl">{{ estadisticas.manuales }}</p>
                        <p class="metric-note">Captura manual</p>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-12">
                    <div class="app-panel xl:col-span-4">
                        <div class="panel-header">
                            <div class="flex items-start gap-3">
                                <div class="soft-icon-amber">
                                    <i class="ti ti-calendar-plus text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h3 class="panel-title">{{ editando ? 'Editar dia' : 'Agregar dia' }}</h3>
                                    <p class="panel-subtitle">Usa este formulario para jornadas electorales, descansos internos o correcciones.</p>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="guardarDia" class="space-y-4 p-5 sm:p-6">
                            <div>
                                <label class="field-label">Fecha</label>
                                <input v-model="form.fecha" type="date" class="field-input-soft" />
                                <p v-if="form.errors.fecha" class="mt-2 text-sm font-semibold text-rose-600">{{ form.errors.fecha }}</p>
                            </div>

                            <div>
                                <label class="field-label">Nombre</label>
                                <input v-model="form.nombre" type="text" class="field-input-soft" placeholder="Ej. Jornada electoral" />
                                <p v-if="form.errors.nombre" class="mt-2 text-sm font-semibold text-rose-600">{{ form.errors.nombre }}</p>
                            </div>

                            <div>
                                <label class="field-label">Tipo</label>
                                <select v-model="form.tipo" class="field-input-soft">
                                    <option value="oficial">Oficial</option>
                                    <option value="empresa">Empresa</option>
                                    <option value="electoral">Electoral</option>
                                    <option value="manual">Manual</option>
                                </select>
                                <p v-if="form.errors.tipo" class="mt-2 text-sm font-semibold text-rose-600">{{ form.errors.tipo }}</p>
                            </div>

                            <div>
                                <label class="field-label">Descripcion</label>
                                <textarea v-model="form.descripcion" rows="3" class="field-input-soft resize-none" placeholder="Nota visible para administracion"></textarea>
                                <p v-if="form.errors.descripcion" class="mt-2 text-sm font-semibold text-rose-600">{{ form.errors.descripcion }}</p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700">
                                    <input v-model="form.es_oficial" type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                                    Oficial LFT
                                </label>
                                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700">
                                    <input v-model="form.activo" type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                                    Activo
                                </label>
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <button type="submit" :disabled="form.processing" class="btn-accent flex-1">
                                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                                    {{ form.processing ? 'Guardando...' : (editando ? 'Guardar cambios' : 'Agregar dia') }}
                                </button>
                                <button v-if="editando" type="button" @click="limpiarFormulario" class="btn-secondary">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="space-y-6 xl:col-span-8">
                        <div class="app-panel">
                            <div class="panel-header">
                                <div class="flex items-start gap-3">
                                    <div class="soft-icon-teal">
                                        <i class="ti ti-sparkles text-xl" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <h3 class="panel-title">Generador oficial</h3>
                                        <p class="panel-subtitle">{{ fuenteOficial }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <select v-model="anioSeleccionado" @change="cambiarAnio" class="field-input-soft min-w-36">
                                        <option v-for="item in aniosDisponibles" :key="item" :value="item">{{ item }}</option>
                                    </select>
                                    <button type="button" @click="generarOficiales" :disabled="generando" class="btn-primary">
                                        <i class="ti ti-refresh" aria-hidden="true"></i>
                                        {{ generando ? 'Generando...' : 'Generar oficiales' }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-4 p-5 sm:p-6 md:grid-cols-2">
                                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                                    <p class="text-xs font-black uppercase text-emerald-700">Siguiente festivo</p>
                                    <template v-if="siguienteFestivo">
                                        <p class="mt-2 text-lg font-black text-slate-950">{{ siguienteFestivo.nombre }}</p>
                                        <p class="text-sm font-bold capitalize text-slate-600">{{ siguienteFestivo.dia_semana }} · {{ siguienteFestivo.fecha_formateada }}</p>
                                    </template>
                                    <p v-else class="mt-2 text-sm font-bold text-slate-500">Sin festivos activos proximos en este año.</p>
                                </div>
                                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                                    <p class="text-xs font-black uppercase text-blue-700">Como funciona</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-700">
                                        El sistema agrega solo los festivos que falten. Si ya editaste un dia, no lo reemplaza. Si restauras una base anterior, solo ejecuta las migraciones para crear esta tabla nueva.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="app-panel">
                            <div class="panel-header">
                                <div>
                                    <h3 class="panel-title">Calendario {{ anio }}</h3>
                                    <p class="panel-subtitle">Activa, desactiva o corrige los dias que verá el dashboard.</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="table-premium">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Dia</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Origen</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="dia in diasOrdenados" :key="dia.id" :class="!dia.activo ? 'opacity-60' : ''">
                                            <td>
                                                <p class="font-black text-slate-900">{{ fechaInput(dia.fecha) }}</p>
                                                <p class="text-xs font-semibold text-slate-400">{{ dia.fecha_formateada }}</p>
                                            </td>
                                            <td class="capitalize">{{ dia.dia_semana }}</td>
                                            <td>
                                                <p class="font-bold text-slate-900">{{ dia.nombre }}</p>
                                                <p v-if="dia.descripcion" class="mt-1 max-w-xl text-xs font-medium text-slate-500">{{ dia.descripcion }}</p>
                                            </td>
                                            <td>
                                                <span :class="['status-pill', claseTipo(dia)]">{{ etiquetaTipo(dia) }}</span>
                                            </td>
                                            <td class="capitalize">{{ dia.origen }}</td>
                                            <td>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @click="editarDia(dia)" class="icon-button" title="Editar">
                                                        <i class="ti ti-pencil" aria-hidden="true"></i>
                                                    </button>
                                                    <button
                                                        v-if="dia.activo || esManual(dia)"
                                                        type="button"
                                                        @click="quitarDia(dia)"
                                                        class="icon-button-danger"
                                                        :title="esManual(dia) ? 'Borrar' : 'Desactivar'"
                                                    >
                                                        <i :class="esManual(dia) ? 'ti ti-trash' : 'ti ti-ban'" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="diasOrdenados.length === 0">
                                            <td colspan="6" class="empty-state">No hay dias festivos para este año.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
