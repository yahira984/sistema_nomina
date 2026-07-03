<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { clavesFotoEmpleado, fotoEmpleadoSrc, mostrarFotoEmpleado, probarSiguienteFotoEmpleado } from '@/Utils/employeePhotos';

const props = defineProps({
    empleado: Object,
    accesoApp: {
        type: Object,
        default: () => ({}),
    },
});

const tabActiva = ref('perfil');

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

// Saca las iniciales del nombre (Ej: Kevin Yahir Avila -> KY)
const iniciales = computed(() => {
    if (!props.empleado.nombre_completo) return 'EM';
    return props.empleado.nombre_completo.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
});

const esEstudiante = computed(() => Boolean(Number(props.empleado.es_estudiante ?? 0)));

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

                    <div class="relative z-10 flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-gradient-to-br from-teal-600 to-emerald-800 text-2xl font-black text-white shadow-lg sm:h-24 sm:w-24 sm:text-3xl">
                        <span>{{ iniciales }}</span>
                        <img
                            v-if="clavesFotoEmpleado(empleado).length"
                            :src="fotoEmpleadoSrc(empleado)"
                            :alt="`Foto de ${empleado.nombre_completo}`"
                            loading="lazy"
                            decoding="async"
                            class="absolute inset-0 h-full w-full object-cover"
                            @load="mostrarFotoEmpleado"
                            @error="probarSiguienteFotoEmpleado(empleado, $event)"
                        />
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
                        </div>
                    </div>
                </div>

                <div v-show="tabActiva === 'perfil'" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in">
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
                            <p class="text-xs text-slate-500 mt-1">Ingreso: {{ empleado.fecha_ingreso || 'N/A' }}</p>
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
