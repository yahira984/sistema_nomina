<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { clavesFotoEmpleado, fotoEmpleadoSrc, mostrarFotoEmpleado, probarSiguienteFotoEmpleado } from '@/Utils/employeePhotos';

const props = defineProps({
    empleado: Object,
    accesoApp: {
        type: Object,
        default: () => ({}),
    },
    timeline: {
        type: Array,
        default: () => [],
    },
    resumenServicio: {
        type: Object,
        default: null,
    },
    documentTypes: {
        type: Array,
        default: () => [],
    },
    canManageDocuments: {
        type: Boolean,
        default: false,
    },
    initialTab: {
        type: String,
        default: 'perfil',
    },
    canEditPersonalData: {
        type: Boolean,
        default: false,
    },
    canChangePhoto: {
        type: Boolean,
        default: false,
    },
    initialAction: {
        type: String,
        default: null,
    },
});

const tabActiva = ref(props.initialTab);
const fotoAmpliada = ref(false);
const fotoDisponible = ref(false);
const fotoVersion = ref(Date.now());
const modalFoto = ref(false);
const fotoPreview = ref('');
const fotoForm = useForm({ foto: null });
const modalDatosPersonales = ref(false);
const personalForm = useForm({
    curp: props.empleado.curp || '',
    nss: props.empleado.nss || '',
    rfc: props.empleado.rfc || '',
    telefono: props.empleado.telefono || '',
    contacto_emergencia_nombre: props.empleado.contacto_emergencia_nombre || '',
    contacto_emergencia_telefono: props.empleado.contacto_emergencia_telefono || '',
    fecha_ingreso: props.empleado.fecha_ingreso || '',
});
const profilePhotoSrc = computed(() => `${fotoEmpleadoSrc(props.empleado)}&profile=${fotoVersion.value}`);
const requiredPersonalData = computed(() => [
    ['CURP', props.empleado.curp],
    ['Número de Seguro Social', props.empleado.nss],
    ['RFC', props.empleado.rfc],
    ['Celular personal', props.empleado.telefono],
    ['Nombre de emergencia', props.empleado.contacto_emergencia_nombre],
    ['Teléfono de emergencia', props.empleado.contacto_emergencia_telefono],
    ['Fecha de ingreso', props.empleado.fecha_ingreso],
]);
const missingPersonalData = computed(() => requiredPersonalData.value
    .filter(([, value]) => value === null || value === undefined || String(value).trim() === '')
    .map(([label]) => label));

const marcarFotoDisponible = (event) => {
    fotoDisponible.value = true;
    mostrarFotoEmpleado(event);
};

const abrirFotoEmpleado = () => {
    if (fotoDisponible.value) fotoAmpliada.value = true;
};

const cerrarFotoEmpleado = () => {
    fotoAmpliada.value = false;
};

const abrirEditorFotoPerfil = () => {
    if (!props.canChangePhoto) return;
    fotoForm.reset();
    fotoForm.clearErrors();
    if (fotoPreview.value) URL.revokeObjectURL(fotoPreview.value);
    fotoPreview.value = '';
    modalFoto.value = true;
};
const cerrarEditorFotoPerfil = () => {
    if (fotoPreview.value) URL.revokeObjectURL(fotoPreview.value);
    fotoPreview.value = '';
    fotoForm.reset();
    fotoForm.clearErrors();
    modalFoto.value = false;
};
const seleccionarFotoPerfil = event => {
    const file = event.target.files?.[0] || null;
    if (fotoPreview.value) URL.revokeObjectURL(fotoPreview.value);
    fotoForm.foto = file;
    fotoPreview.value = file ? URL.createObjectURL(file) : '';
};
const guardarFotoPerfil = () => {
    if (!fotoForm.foto) return;
    fotoForm.post(route('empleados.foto.actualizar', props.empleado.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            fotoDisponible.value = true;
            fotoVersion.value = Date.now();
            cerrarEditorFotoPerfil();
        },
    });
};
const abrirDatosPersonales = () => {
    if (!props.canEditPersonalData) return;
    Object.keys(personalForm.data()).forEach(field => {
        personalForm[field] = props.empleado[field] || '';
    });
    personalForm.clearErrors();
    modalDatosPersonales.value = true;
};
const cerrarDatosPersonales = () => {
    personalForm.clearErrors();
    modalDatosPersonales.value = false;
};
const guardarDatosPersonales = () => personalForm.patch(
    route('empleados.datos-personales.actualizar', props.empleado.id),
    {
        preserveScroll: true,
        onSuccess: () => cerrarDatosPersonales(),
    },
);

const manejarTeclaFoto = (event) => {
    if (event.key !== 'Escape') return;
    cerrarFotoEmpleado();
    if (modalFoto.value) cerrarEditorFotoPerfil();
    if (modalDatosPersonales.value) cerrarDatosPersonales();
};

onMounted(() => {
    window.addEventListener('keydown', manejarTeclaFoto);
    if (props.initialAction === 'photo') abrirEditorFotoPerfil();
    if (props.initialAction === 'personal') abrirDatosPersonales();
});
onBeforeUnmount(() => {
    window.removeEventListener('keydown', manejarTeclaFoto);
    if (fotoPreview.value) URL.revokeObjectURL(fotoPreview.value);
});

/* MODAL PARA EDITAR FECHA DE BAJA */
const modalEditarBaja = ref(false);

const bajaForm = useForm({
    fecha_baja: props.empleado.fecha_baja || '',
});

const abrirModalEditarBaja = () => {
    bajaForm.fecha_baja = props.empleado.fecha_baja || '';
    bajaForm.clearErrors();
    modalEditarBaja.value = true;
};

const cerrarModalEditarBaja = () => {
    modalEditarBaja.value = false;
    bajaForm.clearErrors();
};

const guardarFechaBaja = () => {
    bajaForm.patch(route('empleados.fecha-baja.actualizar', props.empleado.id), {
        preserveScroll: true,
        onSuccess: () => cerrarModalEditarBaja(),
    });
};

const modalEditarReingreso = ref(false);
const reingresoForm = useForm({
    fecha_reingreso: props.empleado.fecha_reingreso || '',
});
const abrirModalEditarReingreso = () => {
    reingresoForm.fecha_reingreso = props.empleado.fecha_reingreso || '';
    reingresoForm.clearErrors();
    modalEditarReingreso.value = true;
};
const cerrarModalEditarReingreso = () => {
    modalEditarReingreso.value = false;
    reingresoForm.clearErrors();
};
const guardarFechaReingreso = () => {
    reingresoForm.patch(route('empleados.fecha-reingreso.actualizar', props.empleado.id), {
        preserveScroll: true,
        onSuccess: () => cerrarModalEditarReingreso(),
    });
};

// Saca las iniciales del nombre (Ej: Kevin Yahir Avila -> KY)
const iniciales = computed(() => {
    if (!props.empleado.nombre_completo) return 'EM';
    return props.empleado.nombre_completo.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
});

