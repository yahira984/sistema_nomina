<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { clavesFotoEmpleado, fotoEmpleadoSrc, mostrarFotoEmpleado, probarSiguienteFotoEmpleado } from '@/Utils/employeePhotos';

const props = defineProps({
    empleados: Array,
    empleadosMeta: { type: Object, default: () => ({}) },
    resumen: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
});
const page = usePage();
const canManage = computed(() => page.props.auth?.can?.['empleados.manage'] ?? false);

const editando = ref(false);
const empleadoId = ref(null);
const searchQuery = ref(props.filtros.search || '');
const filtroEstado = ref(props.filtros.status || 'activos');
const criterioOrdenDirectorio = ref(props.filtros.sort || 'num_asc');
const vistaPreferida = page.props.auth?.user?.preferences?.default_view === 'grid' ? 'cuadricula' : 'tabla';
const vistaDirectorio = ref(localStorage.getItem('empleados:vista') || vistaPreferida);
const empleadoFotoAmpliada = ref(null);
const empleadoFotoEdicion = ref(null);
const empleadoRestauracion = ref(null);
const empleadoBaja = ref(null);
const fotoPreview = ref('');
const fotosDisponibles = ref(new Set());
const fotoForm = useForm({ foto: null });
const restaurarForm = useForm({ fecha_reingreso: '' });
const bajaEmpleadoForm = useForm({ fecha_baja: '', motivo_baja: '' });

const fechaLocalHoy = () => {
    const hoy = new Date();
    const offset = hoy.getTimezoneOffset() * 60000;
    return new Date(hoy.getTime() - offset).toISOString().substring(0, 10);
};

const marcarFotoDisponible = (empleado, event) => {
    mostrarFotoEmpleado(event);
    fotosDisponibles.value = new Set([...fotosDisponibles.value, Number(empleado.id)]);
};

const fotoDisponible = (empleado) => fotosDisponibles.value.has(Number(empleado.id));

const abrirFotoEmpleado = (empleado) => {
    if (!clavesFotoEmpleado(empleado).length) return;
    empleadoFotoAmpliada.value = empleado;
};

const cerrarFotoEmpleado = () => {
    empleadoFotoAmpliada.value = null;
};

const abrirEditorFoto = (empleado) => {
    empleadoFotoEdicion.value = empleado;
    fotoForm.reset();
    fotoForm.clearErrors();
    fotoPreview.value = '';
};

const cerrarEditorFoto = () => {
    if (fotoPreview.value) URL.revokeObjectURL(fotoPreview.value);
    fotoPreview.value = '';
    fotoForm.reset();
    fotoForm.clearErrors();
    empleadoFotoEdicion.value = null;
};

const seleccionarFoto = (event) => {
    const archivo = event.target.files?.[0] || null;
    if (fotoPreview.value) URL.revokeObjectURL(fotoPreview.value);
    fotoForm.foto = archivo;
    fotoPreview.value = archivo ? URL.createObjectURL(archivo) : '';
};

const guardarFoto = () => {
    if (!empleadoFotoEdicion.value || !fotoForm.foto) return;

    const empleado = empleadoFotoEdicion.value;
    fotoForm.post(route('empleados.foto.actualizar', empleado.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            fotosDisponibles.value = new Set([...fotosDisponibles.value, Number(empleado.id)]);
            cerrarEditorFoto();
            recargarEmpleados();
        },
    });
};

const manejarTeclaFoto = (event) => {
    if (event.key === 'Escape') cerrarFotoEmpleado();
};

onMounted(() => window.addEventListener('keydown', manejarTeclaFoto));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', manejarTeclaFoto);
    if (fotoPreview.value) URL.revokeObjectURL(fotoPreview.value);
});

const normalizarNumeroEmpleado = (numero) => {
    const texto = String(numero || '').trim();
    return texto.replace(/^0+/, '') || texto || '';
};

const empleadoActivo = (empleado) => Boolean(Number(empleado.estatus ?? 0));

const numeroDirectorio = (empleado) => {
    if (empleadoActivo(empleado)) return empleado.numero_empleado || '';

    return empleado.numero_empleado || empleado.numero_empleado_baja || '';
};

const valorNumeroEmpleado = (empleado) => {
    const valor = parseInt(normalizarNumeroEmpleado(numeroDirectorio(empleado)), 10);
    return Number.isFinite(valor) ? valor : Number.MAX_SAFE_INTEGER;
};

const ordenarEmpleadosDirectorio = (empleados) => {
    return [...empleados].sort((a, b) => {
        if (criterioOrdenDirectorio.value === 'num_asc' || criterioOrdenDirectorio.value === 'num_desc') {
            const diferencia = valorNumeroEmpleado(a) - valorNumeroEmpleado(b);
            if (diferencia !== 0) return criterioOrdenDirectorio.value === 'num_asc' ? diferencia : -diferencia;
        }
        const nombreA = String(a.nombre_completo || '');
        const nombreB = String(b.nombre_completo || '');
        return criterioOrdenDirectorio.value === 'nombre_desc' ? nombreB.localeCompare(nombreA, 'es') : nombreA.localeCompare(nombreB, 'es');
    });
};

const form = useForm({
    numero_empleado: '', nombre_completo: '', puesto: '', fecha_ingreso: '', ajuste_vacaciones: 0,
    forma_pago: 'Efectivo', es_estudiante: false, sueldo_semanal: '', sueldo_por_hora: '',
    saldo_prestamo: '', cuota_prestamo: '', descuento_imss: '', descuento_isr: '',
    descuento_infonavit: '', banco: '', numero_cuenta: '', nss: '', rfc: '', curp: '',
    estado_civil: '', genero: '', fecha_nacimiento: '', telefono: '', correo: '',
    direccion: '', contacto_emergencia_nombre: '', contacto_emergencia_telefono: ''
});

const empleadosFiltrados = computed(() => {
    let resultado = props.empleados.filter(emp => {
        if (filtroEstado.value === 'activos') return empleadoActivo(emp);
        if (filtroEstado.value === 'prestamo') return empleadoActivo(emp) && Number(emp.saldo_prestamo ?? 0) > 0;
        if (filtroEstado.value === 'papelera') return !empleadoActivo(emp);
        return true;
    });

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        resultado = resultado.filter(emp => 
            emp.nombre_completo.toLowerCase().includes(query) || 
            (emp.numero_empleado && emp.numero_empleado.toLowerCase().includes(query)) ||
            (emp.numero_empleado_baja && emp.numero_empleado_baja.toLowerCase().includes(query))
        );
    }
    if (filtroEstado.value === 'prestamo') {
        return [...resultado].sort((a, b) => {
            const deudaA = Number(a.saldo_prestamo ?? 0);
            const deudaB = Number(b.saldo_prestamo ?? 0);
            return deudaB - deudaA;
        });
    }
    return ordenarEmpleadosDirectorio(resultado);
});

const empleadosActivos = computed(() => Number(props.resumen.activos ?? 0));
const empleadosBaja = computed(() => Number(props.resumen.bajas ?? 0));
const tituloDirectorio = computed(() => {
    if (filtroEstado.value === 'papelera') return 'Papelera de bajas';
    if (filtroEstado.value === 'prestamo') return 'Empleados con préstamo';
    return 'Directorio activo';
});

const esEstudiante = (empleado) => Boolean(Number(empleado.es_estudiante ?? 0));

const sueldoSemanalEmpleado = (empleado) => {
    const sueldoSemanal = Number(empleado.sueldo_semanal ?? 0);
    if (sueldoSemanal > 0) return sueldoSemanal.toFixed(2);
    const sueldoPorHora = Number(empleado.sueldo_por_hora ?? 0);
    return sueldoPorHora > 0 ? (sueldoPorHora * 56).toFixed(2) : '0.00';
};