const esEstudiante = computed(() => Boolean(Number(props.empleado.es_estudiante ?? 0)));
const servicioForm = useForm({
    universidad: props.empleado.universidad || '',
    carrera: props.empleado.carrera || '',
    matricula_estudiante: props.empleado.matricula_estudiante || '',
    encargado_estadias_escuela: props.empleado.encargado_estadias_escuela || '',
    horas_servicio_requeridas: props.empleado.horas_servicio_requeridas || '',
    fecha_inicio_servicio: props.empleado.fecha_inicio_servicio || props.empleado.fecha_ingreso || '',
    fecha_limite_servicio: props.empleado.fecha_limite_servicio || '',
    fecha_termino_servicio: props.empleado.fecha_termino_servicio || '',
    evaluacion_estadia: props.empleado.evaluacion_estadia || '',
    area_proyecto_servicio: props.empleado.area_proyecto_servicio || '',
    observaciones_servicio: props.empleado.observaciones_servicio || '',
    servicio_pausado: Boolean(props.empleado.servicio_pausado),
});
const guardarServicioAlumno = () => servicioForm.patch(route('empleados.servicio-alumno.actualizar', props.empleado.id), {
    preserveScroll: true,
});
const formatoHoras = (valor) => Number(valor || 0).toLocaleString('es-MX', { maximumFractionDigits: 2 });
const camposCarta = computed(() => [
    ['Universidad', props.empleado.universidad],
    ['Carrera', props.empleado.carrera],
    ['Matrícula', props.empleado.matricula_estudiante],
    ['Encargado escolar', props.empleado.encargado_estadias_escuela],
    ['Inicio del servicio', props.empleado.fecha_inicio_servicio],
    ['Término del servicio', props.empleado.fecha_termino_servicio],
    ['Proyecto', props.empleado.area_proyecto_servicio],
    ['Evaluación', props.empleado.evaluacion_estadia],
]);
const faltantesCarta = computed(() => camposCarta.value.filter(([, value]) => value === null || value === undefined || String(value).trim() === '').map(([label]) => label));
const cartaLista = computed(() => faltantesCarta.value.length === 0);
const selectedDocumentType = ref('');
const selectedDocumentFile = ref(null);
const documentUploading = ref(false);
const documentUploadProgress = ref(0);
const documentErrors = ref({});
const documentInputs = new Map();
const documentToDelete = ref(null);
const documentDeleting = ref(false);
const documentMap = computed(() => Object.fromEntries((props.empleado.documents || []).map(document => [document.document_type, document])));
const documentProgress = computed(() => ({
    loaded: Object.keys(documentMap.value).length,
    total: props.documentTypes.length,
}));
const formatFileSize = bytes => {
    const value = Number(bytes || 0);
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(0)} KB`;
    return `${(value / 1024 / 1024).toFixed(1)} MB`;
};
const compressionText = document => {
    const original = Number(document?.original_size_bytes || 0);
    const stored = Number(document?.stored_size_bytes || 0);
    if (!original || stored >= original) return formatFileSize(stored);
    return `${formatFileSize(stored)} · ${Math.round((1 - stored / original) * 100)}% menos`;
};
const uploadDocument = () => {
    if (!selectedDocumentType.value || !selectedDocumentFile.value || documentUploading.value) return;
    const uploadType = selectedDocumentType.value;
    const data = new FormData();
    data.append('document_type', uploadType);
    data.append('file', selectedDocumentFile.value);
    documentUploading.value = true;
    documentUploadProgress.value = 0;
    documentErrors.value = {};
    router.post(route('empleados.documentos.store', props.empleado.id), data, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            selectedDocumentFile.value = null;
        },
        onError: errors => { documentErrors.value = errors; },
        onProgress: progress => { documentUploadProgress.value = progress?.percentage || 0; },
        onFinish: () => {
            const input = documentInputs.get(uploadType);
            if (input) input.value = '';
            selectedDocumentFile.value = null;
            documentUploading.value = false;
            documentUploadProgress.value = 0;
        },
    });
};
const deleteDocument = document => {
    documentToDelete.value = document;
};
const confirmDeleteDocument = () => {
    if (!documentToDelete.value || documentDeleting.value) return;
    documentDeleting.value = true;
    router.delete(route('empleados.documentos.destroy', [props.empleado.id, documentToDelete.value.id]), {
        preserveScroll: true,
        onSuccess: () => { documentToDelete.value = null; },
        onFinish: () => { documentDeleting.value = false; },
    });
};
const printDocument = document => {
    const frame = window.document.createElement('iframe');
    frame.style.position = 'fixed';
    frame.style.width = '1px';
    frame.style.height = '1px';
    frame.style.opacity = '0';
    frame.style.pointerEvents = 'none';
    frame.src = route('empleados.documentos.view', [props.empleado.id, document.id]);
    frame.onload = () => {
        setTimeout(() => {
            frame.contentWindow?.focus();
            frame.contentWindow?.print();
            setTimeout(() => frame.remove(), 1500);
        }, 350);
    };
    window.document.body.appendChild(frame);
};
const setDocumentInput = (type, element) => {
    if (element) documentInputs.set(type, element);
    else documentInputs.delete(type);
};
const openDocumentPicker = type => {
    if (documentUploading.value) return;
    selectedDocumentType.value = type;
    documentErrors.value = {};
    documentInputs.get(type)?.click();
};
const selectDocumentFile = (type, event) => {
    const file = event.target.files?.[0] || null;
    if (!file) return;
    selectedDocumentType.value = type;
    selectedDocumentFile.value = file;
    uploadDocument();
};

const sueldoSemanalMostrado = computed(() => {
    const sueldoSemanal = Number(props.empleado.sueldo_semanal ?? 0);
    if (sueldoSemanal > 0) return sueldoSemanal.toFixed(2);

    const sueldoPorHora = Number(props.empleado.sueldo_por_hora ?? 0);
    return sueldoPorHora > 0 ? (sueldoPorHora * 56).toFixed(2) : '0.00';
});

const moneda = (valor) => Number(valor ?? 0).toLocaleString('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const saldoPrestamo = computed(() => Number(props.empleado.saldo_prestamo ?? 0));
const prestamoActivo = computed(() => saldoPrestamo.value > 0);
const empleadoActivo = computed(() => Boolean(Number(props.empleado.estatus ?? 0)));
const numeroEmpleado = computed(() => {
    if (empleadoActivo.value) return props.empleado.numero_empleado || 'S/N';

    return props.empleado.numero_empleado || props.empleado.numero_empleado_baja || props.empleado.id;
});
const accesoActivo = computed(() => Boolean(props.accesoApp?.activo));
const accesoUsuario = computed(() => props.accesoApp?.login_usuario || '');
const accesoEmail = computed(() => props.accesoApp?.email_login || '');
const anioBaja = computed(() => props.empleado.fecha_baja ? String(props.empleado.fecha_baja).substring(0, 4) : '');

const accesoForm = useForm({
    usuario: accesoUsuario.value || String(numeroEmpleado.value || ''),
    password: '',
});

watch(() => props.accesoApp, (acceso) => {
    if (!accesoForm.password) {
        accesoForm.usuario = acceso?.login_usuario || String(numeroEmpleado.value || '');
    }
}, { deep: true });

const guardarAccesoApp = () => {
    accesoForm.post(route('empleados.acceso-app.guardar', props.empleado.id), {
        preserveScroll: true,
        onSuccess: () => accesoForm.reset('password'),
    });
};

const desactivarAccesoApp = () => {
    if (!window.confirm('¿Desactivar el acceso de app para este empleado?')) return;

    router.delete(route('empleados.acceso-app.desactivar', props.empleado.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Expediente | ${empleado.nombre_completo}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <Link :href="route('empleados.index')" class="icon-button" aria-label="Volver">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19 3 12m0 0 7-7m-7 7h18" />
                    </svg>
                </Link>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-teal-700">Expediente Digital</p>
                    <h2 class="text-xl font-semibold text-slate-950 sm:text-2xl">Perfil del Empleado</h2>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-6">

                <div class="relative flex flex-col items-start gap-5 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 md:flex-row md:items-center md:gap-6">
                    <div class="absolute top-0 left-0 w-full h-16 bg-gradient-to-r from-teal-500 to-emerald-600 opacity-20"></div>

                    <div class="relative z-10 shrink-0">
                        <button
                            type="button"
                            class="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-gradient-to-br from-teal-600 to-emerald-800 text-2xl font-black text-white shadow-lg transition hover:ring-4 hover:ring-teal-200 disabled:cursor-default disabled:hover:ring-0 sm:h-24 sm:w-24 sm:text-3xl"
                            :disabled="!fotoDisponible"
                            :title="fotoDisponible ? 'Ampliar fotografía' : 'Sin fotografía'"
                            @click="abrirFotoEmpleado"
                        >
                            <span>{{ iniciales }}</span>
                            <img
                                v-if="clavesFotoEmpleado(empleado).length"
                                :src="profilePhotoSrc"
                                :alt="`Foto de ${empleado.nombre_completo}`"
                                loading="lazy"
                                decoding="async"
                                class="absolute inset-0 h-full w-full object-cover"
                                @load="marcarFotoDisponible"
                                @error="probarSiguienteFotoEmpleado(empleado, $event)"
                            />
                        </button>
                        <button
                            v-if="canChangePhoto"
                            type="button"
                            class="absolute -bottom-1 -right-1 flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-blue-700 text-white shadow-lg transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200"
                            title="Cambiar fotografía"
                            aria-label="Cambiar fotografía"
                            @click="abrirEditorFotoPerfil"
                        >
                            <i class="ti ti-camera" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="z-10 min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center gap-2 sm:gap-3">
                            <h1 class="break-words text-xl font-bold leading-tight text-slate-900 sm:text-2xl">
                                {{ empleado.nombre_completo }}
                            </h1>

                            <span
                                v-if="empleado.estatus"
                                class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200"
                            >
                                Activo
                            </span>

                            <span
                                v-else
                                class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 text-xs font-bold border border-rose-200"
                            >
                                Baja
                            </span>

                            <span
                                v-if="prestamoActivo"
                                class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-800"
                            >
                                <i class="ti ti-cash-banknote" aria-hidden="true"></i>
                                Debe ${{ moneda(empleado.saldo_prestamo) }}
                            </span>

                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700"
                            >
                                <i class="ti ti-circle-check" aria-hidden="true"></i>
                                Sin deuda
                            </span>
                        </div>

                        <p class="mb-3 flex flex-wrap items-center gap-2 text-sm font-medium text-slate-600 sm:text-base">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            {{ empleado.puesto || 'Puesto no asignado' }} • No. empleado: #{{ numeroEmpleado }}
                        </p>

                        <!-- BLOQUE DE BAJA CON BOTÓN DE LÁPIZ -->
                        <div
                            v-if="!empleado.estatus"
                            class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <span>
                                    Baja registrada el {{ empleado.fecha_baja || 'sin fecha' }}
                                    · {{ empleado.dias_laborados || 0 }} días laborados
                                </span>

                                <span v-if="anioBaja" class="text-xs font-bold text-rose-500">
                                    {{ empleado.dias_laborados_anio_baja || 0 }} dias laborados en {{ anioBaja }}
                                </span>

                                <button
                                    type="button"
                                    class="inline-flex w-fit items-center gap-1 rounded-md border border-rose-200 bg-white px-2.5 py-1 text-xs font-bold text-rose-700 shadow-sm transition hover:bg-rose-100"
                                    title="Editar fecha de baja"
                                    @click="abrirModalEditarBaja"
                                >
                                    <i class="ti ti-pencil" aria-hidden="true"></i>
                                    Editar baja
                                </button>
                            </div>
                        </div>

                        <div v-if="empleadoActivo && empleado.fecha_reingreso" class="mb-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-800">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <span>Periodo actual desde el {{ empleado.fecha_reingreso }}</span>
                                <button type="button" class="inline-flex w-fit items-center gap-1 rounded-md border border-blue-200 bg-white px-2.5 py-1 text-xs font-bold text-blue-700 shadow-sm transition hover:bg-blue-100" title="Editar fecha de reingreso" @click="abrirModalEditarReingreso">
                                    <i class="ti ti-pencil" aria-hidden="true"></i>Editar reingreso
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2 border-b border-slate-200 pb-2">
                            <button
                                @click="tabActiva = 'perfil'"
                                :class="tabActiva === 'perfil' ? 'bg-teal-50 text-teal-700 border-teal-200 font-bold' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                                class="inline-flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-all sm:w-auto"
                            >
                                <i class="ti ti-id" aria-hidden="true"></i>
                                Datos Personales
                            </button>

                            <button
                                @click="tabActiva = 'nomina'"
                                :class="tabActiva === 'nomina' ? 'bg-blue-50 text-blue-700 border-blue-200 font-bold' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                                class="inline-flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-all sm:w-auto"
                            >
                                <i class="ti ti-report-money" aria-hidden="true"></i>
                                Nómina y Puesto
                            </button>

                            <button
                                @click="tabActiva = 'asistencia'"
                                :class="tabActiva === 'asistencia' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                                class="inline-flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-all sm:w-auto"
                            >
                                <i class="ti ti-calendar-check" aria-hidden="true"></i>
                                Asistencia y Vacaciones
                            </button>

                            <button
                                v-if="documentTypes.length"
                                @click="tabActiva = 'documentacion'"
                                :class="tabActiva === 'documentacion' ? 'bg-cyan-50 text-cyan-700 border-cyan-200 font-bold' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                                class="inline-flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-all sm:w-auto"
                            >
                                <i class="ti ti-folder" aria-hidden="true"></i>
                                Documentación
                                <span :class="documentProgress.loaded === documentProgress.total ? 'status-success' : 'status-warning'">{{ documentProgress.loaded }}/{{ documentProgress.total }}</span>
                            </button>

                            <button
                                v-if="esEstudiante"
                                @click="tabActiva = 'servicio'"
                                :class="tabActiva === 'servicio' ? 'bg-violet-50 text-violet-700 border-violet-200 font-bold' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                                class="inline-flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-all sm:w-auto"
                            >
                                <i class="ti ti-school" aria-hidden="true"></i>
                                Servicio social
                            </button>

                            <button
                                @click="tabActiva = 'historial'"
                                :class="tabActiva === 'historial' ? 'bg-amber-50 text-amber-700 border-amber-200 font-bold' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                                class="inline-flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-all sm:w-auto"
                            >
                                <i class="ti ti-history" aria-hidden="true"></i>
                                Historial
                                <span class="status-warning">{{ timeline.length + (empleado.reingresos || []).length }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-show="tabActiva === 'perfil'" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in">
                    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 md:col-span-2 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700">
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">Información personal del expediente</p>
                            <p v-if="missingPersonalData.length" class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-300">Pendiente: {{ missingPersonalData.join(' · ') }}</p>
                            <p v-else class="mt-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">Los datos obligatorios están completos.</p>
                        </div>
                        <button v-if="canEditPersonalData" type="button" class="btn-secondary shrink-0" @click="abrirDatosPersonales">
                            <i class="ti ti-pencil" aria-hidden="true"></i>
                            Actualizar información
                        </button>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                            <i class="ti ti-user-circle text-xl text-teal-600" aria-hidden="true"></i>
                            Información Básica
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">CURP</p>
                                <p class="font-medium">{{ empleado.curp || 'No registrado' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Fecha de Nacimiento</p>
                                <p class="font-medium">{{ empleado.fecha_nacimiento || 'No registrada' }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-slate-500 uppercase font-semibold">Género</p>
                                    <p class="font-medium">{{ empleado.genero || '--' }}</p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-500 uppercase font-semibold">Estado Civil</p>
                                    <p class="font-medium">{{ empleado.estado_civil || '--' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                            <i class="ti ti-phone-call text-xl text-teal-600" aria-hidden="true"></i>
                            Contacto y Emergencias
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Teléfono</p>
                                <p class="font-medium">{{ empleado.telefono || 'No registrado' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Correo Electrónico</p>
                                <p class="font-medium">{{ empleado.correo || 'No registrado' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Dirección</p>
                                <p class="font-medium">{{ empleado.direccion || 'No registrada' }}</p>
                            </div>

                            <div class="mt-4 pt-4 border-t border-rose-100 bg-rose-50/50 -mx-5 px-5 pb-2">
                                <p class="text-xs text-rose-600 uppercase font-bold mb-2">En caso de emergencia</p>

                                <div>
                                    <p class="text-xs text-slate-500">Contactar a:</p>
                                    <p class="font-bold text-slate-800">{{ empleado.contacto_emergencia_nombre || 'No registrado' }}</p>
                                </div>

                                <div class="mt-1">
                                    <p class="text-xs text-slate-500">Teléfono:</p>
                                    <p class="font-bold text-slate-800">{{ empleado.contacto_emergencia_telefono || '--' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm md:col-span-2">
                        <div class="mb-4 flex flex-col gap-3 border-b border-blue-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="flex items-center gap-2 font-bold text-slate-800">
                                    <i class="ti ti-device-mobile text-xl text-blue-700" aria-hidden="true"></i>
                                    Acceso a la app móvil
                                </h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    Crea o cambia el usuario y contraseña temporal para que el empleado entre a Mi Lugarth.
                                </p>
                            </div>

                            <span
                                :class="accesoActivo ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500'"
                                class="inline-flex w-fit items-center gap-1 rounded-full border px-3 py-1 text-xs font-bold"
                            >
                                <i :class="accesoActivo ? 'ti ti-circle-check' : 'ti ti-circle-dashed'" aria-hidden="true"></i>
                                {{ accesoActivo ? 'Acceso activo' : 'Sin acceso activo' }}
                            </span>
                        </div>

                        <div v-if="accesoUsuario" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-blue-100 bg-white px-3 py-2">
                                <p class="text-xs font-bold uppercase text-blue-700">Usuario actual</p>
                                <p class="font-black text-slate-900">{{ accesoUsuario }}</p>
                            </div>

                            <div class="rounded-lg border border-blue-100 bg-white px-3 py-2">
                                <p class="text-xs font-bold uppercase text-blue-700">Correo interno Firebase</p>
                                <p class="break-all text-sm font-semibold text-slate-700">{{ accesoEmail || 'No registrado' }}</p>
                            </div>
                        </div>

                        <form class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_1fr_auto]" @submit.prevent="guardarAccesoApp">
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Usuario</label>
                                <input
                                    v-model="accesoForm.usuario"
                                    type="text"
                                    autocomplete="off"
                                    class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Ej. 43 o angel.patino"
                                />
                                <p v-if="accesoForm.errors.usuario" class="mt-1 text-xs font-semibold text-rose-600">
                                    {{ accesoForm.errors.usuario }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Contraseña temporal</label>
                                <input
                                    v-model="accesoForm.password"
                                    type="text"
                                    autocomplete="new-password"
                                    class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Mínimo 6 caracteres"
                                />
                                <p v-if="accesoForm.errors.password" class="mt-1 text-xs font-semibold text-rose-600">
                                    {{ accesoForm.errors.password }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-2 lg:justify-end">
                                <button
                                    type="submit"
                                    :disabled="accesoForm.processing"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                                    {{ accesoForm.processing ? 'Guardando...' : 'Guardar acceso' }}
                                </button>

                                <button
                                    v-if="accesoActivo"
                                    type="button"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-bold text-rose-700 transition hover:bg-rose-50"
                                    @click="desactivarAccesoApp"
                                >
                                    <i class="ti ti-user-off" aria-hidden="true"></i>
                                    Desactivar
                                </button>
                            </div>
                        </form>

                        <p v-if="accesoForm.errors.acceso_app" class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700">
                            {{ accesoForm.errors.acceso_app }}
                        </p>
                    </div>
                </div>

                <div v-show="tabActiva === 'nomina'" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in">
                    <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">
                            Esquema de Pago
                        </h3>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6">
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Salario Base</p>
                                <p class="text-2xl font-black text-emerald-600">
                                    <span v-if="esEstudiante">
                                        ${{ empleado.sueldo_por_hora }}
                                        <span class="text-sm font-medium text-slate-500">/ hora</span>
                                    </span>

                                    <span v-else>
                                        ${{ sueldoSemanalMostrado }}
                                        <span class="text-sm font-medium text-slate-500">/ semana</span>
                                    </span>
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Método de Pago</p>
                                <p class="text-lg font-bold text-slate-800">{{ empleado.forma_pago }}</p>
                                <p v-if="empleado.forma_pago === 'Deposito'" class="text-sm text-slate-500">
                                    {{ empleado.banco }} - Cta: {{ empleado.numero_cuenta }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">RFC</p>
                                <p class="font-medium">{{ empleado.rfc || '--' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">NSS (Seguro Social)</p>
                                <p class="font-medium">{{ empleado.nss || '--' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">
                            Deducciones Activas
                        </h3>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">IMSS</span>
                                <span class="font-bold text-rose-600">-${{ empleado.descuento_imss || '0.00' }}</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">ISR</span>
                                <span class="font-bold text-rose-600">-${{ empleado.descuento_isr || '0.00' }}</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">INFONAVIT</span>
                                <span class="font-bold text-rose-600">-${{ empleado.descuento_infonavit || '0.00' }}</span>
                            </div>

                            <div class="border-t border-slate-100 pt-3 mt-3">
                                <p class="text-xs text-slate-500 uppercase font-semibold mb-1">
                                    Préstamo Empresarial
                                </p>

                                <div
                                    :class="prestamoActivo ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                                    class="mb-2 rounded-lg border px-3 py-2"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-bold">
                                            {{ prestamoActivo ? 'Deuda pendiente' : 'Préstamo liquidado' }}
                                        </span>
                                        <span class="text-lg font-black">
                                            ${{ moneda(empleado.saldo_prestamo) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600">Abono Semanal</span>
                                    <span class="font-bold">-${{ moneda(empleado.cuota_prestamo) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-show="tabActiva === 'asistencia'" class="space-y-6 animate-fade-in">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 uppercase">
                                <i class="ti ti-timeline" aria-hidden="true"></i>
                                Antigüedad
                            </p>
                            <p class="text-2xl font-bold text-slate-800">{{ empleado.antiguedad_anios }} año(s)</p>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ empleado.fecha_reingreso ? 'Periodo actual' : 'Ingreso' }}: {{ empleado.fecha_inicio_periodo_actual || 'N/A' }}
                            </p>
                            <p v-if="empleado.fecha_reingreso" class="mt-1 text-[11px] font-semibold text-slate-400">Ingreso original: {{ empleado.fecha_ingreso || 'N/A' }}</p>
                        </div>

                        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 uppercase">
                                <i class="ti ti-calendar-event" aria-hidden="true"></i>
                                Días Ley
                            </p>
                            <p class="text-2xl font-bold text-teal-700">{{ empleado.dias_vacaciones_totales }}</p>
                            <p class="text-xs text-slate-500 mt-1">Tomados: {{ empleado.dias_vacaciones_tomados }}</p>
                        </div>

                        <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200 shadow-sm">
                            <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-800 uppercase">
                                <i class="ti ti-calendar-check" aria-hidden="true"></i>
                                Vacaciones Libres
                            </p>
                            <p class="text-3xl font-black text-emerald-600">{{ empleado.dias_vacaciones_restantes }}</p>
                            <p v-if="empleado.ajuste_vacaciones !== 0" class="text-xs text-emerald-700 mt-1">
                                Incluye ajuste: {{ empleado.ajuste_vacaciones }}
                            </p>
                        </div>

                        <div class="bg-rose-50 p-4 rounded-xl border border-rose-200 shadow-sm">
                            <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-800 uppercase">
                                <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                                Faltas Injustificadas
                            </p>
                            <p class="text-3xl font-black text-rose-600">{{ empleado.dias_faltas_totales }}</p>
                            <p class="text-xs text-rose-700 mt-1">Acumulado total</p>
                        </div>
                    </div>
                </div>

                <div v-if="esEstudiante" v-show="tabActiva === 'servicio'" class="space-y-5 animate-fade-in">
                    <section class="app-panel overflow-hidden">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title">Avance del servicio</h3>
                                <p class="panel-subtitle">Las horas cumplidas se obtienen de sus asistencias normales registradas.</p>
                            </div>
                            <span class="status-pill" :class="resumenServicio?.estado === 'Completado' ? 'status-success' : resumenServicio?.estado === 'Pausado' ? 'status-warning' : 'status-info'">
                                {{ resumenServicio?.estado || 'En curso' }}
                            </span>
                        </div>
                        <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4"><p class="field-label">Horas requeridas</p><p class="mt-1 text-2xl font-black text-blue-800">{{ formatoHoras(resumenServicio?.horas_requeridas) }}</p></div>
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"><p class="field-label">Horas cumplidas</p><p class="mt-1 text-2xl font-black text-emerald-700">{{ formatoHoras(resumenServicio?.horas_cumplidas) }}</p></div>
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4"><p class="field-label">Horas restantes</p><p class="mt-1 text-2xl font-black text-amber-700">{{ formatoHoras(resumenServicio?.horas_restantes) }}</p></div>
                            <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="field-label">Progreso</p><p class="mt-1 text-2xl font-black text-slate-900">{{ resumenServicio?.porcentaje || 0 }}%</p><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-teal-500" :style="{ width: `${Math.min(100, resumenServicio?.porcentaje || 0)}%` }"></div></div></div>
                        </div>
                        <div class="grid gap-3 border-t border-slate-100 bg-slate-50 p-5 text-sm sm:grid-cols-2 xl:grid-cols-4">
                            <p><span class="field-label block">Días registrados</span><strong>{{ resumenServicio?.dias_con_registro || 0 }}</strong></p>
                            <p><span class="field-label block">Promedio diario</span><strong>{{ formatoHoras(resumenServicio?.promedio_horas_dia) }} h</strong></p>
                            <p><span class="field-label block">Última asistencia</span><strong>{{ resumenServicio?.ultima_asistencia || 'Sin registros' }}</strong></p>
                            <p><span class="field-label block">Término estimado</span><strong>{{ resumenServicio?.fecha_estimada_termino || 'Por definir' }}</strong></p>
                        </div>
                    </section>

                    <form class="app-panel overflow-hidden" @submit.prevent="guardarServicioAlumno">
                        <div class="panel-header">
                            <div><h3 class="panel-title">Datos académicos</h3><p class="panel-subtitle">Estos datos aparecerán en el expediente, formatos semanales y carta de término.</p></div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a
                                    v-if="cartaLista"
                                    :href="route('empleados.servicio-alumno.carta-termino', empleado.id)"
                                    class="btn-secondary"
                                    title="Descargar carta de término editable en Word"
                                ><i class="ti ti-file-type-docx"></i>Carta de término</a>
                                <button v-else class="btn-secondary" type="button" disabled title="Guarda todos los datos requeridos para habilitar la carta"><i class="ti ti-file-type-docx"></i>Carta de término</button>
                                <button class="btn-primary" type="submit" :disabled="servicioForm.processing"><i class="ti ti-device-floppy"></i>{{ servicioForm.processing ? 'Guardando...' : 'Guardar datos' }}</button>
                            </div>
                        </div>
                        <div class="grid gap-4 p-5 md:grid-cols-2">
                            <label><span class="field-label">Universidad o institución *</span><input v-model="servicioForm.universidad" class="field-input-soft" required maxlength="255" /><span v-if="servicioForm.errors.universidad" class="mt-1 block text-xs font-bold text-rose-600">{{ servicioForm.errors.universidad }}</span></label>
                            <label><span class="field-label">Carrera</span><input v-model="servicioForm.carrera" class="field-input-soft" maxlength="255" /></label>
                            <label><span class="field-label">Matrícula</span><input v-model="servicioForm.matricula_estudiante" class="field-input-soft" maxlength="100" placeholder="Número asignado por la escuela" /></label>
                            <label><span class="field-label">Encargado de estadías de la escuela</span><input v-model="servicioForm.encargado_estadias_escuela" class="field-input-soft" maxlength="255" placeholder="Nombre y grado, por ejemplo: MTRA. ..." /></label>
                            <label><span class="field-label">Horas por cumplir *</span><input v-model="servicioForm.horas_servicio_requeridas" type="number" min="0" step="0.5" class="field-input-soft" required /><span v-if="servicioForm.errors.horas_servicio_requeridas" class="mt-1 block text-xs font-bold text-rose-600">{{ servicioForm.errors.horas_servicio_requeridas }}</span></label>
                            <label><span class="field-label">Inicio del servicio</span><input v-model="servicioForm.fecha_inicio_servicio" type="date" class="field-input-soft" /></label>
                            <label><span class="field-label">Fecha límite para terminar</span><input v-model="servicioForm.fecha_limite_servicio" type="date" class="field-input-soft" /><span class="mt-1 block text-xs font-semibold text-slate-500">Se compara con el término estimado para avisar si las horas podrían no completarse a tiempo.</span><span v-if="servicioForm.errors.fecha_limite_servicio" class="mt-1 block text-xs font-bold text-rose-600">{{ servicioForm.errors.fecha_limite_servicio }}</span></label>
                            <label><span class="field-label">Fecha real de término</span><input v-model="servicioForm.fecha_termino_servicio" type="date" class="field-input-soft" /><span class="mt-1 block text-xs font-semibold text-slate-500">Se imprime como el final del periodo en la carta.</span><span v-if="servicioForm.errors.fecha_termino_servicio" class="mt-1 block text-xs font-bold text-rose-600">{{ servicioForm.errors.fecha_termino_servicio }}</span></label>
                            <label><span class="field-label">Evaluación de la estadía</span><input v-model="servicioForm.evaluacion_estadia" type="number" min="0" max="100" step="0.01" class="field-input-soft" placeholder="Ejemplo: 9.07" /></label>
                            <div v-if="resumenServicio?.en_riesgo" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm font-bold text-amber-900 md:col-span-2"><i class="ti ti-alert-triangle mr-2"></i>{{ resumenServicio.mensaje_alerta }}</div>
                            <label class="md:col-span-2"><span class="field-label">Área o proyecto asignado</span><input v-model="servicioForm.area_proyecto_servicio" class="field-input-soft" maxlength="255" /></label>
                            <div v-if="!cartaLista" class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 md:col-span-2">
                                <p class="font-black"><i class="ti ti-info-circle mr-2"></i>Para habilitar la carta de término</p>
                                <p class="mt-1 font-semibold">Guarda estos datos: {{ faltantesCarta.join(', ') }}.</p>
                            </div>
                            <label class="md:col-span-2"><span class="field-label">Observaciones</span><textarea v-model="servicioForm.observaciones_servicio" rows="3" maxlength="2000" class="field-input-soft"></textarea></label>
                            <label class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 md:col-span-2"><input v-model="servicioForm.servicio_pausado" type="checkbox" class="mt-1 rounded border-amber-300 text-amber-600 focus:ring-amber-500" /><span><strong class="block text-sm text-amber-900">Servicio pausado</strong><span class="text-xs font-semibold text-amber-700">Úsalo cuando temporalmente no continuará acumulando actividad.</span></span></label>
                        </div>
                    </form>
                </div>

                <div v-show="tabActiva === 'documentacion'" class="space-y-5 animate-fade-in">
                    <section class="app-panel overflow-hidden">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title">Expediente documental</h3>
                                <p class="panel-subtitle">Archivos privados, optimizados y disponibles solo para personal autorizado.</p>
                            </div>
                            <span :class="['status-pill', documentProgress.loaded === documentProgress.total ? 'status-success' : 'status-warning']">
                                {{ documentProgress.loaded }} de {{ documentProgress.total }} completos
                            </span>
                        </div>
                        <div v-if="canManageDocuments" class="border-b border-slate-200 bg-blue-50 px-5 py-3 text-sm font-semibold text-blue-900 dark:border-slate-700 dark:bg-blue-950/30 dark:text-blue-200">
                            <i class="ti ti-info-circle mr-1" aria-hidden="true"></i>
                            Usa el botón de cada tarjeta. Al reemplazar, el archivo anterior se elimina automáticamente.
                        </div>

                        <div class="grid gap-3 p-5 md:grid-cols-2 xl:grid-cols-3">
                            <article v-for="type in documentTypes" :key="type.key" class="rounded-lg border p-4" :class="documentMap[type.key] ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-950/30' : 'border-amber-200 bg-amber-50/60 dark:border-amber-900 dark:bg-amber-950/30'">
                                <input
                                    v-if="canManageDocuments"
                                    :ref="element => setDocumentInput(type.key, element)"
                                    type="file"
                                    accept="application/pdf,image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="selectDocumentFile(type.key, $event)"
                                />
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" :class="documentMap[type.key] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                        <i :class="['ti text-xl', documentMap[type.key] ? 'ti-file-check' : 'ti-file-alert']"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ type.label }}</p>
                                        <template v-if="documentMap[type.key]">
                                            <p class="mt-1 text-xs font-semibold text-emerald-700">Cargado · {{ compressionText(documentMap[type.key]) }}</p>
                                            <p class="mt-1 truncate text-xs text-slate-500" :title="documentMap[type.key].original_name">{{ documentMap[type.key].original_name }}</p>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-400">Actualizado {{ new Date(documentMap[type.key].updated_at).toLocaleDateString('es-MX') }}<template v-if="documentMap[type.key].uploaded_by?.name"> por {{ documentMap[type.key].uploaded_by.name }}</template></p>
                                        </template>
                                        <p v-else class="mt-1 text-xs font-bold text-amber-700">Documento pendiente</p>
                                    </div>
                                </div>
                                <div v-if="documentMap[type.key]" class="mt-4 flex flex-wrap gap-2 border-t border-emerald-200 pt-3">
                                    <button v-if="canManageDocuments" class="btn-secondary" type="button" :disabled="documentUploading" @click="openDocumentPicker(type.key)">
                                        <i :class="['ti', documentUploading && selectedDocumentType === type.key ? 'ti-loader-2 animate-spin' : 'ti-refresh']"></i>
                                        {{ documentUploading && selectedDocumentType === type.key ? 'Reemplazando...' : 'Reemplazar' }}
                                    </button>
                                    <a class="btn-icon" target="_blank" rel="noopener" :href="route('empleados.documentos.view', [empleado.id, documentMap[type.key].id])" title="Visualizar documento"><i class="ti ti-eye"></i></a>
                                    <a class="btn-icon" :href="route('empleados.documentos.download', [empleado.id, documentMap[type.key].id])" title="Descargar documento"><i class="ti ti-download"></i></a>
                                    <button class="btn-icon" type="button" title="Imprimir documento" @click="printDocument(documentMap[type.key])"><i class="ti ti-printer"></i></button>
                                    <button v-if="canManageDocuments" class="btn-icon text-rose-600" type="button" title="Eliminar documento" @click="deleteDocument(documentMap[type.key])"><i class="ti ti-trash"></i></button>
                                </div>
                                <button v-else-if="canManageDocuments" type="button" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-emerald-700 bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-50" :disabled="documentUploading" @click="openDocumentPicker(type.key)">
                                    <i :class="['ti', documentUploading && selectedDocumentType === type.key ? 'ti-loader-2 animate-spin' : 'ti-upload']"></i>
                                    {{ documentUploading && selectedDocumentType === type.key ? 'Subiendo...' : 'Subir documento' }}
                                </button>
                                <div v-if="documentUploading && selectedDocumentType === type.key" class="mt-3">
                                    <div class="h-2 overflow-hidden rounded-full bg-blue-100"><div class="h-full bg-blue-600 transition-all" :style="{ width: `${documentUploadProgress}%` }"></div></div>
                                    <p class="mt-1 text-xs font-bold text-blue-700">Subiendo y optimizando · {{ documentUploadProgress }}%</p>
                                </div>
                                <p v-if="selectedDocumentType === type.key && (documentErrors.file || documentErrors.document_type)" class="mt-2 text-xs font-bold text-rose-600">{{ documentErrors.file || documentErrors.document_type }}</p>
                            </article>
                        </div>
                    </section>
                </div>

                <div v-show="tabActiva === 'historial'" class="app-panel animate-fade-in">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Línea de tiempo del expediente</h3>
                            <p class="panel-subtitle">Altas, cambios de datos, baja y restauración registrados por el sistema.</p>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <article v-for="reingreso in (empleado.reingresos || [])" :key="`reingreso-${reingreso.id}`" class="flex gap-4 bg-emerald-50/40 p-5">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-100 text-emerald-700">
                                <i class="ti ti-user-check" aria-hidden="true"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-black text-slate-900">Reingreso laboral · {{ reingreso.fecha_reingreso }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    Baja anterior: {{ reingreso.fecha_baja_anterior || 'Sin fecha' }}
                                    · Registró: {{ reingreso.usuario_registro?.name || 'Sistema' }}
                                </p>
                            </div>
                        </article>
                        <article v-for="registro in timeline" :key="registro.id" class="flex gap-4 p-5">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700">
                                <i class="ti ti-history-toggle" aria-hidden="true"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-black text-slate-900">{{ registro.description || registro.event }}</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">{{ registro.user || 'Sistema' }}</p>
                                    </div>
                                    <time class="text-xs font-bold text-slate-500">{{ new Date(registro.created_at).toLocaleString('es-MX') }}</time>
                                </div>
                                <details v-if="registro.old_values || registro.new_values" class="mt-3 rounded-lg border border-slate-200 bg-slate-50">
                                    <summary class="cursor-pointer px-3 py-2 text-xs font-black text-slate-600">Ver datos modificados</summary>
                                    <div class="grid gap-3 border-t border-slate-200 p-3 text-xs md:grid-cols-2">
                                        <pre class="overflow-auto whitespace-pre-wrap text-rose-700">{{ JSON.stringify(registro.old_values || {}, null, 2) }}</pre>
                                        <pre class="overflow-auto whitespace-pre-wrap text-emerald-700">{{ JSON.stringify(registro.new_values || {}, null, 2) }}</pre>
                                    </div>
                                </details>
                            </div>
                        </article>
                        <div v-if="timeline.length === 0 && !(empleado.reingresos || []).length" class="empty-state">
                            Aún no hay movimientos registrados para este expediente.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL EDITAR FECHA DE BAJA -->
        <div
            v-if="modalEditarBaja"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
                <div class="mb-4 flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-900">
                            Editar fecha de baja
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Modifica la fecha en la que el empleado fue dado de baja.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        @click="cerrarModalEditarBaja"
                    >
                        <i class="ti ti-x text-xl" aria-hidden="true"></i>
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="guardarFechaBaja">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-600">
                            Fecha de baja
                        </label>

                        <input
                            v-model="bajaForm.fecha_baja"
                            type="date"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                        />

                        <p
                            v-if="bajaForm.errors.fecha_baja"
                            class="mt-1 text-xs font-semibold text-rose-600"
                        >
                            {{ bajaForm.errors.fecha_baja }}
                        </p>
                    </div>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50"
                            @click="cerrarModalEditarBaja"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            :disabled="bajaForm.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <i class="ti ti-device-floppy" aria-hidden="true"></i>
                            {{ bajaForm.processing ? 'Guardando...' : 'Guardar cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="modalEditarReingreso" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" @click.self="cerrarModalEditarReingreso">
            <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-2xl">
                <div class="mb-4 flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-900">Editar fecha de reingreso</h3>
                        <p class="mt-1 text-sm text-slate-500">Esta fecha inicia la antigüedad y vacaciones del periodo laboral actual.</p>
                    </div>
                    <button type="button" class="icon-button" title="Cerrar" aria-label="Cerrar" @click="cerrarModalEditarReingreso"><i class="ti ti-x"></i></button>
                </div>
                <form class="space-y-4" @submit.prevent="guardarFechaReingreso">
                    <div>
                        <label class="field-label" for="fecha-reingreso-perfil">Fecha de reingreso</label>
                        <input id="fecha-reingreso-perfil" v-model="reingresoForm.fecha_reingreso" type="date" :max="new Date().toISOString().substring(0, 10)" class="field-input" required />
                        <p v-if="reingresoForm.errors.fecha_reingreso" class="mt-1 text-xs font-semibold text-rose-600">{{ reingresoForm.errors.fecha_reingreso }}</p>
                    </div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-900">
                        El ingreso original y los periodos anteriores permanecerán en el historial. Los cálculos actuales se actualizarán con esta fecha.
                    </div>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" class="btn-secondary" @click="cerrarModalEditarReingreso">Cancelar</button>
                        <button type="submit" class="btn-primary" :disabled="reingresoForm.processing"><i class="ti ti-device-floppy"></i>{{ reingresoForm.processing ? 'Guardando...' : 'Guardar cambios' }}</button>
                    </div>
                </form>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="modalFoto" class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Cambiar fotografía" @click.self="cerrarEditorFotoPerfil">
                <form class="w-full max-w-lg overflow-hidden rounded-lg border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900" @submit.prevent="guardarFotoPerfil">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-200 p-5 dark:border-slate-700">
                        <div>
                            <h3 class="text-lg font-black text-slate-950 dark:text-white">Cambiar fotografía</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">La fotografía anterior se eliminará automáticamente para no ocupar espacio.</p>
                        </div>
                        <button type="button" class="icon-button" title="Cerrar" aria-label="Cerrar" @click="cerrarEditorFotoPerfil"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="relative mx-auto flex h-40 w-40 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-gradient-to-br from-teal-600 to-emerald-800 text-4xl font-black text-white shadow-lg">
                            <span>{{ iniciales }}</span>
                            <img v-if="fotoPreview || fotoDisponible" :src="fotoPreview || profilePhotoSrc" :alt="`Vista previa de ${empleado.nombre_completo}`" class="absolute h-40 w-40 rounded-full object-cover" />
                        </div>
                        <label class="block">
                            <span class="field-label">Nueva fotografía</span>
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-700 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-white hover:file:bg-blue-800" required @change="seleccionarFotoPerfil" />
                            <span class="mt-1 block text-xs font-semibold text-slate-500">JPG, PNG o WebP, máximo 5 MB.</span>
                            <span v-if="fotoForm.errors.foto" class="mt-1 block text-xs font-bold text-rose-600">{{ fotoForm.errors.foto }}</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-200 p-4 dark:border-slate-700">
                        <button type="button" class="btn-secondary" :disabled="fotoForm.processing" @click="cerrarEditorFotoPerfil">Cancelar</button>
                        <button type="submit" class="btn-primary" :disabled="fotoForm.processing || !fotoForm.foto"><i :class="['ti', fotoForm.processing ? 'ti-loader-2 animate-spin' : 'ti-device-floppy']"></i>{{ fotoForm.processing ? 'Guardando...' : 'Guardar fotografía' }}</button>
                    </div>
                </form>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="modalDatosPersonales" class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Actualizar información personal" @click.self="cerrarDatosPersonales">
                <form class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900" @submit.prevent="guardarDatosPersonales">
                    <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                        <div>
                            <h3 class="text-lg font-black text-slate-950 dark:text-white">Actualizar información personal</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Completa solamente el expediente; sueldo, puesto y banco no se modificarán.</p>
                        </div>
                        <button type="button" class="icon-button" title="Cerrar" aria-label="Cerrar" @click="cerrarDatosPersonales"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <label><span class="field-label">CURP</span><input v-model="personalForm.curp" maxlength="18" class="field-input-soft uppercase" autocomplete="off" /><span v-if="personalForm.errors.curp" class="mt-1 block text-xs font-bold text-rose-600">{{ personalForm.errors.curp }}</span></label>
                        <label><span class="field-label">RFC</span><input v-model="personalForm.rfc" maxlength="20" class="field-input-soft uppercase" autocomplete="off" /><span v-if="personalForm.errors.rfc" class="mt-1 block text-xs font-bold text-rose-600">{{ personalForm.errors.rfc }}</span></label>
                        <label><span class="field-label">Número de Seguro Social</span><input v-model="personalForm.nss" maxlength="20" class="field-input-soft" inputmode="numeric" /><span v-if="personalForm.errors.nss" class="mt-1 block text-xs font-bold text-rose-600">{{ personalForm.errors.nss }}</span></label>
                        <label><span class="field-label">Celular personal</span><input v-model="personalForm.telefono" maxlength="20" class="field-input-soft" inputmode="tel" /><span v-if="personalForm.errors.telefono" class="mt-1 block text-xs font-bold text-rose-600">{{ personalForm.errors.telefono }}</span></label>
                        <label><span class="field-label">Nombre del contacto de emergencia</span><input v-model="personalForm.contacto_emergencia_nombre" maxlength="255" class="field-input-soft" /><span v-if="personalForm.errors.contacto_emergencia_nombre" class="mt-1 block text-xs font-bold text-rose-600">{{ personalForm.errors.contacto_emergencia_nombre }}</span></label>
                        <label><span class="field-label">Teléfono de emergencia</span><input v-model="personalForm.contacto_emergencia_telefono" maxlength="20" class="field-input-soft" inputmode="tel" /><span v-if="personalForm.errors.contacto_emergencia_telefono" class="mt-1 block text-xs font-bold text-rose-600">{{ personalForm.errors.contacto_emergencia_telefono }}</span></label>
                        <label class="sm:col-span-2"><span class="field-label">Fecha de ingreso</span><input v-model="personalForm.fecha_ingreso" type="date" :max="new Date().toISOString().substring(0, 10)" class="field-input-soft" /><span class="mt-1 block text-xs font-semibold text-slate-500">Esta fecha influye en antigüedad y vacaciones cuando no existe un reingreso posterior.</span><span v-if="personalForm.errors.fecha_ingreso" class="mt-1 block text-xs font-bold text-rose-600">{{ personalForm.errors.fecha_ingreso }}</span></label>
                    </div>
                    <div class="sticky bottom-0 flex justify-end gap-2 border-t border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                        <button type="button" class="btn-secondary" :disabled="personalForm.processing" @click="cerrarDatosPersonales">Cancelar</button>
                        <button type="submit" class="btn-primary" :disabled="personalForm.processing"><i :class="['ti', personalForm.processing ? 'ti-loader-2 animate-spin' : 'ti-device-floppy']"></i>{{ personalForm.processing ? 'Guardando...' : 'Guardar información' }}</button>
                    </div>
                </form>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="documentToDelete" class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" @click.self="documentToDelete = null">
                <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700"><i class="ti ti-trash text-xl"></i></div>
                        <div>
                            <h3 class="text-lg font-black text-slate-950 dark:text-white">Eliminar documento</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Se quitará del expediente y se borrará el archivo para liberar espacio. Esta acción no se puede deshacer.</p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" class="btn-secondary" :disabled="documentDeleting" @click="documentToDelete = null">Cancelar</button>
                        <button type="button" class="btn-danger" :disabled="documentDeleting" @click="confirmDeleteDocument"><i :class="['ti', documentDeleting ? 'ti-loader-2 animate-spin' : 'ti-trash']"></i>{{ documentDeleting ? 'Eliminando...' : 'Eliminar archivo' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="fotoAmpliada"
                class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                :aria-label="`Fotografia de ${empleado.nombre_completo}`"
                @click.self="cerrarFotoEmpleado"
            >
                <div class="relative max-h-[92vh] w-full max-w-3xl overflow-hidden rounded-xl border border-white/20 bg-slate-950 shadow-2xl">
                    <button
                        type="button"
                        class="absolute right-3 top-3 z-10 flex h-10 w-10 items-center justify-center rounded-lg border border-white/20 bg-slate-950/70 text-xl text-white transition hover:bg-white hover:text-slate-950"
                        title="Cerrar fotografia"
                        aria-label="Cerrar fotografia"
                        @click="cerrarFotoEmpleado"
                    >
                        <i class="ti ti-x" aria-hidden="true"></i>
                    </button>
                    <img
                        :src="profilePhotoSrc"
                        :alt="`Foto de ${empleado.nombre_completo}`"
                        class="max-h-[84vh] w-full bg-slate-900 object-contain"
                        @load="mostrarFotoEmpleado"
                        @error="probarSiguienteFotoEmpleado(empleado, $event)"
                    />
                    <div class="border-t border-white/10 bg-slate-950 px-5 py-4 text-white">
                        <p class="text-base font-black">{{ empleado.nombre_completo }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-300">No. empleado {{ numeroEmpleado }}</p>
                    </div>
                </div>
            </div>
        </Teleport>
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