const moneda = (valor) => Number(valor ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const saldoPrestamoEmpleado = (empleado) => Number(empleado.saldo_prestamo ?? 0);
const tieneDeuda = (empleado) => saldoPrestamoEmpleado(empleado) > 0;
const empleadosConDeuda = computed(() => Number(props.resumen.con_deuda ?? 0));

const cambiarVista = (vista) => {
    vistaDirectorio.value = vista;
    localStorage.setItem('empleados:vista', vista);
    window.axios?.patch(route('preferencias.update'), {
        default_view: vista === 'cuadricula' ? 'grid' : 'list',
    }).catch(() => {});
};

let filtroTimer = null;
const cargarDirectorio = (pageNumber = 1) => {
    router.get(route('empleados.index'), {
        search: searchQuery.value || undefined,
        status: filtroEstado.value,
        sort: criterioOrdenDirectorio.value,
        page: pageNumber,
    }, {
        only: ['empleados', 'empleadosMeta', 'resumen', 'filtros'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

watch([searchQuery, filtroEstado, criterioOrdenDirectorio], () => {
    clearTimeout(filtroTimer);
    filtroTimer = setTimeout(() => {
        window.axios?.patch(route('preferencias.update'), {
            filter_key: 'employees',
            filter_value: {
                search: searchQuery.value,
                status: filtroEstado.value,
                sort: criterioOrdenDirectorio.value,
            },
        }).catch(() => {});
        cargarDirectorio(1);
    }, 300);
});

const submitForm = () => {
    if (Number(form.saldo_prestamo || 0) <= 0) form.cuota_prestamo = 0;
    if (editando.value) {
        form.put(route('empleados.update', empleadoId.value), { onSuccess: () => cancelarEdicion() });
    } else {
        form.post(route('empleados.store'), { onSuccess: () => form.reset() });
    }
};

const editarEmpleado = (empleado) => {
    editando.value = true;
    empleadoId.value = empleado.id;
    Object.keys(form.data()).forEach(key => form[key] = empleado[key] ?? form[key]);
    form.ajuste_vacaciones = empleado.ajuste_vacaciones || 0;
    form.forma_pago = empleado.forma_pago || 'Efectivo';
    form.es_estudiante = esEstudiante(empleado);
    form.cuota_prestamo = saldoPrestamoEmpleado(empleado) > 0 ? (empleado.cuota_prestamo || '') : 0;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelarEdicion = () => { editando.value = false; empleadoId.value = null; form.reset(); };
const recargarEmpleados = () => router.reload({
    only: ['empleados', 'empleadosMeta', 'resumen'],
    preserveScroll: true,
    preserveState: true,
});
const abrirBajaEmpleado = (empleado) => {
    empleadoBaja.value = empleado;
    bajaEmpleadoForm.fecha_baja = fechaLocalHoy();
    bajaEmpleadoForm.motivo_baja = '';
    bajaEmpleadoForm.clearErrors();
};
const cerrarBajaEmpleado = () => {
    empleadoBaja.value = null;
    bajaEmpleadoForm.reset();
    bajaEmpleadoForm.clearErrors();
};
const eliminarEmpleado = () => {
    if (!empleadoBaja.value) return;

    bajaEmpleadoForm.delete(route('empleados.destroy', empleadoBaja.value.id), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            filtroEstado.value = 'papelera';
            cerrarBajaEmpleado();
            recargarEmpleados();
        },
    });
};
const abrirRestauracion = (empleado) => {
    empleadoRestauracion.value = empleado;
    restaurarForm.fecha_reingreso = fechaLocalHoy();
    restaurarForm.clearErrors();
};

const cerrarRestauracion = () => {
    empleadoRestauracion.value = null;
    restaurarForm.reset();
    restaurarForm.clearErrors();
};

const restaurarEmpleado = () => {
    if (!empleadoRestauracion.value) return;

    restaurarForm.put(route('empleados.restaurar', empleadoRestauracion.value.id), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: (pageResponse) => {
            const mensaje = pageResponse.props.flash?.success;
            if (mensaje) window.alert(mensaje);
            cerrarRestauracion();
            recargarEmpleados();
        },
    });
};
</script>

<template>
    <Head title="Control de Empleados" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-4">
                <Link :href="route('dashboard')" class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 transition-all hover:bg-blue-50 hover:text-blue-600">
                    <i class="ti ti-arrow-left text-2xl"></i>
                </Link>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Gestión de Personal</p>
                    <h2 class="font-['Sora'] text-2xl font-extrabold text-slate-900 dark:text-white">Directorio de Empleados</h2>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Formulario Bento Box -->
            <section v-if="canManage" :class="['relative overflow-hidden rounded-3xl bg-white border shadow-sm transition-all duration-300', editando ? 'border-amber-300 shadow-amber-500/10 shadow-xl' : 'border-slate-200/60']">
                <div :class="['absolute top-0 left-0 w-1.5 h-full', editando ? 'bg-amber-400' : 'bg-blue-600']"></div>
                
                <div class="border-b border-slate-100 px-6 py-5 sm:px-8 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-4">
                        <div :class="['flex h-12 w-12 items-center justify-center rounded-2xl shadow-inner text-2xl', editando ? 'bg-amber-100 text-amber-600 border border-amber-200' : 'bg-blue-100 text-blue-600 border border-blue-200']">
                            <i :class="editando ? 'ti ti-user-edit' : 'ti ti-user-plus'"></i>
                        </div>
                        <div>
                            <h3 class="font-['Sora'] text-lg font-bold text-slate-900">{{ editando ? 'Actualizar expediente' : 'Alta de trabajador' }}</h3>
                            <p class="text-xs font-medium text-slate-500">Completa el perfil para automatizar nóminas y asistencias.</p>
                        </div>
                    </div>
                    <button v-if="editando" @click="cancelarEdicion" class="hidden sm:flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-200">
                        <i class="ti ti-x"></i> Cancelar
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 sm:p-8">
                    <!-- Fila 1: Datos Base -->
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-12 mb-8">
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">NO.EMPLEADO</label>
                            <input v-model="form.numero_empleado" type="text" maxlength="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-900 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="Ej. 84" @input="form.numero_empleado = form.numero_empleado.replace(/\D/g, '')" />
                        </div>
                        <div class="md:col-span-4">
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nombre completo <span class="text-rose-500">*</span></label>
                            <input v-model="form.nombre_completo" type="text" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-900 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="Nombre y apellidos" @input="form.nombre_completo = form.nombre_completo.replace(/[0-9]/g, '')" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Puesto</label>
                            <input v-model="form.puesto" type="text" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-900 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="Ej. Operador" @input="form.puesto = form.puesto.replace(/[0-9]/g, '')" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Ingreso</label>
                            <input v-model="form.fecha_ingreso" type="date" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-900 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all" />
                        </div>
                    </div>

                    <!-- Fila 2: Salarios y Descuentos -->
                    <div class="mb-8 rounded-2xl bg-slate-50 p-6 border border-slate-100">
                        <div class="mb-4 flex items-center gap-2">
                            <i class="ti ti-coin text-emerald-600 text-lg"></i>
                            <h4 class="font-bold text-slate-800">Condiciones de Pago</h4>
                        </div>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                            <div class="md:col-span-3">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Método <span class="text-rose-500">*</span></label>
                                <select v-model="form.forma_pago" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Deposito">Depósito / Transferencia</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2 flex items-center pt-6">
                                <label class="flex items-center gap-2 cursor-pointer bg-white border border-slate-200 px-4 py-2.5 rounded-xl w-full hover:bg-slate-50">
                                    <input type="checkbox" v-model="form.es_estudiante" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                    <span class="text-xs font-bold text-slate-700">Estudiante</span>
                                </label>
                            </div>

                            <template v-if="form.es_estudiante">
                                <div class="md:col-span-3">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-teal-600">Tarifa x Hora ($) <span class="text-rose-500">*</span></label>
                                    <input v-model="form.sueldo_por_hora" type="number" step="0.01" class="w-full rounded-xl border border-teal-200 bg-white px-4 py-2.5 text-sm font-black text-teal-900 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 transition-all" placeholder="0.00" />
                                </div>
                            </template>
                            <template v-else>
                                <div class="md:col-span-3">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-emerald-600">Base Semanal ($) <span class="text-rose-500">*</span></label>
                                    <input v-model="form.sueldo_semanal" type="number" step="0.01" class="w-full rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm font-black text-emerald-900 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all" placeholder="0.00" />
                                </div>
                            </template>

                            <div class="md:col-span-2">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-amber-600">Deuda Total ($)</label>
                                <input v-model="form.saldo_prestamo" type="number" step="0.01" class="w-full rounded-xl border border-amber-200 bg-white px-4 py-2.5 text-sm font-black text-amber-900 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all" placeholder="0.00" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-rose-500">Desc. Préstamo ($)</label>
                                <input v-model="form.cuota_prestamo" type="number" step="0.01" class="w-full rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-black text-rose-900 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all" placeholder="0.00" />
                            </div>
                        </div>
                    </div>

                    <!-- Fila 3: Opcionales Agrupados -->
                    <details class="group mb-8 [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex cursor-pointer items-center justify-between rounded-xl bg-slate-50 px-6 py-4 font-bold text-slate-700 hover:bg-slate-100 transition-colors">
                            <span class="flex items-center gap-2"><i class="ti ti-adjustments-horizontal text-lg"></i> Más datos (Bancos, Impuestos, Contacto)</span>
                            <span class="transition group-open:rotate-180"><i class="ti ti-chevron-down"></i></span>
                        </summary>
                        <div class="pt-6 grid grid-cols-1 gap-5 md:grid-cols-12 px-2">
                            <!-- Impuestos -->
                            <div class="md:col-span-3">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Desc. IMSS</label>
                                <input v-model="form.descuento_imss" type="number" step="0.01" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" />
                            </div>
                            <div class="md:col-span-3">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Desc. ISR</label>
                                <input v-model="form.descuento_isr" type="number" step="0.01" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" />
                            </div>
                            <div class="md:col-span-3">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">INFONAVIT</label>
                                <input v-model="form.descuento_infonavit" type="number" step="0.01" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" />
                            </div>
                            <div class="md:col-span-3">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-blue-500">Ajuste Vacaciones</label>
                                <input v-model="form.ajuste_vacaciones" type="number" class="w-full rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-bold" placeholder="-2" />
                            </div>

                            <!-- Banco -->
                            <template v-if="form.forma_pago === 'Deposito'">
                                <div class="md:col-span-6">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Banco <span class="text-rose-500">*</span></label>
                                    <input v-model="form.banco" type="text" :required="form.forma_pago === 'Deposito'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" />
                                </div>
                                <div class="md:col-span-6">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Cuenta o CLABE <span class="text-rose-500">*</span></label>
                                    <input v-model="form.numero_cuenta" type="text" :required="form.forma_pago === 'Deposito'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" />
                                </div>
                            </template>

                            <!-- Personales -->
                            <div class="md:col-span-4"><label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">CURP</label><input v-model="form.curp" type="text" maxlength="18" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold uppercase" @input="form.curp = form.curp.toUpperCase().replace(/[^A-Z0-9]/g, '')" /></div>
                            <div class="md:col-span-4"><label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">RFC</label><input v-model="form.rfc" type="text" maxlength="13" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold uppercase" @input="form.rfc = form.rfc.toUpperCase().replace(/[^A-Z0-9&]/g, '')" /></div>
                            <div class="md:col-span-4"><label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">NSS</label><input v-model="form.nss" type="text" maxlength="11" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" @input="form.nss = form.nss.replace(/\D/g, '')" /></div>
                            
                            <!-- Generales -->
                            <div class="md:col-span-4"><label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nacimiento</label><input v-model="form.fecha_nacimiento" type="date" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" /></div>
                            <div class="md:col-span-4">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">G&eacute;nero</label>
                                <select v-model="form.genero" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold">
                                    <option value="">Sin registrar</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="md:col-span-4">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Estado civil</label>
                                <select v-model="form.estado_civil" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold">
                                    <option value="">Sin registrar</option>
                                    <option value="Soltero(a)">Soltero(a)</option>
                                    <option value="Casado(a)">Casado(a)</option>
                                    <option value="Uni&oacute;n libre">Uni&oacute;n libre</option>
                                    <option value="Divorciado(a)">Divorciado(a)</option>
                                    <option value="Viudo(a)">Viudo(a)</option>
                                </select>
                            </div>
                            <div class="md:col-span-4">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Correo electr&oacute;nico</label>
                                <input v-model="form.correo" type="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" placeholder="correo@empresa.com" />
                            </div>
                            <div class="md:col-span-8">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Direcci&oacute;n</label>
                                <input v-model="form.direccion" type="text" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" placeholder="Calle, numero, colonia" />
                            </div>
                            <div class="md:col-span-4">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-rose-500">Emergencia (Nombre)</label>
                                <input v-model="form.contacto_emergencia_nombre" type="text" class="w-full rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-bold" placeholder="Nombre completo" @input="form.contacto_emergencia_nombre = form.contacto_emergencia_nombre.replace(/[0-9]/g, '')" />
                            </div>
                            <div class="md:col-span-4"><label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Teléfono</label><input v-model="form.telefono" type="text" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold" @input="form.telefono = form.telefono.replace(/[^\d+\s()-]/g, '')" /></div>
                            <div class="md:col-span-4">
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-rose-500">Emergencia (Tel)</label>
                                <input v-model="form.contacto_emergencia_telefono" type="text" class="w-full rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-bold" @input="form.contacto_emergencia_telefono = form.contacto_emergencia_telefono.replace(/[^\d+\s()-]/g, '')" />
                            </div>
                        </div>
                    </details>

                    <!-- Botón Flotante -->
                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" :class="[
                            'flex w-full items-center justify-center gap-2 rounded-2xl px-8 py-3.5 text-sm font-extrabold text-white shadow-lg transition-all duration-300 hover:-translate-y-1 sm:w-auto',
                            editando ? 'bg-amber-500 shadow-amber-500/30 hover:bg-amber-600 hover:shadow-amber-500/50' : 'bg-blue-600 shadow-blue-500/30 hover:bg-blue-700 hover:shadow-blue-500/50'
                        ]">
                            <i :class="['ti text-xl', editando ? 'ti-device-floppy' : 'ti-user-plus']"></i>
                            {{ form.processing ? 'Procesando...' : (editando ? 'Guardar Cambios' : 'Registrar Empleado') }}
                        </button>
                    </div>
                </form>
            </section>

            <!-- Directorio (Tabla Bento) -->
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-3 border-b border-slate-100 bg-white p-4 xl:flex-row xl:items-center xl:justify-between dark:border-slate-800 dark:bg-slate-900">
                    <div class="shrink-0">
                        <h3 class="font-['Sora'] text-lg font-bold text-slate-900">{{ tituloDirectorio }}</h3>
                        <p class="text-xs font-medium text-slate-500">
                            {{ empleadosMeta.total ?? empleadosFiltrados.length }} colaborador(es) · {{ empleadosConDeuda }} con deuda
                        </p>
                    </div>
                    
                    <div class="w-full min-w-0 space-y-2 xl:max-w-4xl">
                        <!-- Filtros (Pills) -->
                        <div class="grid w-full grid-cols-3 gap-1 rounded-lg bg-slate-100/80 p-1">
                            <button @click="filtroEstado = 'activos'" :class="filtroEstado === 'activos' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="min-w-0 rounded-lg px-2 py-2 text-[11px] font-bold uppercase tracking-wider transition-all sm:px-4">
                                Activos ({{ empleadosActivos }})
                            </button>
                            <button @click="filtroEstado = 'papelera'" :class="filtroEstado === 'papelera' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="min-w-0 rounded-lg px-2 py-2 text-[11px] font-bold uppercase tracking-wider transition-all sm:px-4">
                                Bajas ({{ empleadosBaja }})
                            </button>
                            <button @click="filtroEstado = 'prestamo'" :class="filtroEstado === 'prestamo' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="min-w-0 rounded-lg px-2 py-2 text-[11px] font-bold uppercase tracking-wider transition-all sm:px-4">
                                Préstamos ({{ empleadosConDeuda }})
                            </button>
                        </div>

                        <div class="grid w-full min-w-0 grid-cols-1 gap-3 sm:grid-cols-[minmax(240px,1fr)_220px_auto]">
                        <!-- Buscador -->
                        <div class="relative min-w-0">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex w-12 items-center justify-center text-blue-600" aria-hidden="true">
                                <i class="ti ti-search text-xl"></i>
                            </span>
                            <input v-model="searchQuery" type="search" class="h-12 w-full rounded-lg border border-slate-200 bg-white pl-12 pr-4 text-sm font-semibold text-slate-800 shadow-sm transition-all placeholder:font-medium placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10" placeholder="Buscar por nombre o nÃºmero..." aria-label="Buscar empleado por nombre o nÃºmero" />
                        </div>
                        <select v-model="criterioOrdenDirectorio" class="field-input h-10 w-full min-w-0 py-2" aria-label="Ordenar directorio">
                            <option value="num_asc">Número ascendente</option>
                            <option value="num_desc">Número descendente</option>
                            <option value="name_asc">Nombre A - Z</option>
                            <option value="name_desc">Nombre Z - A</option>
                        </select>
                        <div class="segmented-control h-10 shrink-0 justify-self-start sm:justify-self-end" aria-label="Vista del directorio">
                            <button type="button" :class="{ active: vistaDirectorio === 'tabla' }" title="Vista de tabla" @click="cambiarVista('tabla')">
                                <i class="ti ti-list" aria-hidden="true"></i><span class="sr-only">Tabla</span>
                            </button>
                            <button type="button" :class="{ active: vistaDirectorio === 'cuadricula' }" title="Vista de cuadrícula" @click="cambiarVista('cuadricula')">
                                <i class="ti ti-layout-grid" aria-hidden="true"></i><span class="sr-only">Cuadrícula</span>
                            </button>
                        </div>
                        </div>
                    </div>
                </div>

                <div v-if="vistaDirectorio === 'tabla'" class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                            <tr>
                                <th class="px-6 py-4">Colaborador</th>
                                <th class="px-6 py-4">Puesto / Antigüedad</th>
                                <th class="px-6 py-4">Tarifa de Pago</th>
                                <th class="px-6 py-4">Préstamo</th>
                                <th class="px-6 py-4">Vacaciones</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="empleado in empleadosFiltrados" :key="empleado.id" class="transition-colors hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-blue-100 bg-blue-50 text-xs font-black text-blue-600 shadow-sm transition hover:border-blue-300 hover:ring-4 hover:ring-blue-100 disabled:cursor-default disabled:hover:border-blue-100 disabled:hover:ring-0"
                                            :disabled="!fotoDisponible(empleado)"
                                            :title="fotoDisponible(empleado) ? 'Ampliar fotografia' : 'Sin fotografia'"
                                            @click="abrirFotoEmpleado(empleado)"
                                        >
                                            <span>{{ numeroDirectorio(empleado) || 'S/N' }}</span>
                                            <img
                                                v-if="clavesFotoEmpleado(empleado).length"
                                                :key="`foto-${empleado.id}-${numeroDirectorio(empleado) || empleado.id}`"
                                                :src="fotoEmpleadoSrc(empleado)"
                                                :alt="`Foto de ${empleado.nombre_completo}`"
                                                loading="lazy"
                                                decoding="async"
                                                class="absolute inset-0 h-full w-full object-cover"
                                                @load="marcarFotoDisponible(empleado, $event)"
                                                @error="probarSiguienteFotoEmpleado(empleado, $event)"
                                            />
                                        </button>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-slate-900">{{ empleado.nombre_completo }}</span>
                                                <span v-if="!empleadoActivo(empleado)" class="rounded-md bg-rose-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-rose-600 border border-rose-200">Inactivo</span>
                                                <span v-else-if="!numeroDirectorio(empleado)" class="rounded-md bg-amber-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-600 border border-amber-200">Sin numero</span>
                                            </div>
                                            <span class="text-xs font-semibold text-slate-400">
                                                No. empleado: {{ numeroDirectorio(empleado) || 'S/N' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700">{{ empleado.puesto || 'No asignado' }}</div>
                                    <div v-if="empleadoActivo(empleado)" class="text-xs font-semibold text-emerald-600">{{ empleado.antiguedad_anios }} año(s) activos</div>
                                    <div v-if="empleadoActivo(empleado) && empleado.fecha_reingreso" class="mt-0.5 text-[10px] font-bold text-blue-600">
                                        Reingreso: {{ empleado.fecha_reingreso }}
                                    </div>
                                    <div v-else class="text-xs font-semibold text-rose-500">
                                        Baja: {{ empleado.fecha_baja || 'S/F' }}
                                        <span class="block text-[10px] font-bold text-rose-400">
                                            {{ empleado.dias_laborados || 0 }} d total
                                            <template v-if="empleado.fecha_baja">
                                                - {{ empleado.dias_laborados_anio_baja || 0 }} d en {{ String(empleado.fecha_baja).substring(0, 4) }}
                                            </template>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-lg bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 border border-emerald-100">
                                        <span v-if="esEstudiante(empleado)">Estudiante: ${{ empleado.sueldo_por_hora }}/hr</span>
                                        <span v-else>${{ sueldoSemanalEmpleado(empleado) }}/sem</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="tieneDeuda(empleado)" class="flex flex-col items-start gap-1">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-700 border border-amber-200">
                                            <i class="ti ti-cash-banknote"></i> Debe ${{ moneda(empleado.saldo_prestamo) }}
                                        </span>
                                        <span v-if="Number(empleado.cuota_prestamo || 0) > 0" class="text-[10px] font-bold text-slate-400 uppercase">Desc: ${{ moneda(empleado.cuota_prestamo) }}</span>
                                    </div>
                                    <span v-else class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500 border border-slate-200">
                                        <i class="ti ti-check"></i> Sin deuda
                                    </span>
                                </td>
                                <!-- Columna de Vacaciones Corregida y Hermosa -->
                                <td class="px-6 py-4">
                                    <div v-if="empleado.fecha_inicio_periodo_actual" class="w-36 flex flex-col gap-1.5 rounded-xl border border-slate-100 bg-slate-50/50 p-2.5">
                                        <div class="flex items-center justify-between text-[10px] uppercase tracking-wide font-bold text-slate-500">
                                            <span><i class="ti ti-palm"></i> Totales</span>
                                            <span class="text-slate-700">{{ empleado.dias_vacaciones_totales }} d</span>
                                        </div>
                                        <div class="flex items-center justify-between text-[10px] uppercase tracking-wide font-bold text-slate-500">
                                            <span><i class="ti ti-calendar-minus"></i> Tomados</span>
                                            <span class="text-rose-600">{{ empleado.dias_vacaciones_tomados }} d</span>
                                        </div>
                                        <div class="mt-0.5 border-t border-slate-200 pt-1.5 flex items-center justify-between text-[10px] uppercase tracking-wide font-black text-slate-700">
                                            <span>Restan</span>
                                            <span class="rounded-md bg-emerald-100 px-1.5 py-0.5 text-emerald-700">{{ empleado.dias_vacaciones_restantes }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs font-semibold italic text-slate-400">Sin ingreso</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('empleados.show', empleado.id)" class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500 hover:bg-blue-50 hover:text-blue-600 border border-slate-200 transition-all" title="Ver perfil">
                                            <i class="ti ti-eye"></i>
                                        </Link>
                                        <button v-if="canManage" type="button" @click="abrirEditorFoto(empleado)" class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500 hover:bg-cyan-50 hover:text-cyan-700 border border-slate-200 transition-all" title="Cambiar fotografía">
                                            <i class="ti ti-camera"></i>
                                        </button>
                                        <button v-if="canManage" @click="editarEmpleado(empleado)" class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500 hover:bg-amber-50 hover:text-amber-600 border border-slate-200 transition-all" title="Editar">
                                            <i class="ti ti-pencil"></i>
                                        </button>
                                        <button v-if="canManage && empleadoActivo(empleado)" @click="abrirBajaEmpleado(empleado)" class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500 hover:bg-rose-50 hover:text-rose-600 border border-slate-200 transition-all" title="Dar baja">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                        <button v-else-if="canManage" @click="abrirRestauracion(empleado)" class="flex h-8 items-center justify-center rounded-lg bg-slate-800 px-3 text-xs font-bold text-white hover:bg-slate-700 transition-all">
                                            Restaurar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="empleadosFiltrados.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i class="ti ti-users-x text-4xl mb-2"></i>
                                        <p class="font-bold">No se encontraron colaboradores</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                    <article v-for="empleado in empleadosFiltrados" :key="`grid-${empleado.id}`" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:shadow-md">
                        <div class="flex items-start gap-3">
                            <button
                                type="button"
                                class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-blue-100 bg-blue-50 text-sm font-black text-blue-700 disabled:cursor-default"
                                :disabled="!fotoDisponible(empleado)"
                                :title="fotoDisponible(empleado) ? 'Ampliar fotografía' : 'Sin fotografía'"
                                @click="abrirFotoEmpleado(empleado)"
                            >
                                {{ numeroDirectorio(empleado) || 'S/N' }}
                                <img
                                    v-if="clavesFotoEmpleado(empleado).length"
                                    :src="fotoEmpleadoSrc(empleado)"
                                    :alt="`Foto de ${empleado.nombre_completo}`"
                                    loading="lazy"
                                    decoding="async"
                                    class="absolute inset-0 h-full w-full object-cover"
                                    @load="marcarFotoDisponible(empleado, $event)"
                                    @error="probarSiguienteFotoEmpleado(empleado, $event)"
                                />
                            </button>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-black text-slate-900">{{ empleado.nombre_completo }}</p>
                                        <p class="mt-0.5 text-xs font-semibold text-slate-500">#{{ numeroDirectorio(empleado) || 'S/N' }} · {{ empleado.puesto || 'Sin puesto' }}</p>
                                    </div>
                                    <span :class="empleadoActivo(empleado) ? 'status-success' : 'status-danger'">
                                        {{ empleadoActivo(empleado) ? 'Activo' : 'Baja' }}
                                    </span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span v-if="!numeroDirectorio(empleado)" class="status-warning">Sin número</span>
                                    <span v-if="!fotoDisponible(empleado)" class="status-warning">Sin fotografía</span>
                                    <span v-if="tieneDeuda(empleado)" class="status-warning">Debe ${{ moneda(empleado.saldo_prestamo) }}</span>
                                    <span v-if="esEstudiante(empleado)" class="status-info">Estudiante</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                            <span class="text-xs font-bold text-slate-500">${{ sueldoSemanalEmpleado(empleado) }}/sem</span>
                            <div class="flex gap-1">
                                <Link :href="route('empleados.show', empleado.id)" class="icon-button" title="Ver perfil"><i class="ti ti-eye"></i></Link>
                                <button v-if="canManage" type="button" class="icon-button text-cyan-700" title="Cambiar fotografía" @click="abrirEditorFoto(empleado)"><i class="ti ti-camera"></i></button>
                                <button v-if="canManage" type="button" class="icon-button" title="Editar empleado" @click="editarEmpleado(empleado)"><i class="ti ti-pencil"></i></button>
                                <button v-if="canManage && empleadoActivo(empleado)" type="button" class="icon-button text-rose-600" title="Dar de baja" @click="abrirBajaEmpleado(empleado)"><i class="ti ti-user-off"></i></button>
                                <button v-else-if="canManage" type="button" class="icon-button text-emerald-700" title="Restaurar" @click="abrirRestauracion(empleado)"><i class="ti ti-user-check"></i></button>
                            </div>
                        </div>
                    </article>
                    <div v-if="empleadosFiltrados.length === 0" class="col-span-full py-12 text-center text-slate-500">
                        <i class="ti ti-users-off text-4xl" aria-hidden="true"></i>
                        <p class="mt-2 font-bold">No hay empleados con estos filtros.</p>
                    </div>
                </div>

                <AppPagination :meta="empleadosMeta" @change="cargarDirectorio" />
            </section>
        </div>

        <Teleport to="body">
            <div
                v-if="empleadoFotoAmpliada"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                :aria-label="`Fotografia de ${empleadoFotoAmpliada.nombre_completo}`"
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
                        :src="fotoEmpleadoSrc(empleadoFotoAmpliada)"
                        :alt="`Foto de ${empleadoFotoAmpliada.nombre_completo}`"
                        class="max-h-[82vh] w-full bg-slate-900 object-contain"
                        @load="mostrarFotoEmpleado"
                        @error="probarSiguienteFotoEmpleado(empleadoFotoAmpliada, $event)"
                    />
                    <div class="border-t border-white/10 bg-slate-950 px-5 py-4 text-white">
                        <p class="text-base font-black">{{ empleadoFotoAmpliada.nombre_completo }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-300">No. empleado {{ numeroDirectorio(empleadoFotoAmpliada) || 'S/N' }}</p>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="empleadoBaja"
                class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-label="Registrar baja"
                @click.self="cerrarBajaEmpleado"
            >
                <form class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-2xl" @submit.prevent="eliminarEmpleado">
                    <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                                <i class="ti ti-user-off text-xl"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-base font-black text-slate-950">Registrar baja</p>
                                <p class="mt-0.5 break-words text-sm font-semibold text-slate-500">{{ empleadoBaja.nombre_completo }}</p>
                            </div>
                        </div>
                        <button type="button" class="icon-button" title="Cerrar" aria-label="Cerrar" @click="cerrarBajaEmpleado"><i class="ti ti-x"></i></button>
                    </div>

                    <div class="space-y-4 p-5">
                        <div>
                            <label class="field-label" for="fecha-baja-empleado">Fecha efectiva de baja</label>
                            <input id="fecha-baja-empleado" v-model="bajaEmpleadoForm.fecha_baja" type="date" :min="empleadoBaja.fecha_inicio_periodo_actual || empleadoBaja.fecha_ingreso || undefined" :max="fechaLocalHoy()" class="field-input" required />
                            <p v-if="bajaEmpleadoForm.errors.fecha_baja" class="mt-2 text-sm font-bold text-rose-600">{{ bajaEmpleadoForm.errors.fecha_baja }}</p>
                        </div>
                        <div>
                            <label class="field-label" for="motivo-baja-empleado">Motivo <span class="font-medium normal-case text-slate-400">(opcional)</span></label>
                            <textarea id="motivo-baja-empleado" v-model="bajaEmpleadoForm.motivo_baja" rows="3" maxlength="500" class="field-input resize-none" placeholder="Motivo de la baja"></textarea>
                            <p v-if="bajaEmpleadoForm.errors.motivo_baja" class="mt-2 text-sm font-bold text-rose-600">{{ bajaEmpleadoForm.errors.motivo_baja }}</p>
                        </div>
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-900">
                            Los días laborados se calcularán desde el inicio del periodo actual hasta esta fecha, sin contar domingos.
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                        <button type="button" class="btn-secondary" @click="cerrarBajaEmpleado">Cancelar</button>
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-60" :disabled="bajaEmpleadoForm.processing">
                            <i class="ti ti-user-off"></i>{{ bajaEmpleadoForm.processing ? 'Procesando...' : 'Confirmar baja' }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="empleadoFotoEdicion"
                class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-label="Cambiar fotografía"
                @click.self="cerrarEditorFoto"
            >
                <form class="w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-2xl" @submit.prevent="guardarFoto">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <p class="text-base font-black text-slate-950">Cambiar fotografía</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-500">{{ empleadoFotoEdicion.nombre_completo }}</p>
                        </div>
                        <button type="button" class="icon-button" title="Cerrar" aria-label="Cerrar" @click="cerrarEditorFoto">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>

                    <div class="p-5">
                        <div class="relative mx-auto flex aspect-square w-full max-w-72 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100 text-2xl font-black text-slate-400">
                            <span>{{ numeroDirectorio(empleadoFotoEdicion) || 'S/N' }}</span>
                            <img
                                :src="fotoPreview || fotoEmpleadoSrc(empleadoFotoEdicion)"
                                :alt="`Vista previa de ${empleadoFotoEdicion.nombre_completo}`"
                                class="absolute inset-0 h-full w-full object-contain"
                                @load="mostrarFotoEmpleado"
                                @error="probarSiguienteFotoEmpleado(empleadoFotoEdicion, $event)"
                            />
                        </div>

                        <label class="btn-secondary mt-5 w-full cursor-pointer justify-center" for="foto-empleado-nueva">
                            <i class="ti ti-photo-up"></i>
                            Seleccionar imagen
                        </label>
                        <input id="foto-empleado-nueva" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="seleccionarFoto" />
                        <p v-if="fotoForm.foto" class="mt-2 truncate text-center text-xs font-bold text-slate-600">{{ fotoForm.foto.name }}</p>
                        <p v-if="fotoForm.errors.foto" class="mt-2 text-center text-sm font-bold text-rose-600">{{ fotoForm.errors.foto }}</p>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                        <button type="button" class="btn-secondary" @click="cerrarEditorFoto">Cancelar</button>
                        <button type="submit" class="btn-primary" :disabled="!fotoForm.foto || fotoForm.processing">
                            <i class="ti ti-device-floppy"></i>
                            {{ fotoForm.processing ? 'Guardando...' : 'Guardar fotografía' }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="empleadoRestauracion"
                class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-label="Registrar reingreso"
                @click.self="cerrarRestauracion"
            >
                <form class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-2xl" @submit.prevent="restaurarEmpleado">
                    <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                <i class="ti ti-user-check text-xl"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-base font-black text-slate-950">Registrar reingreso</p>
                                <p class="mt-0.5 break-words text-sm font-semibold text-slate-500">{{ empleadoRestauracion.nombre_completo }}</p>
                            </div>
                        </div>
                        <button type="button" class="icon-button" title="Cerrar" aria-label="Cerrar" @click="cerrarRestauracion"><i class="ti ti-x"></i></button>
                    </div>

                    <div class="space-y-4 p-5">
                        <div>
                            <label class="field-label" for="fecha-reingreso">Fecha efectiva de reingreso</label>
                            <input id="fecha-reingreso" v-model="restaurarForm.fecha_reingreso" type="date" :max="fechaLocalHoy()" class="field-input" required />
                            <p v-if="restaurarForm.errors.fecha_reingreso" class="mt-2 text-sm font-bold text-rose-600">{{ restaurarForm.errors.fecha_reingreso }}</p>
                        </div>

                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-900">
                            La antigüedad y las vacaciones del nuevo periodo comenzarán en esta fecha. La fecha de ingreso original y los periodos anteriores se conservarán en el historial.
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                        <button type="button" class="btn-secondary" @click="cerrarRestauracion">Cancelar</button>
                        <button type="submit" class="btn-primary" :disabled="restaurarForm.processing">
                            <i class="ti ti-user-check"></i>
                            {{ restaurarForm.processing ? 'Restaurando...' : 'Confirmar reingreso' }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
