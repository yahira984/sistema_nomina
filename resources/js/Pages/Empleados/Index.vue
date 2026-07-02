<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { clavesFotoEmpleado, fotoEmpleadoSrc, mostrarFotoEmpleado, probarSiguienteFotoEmpleado } from '@/Utils/employeePhotos';

const props = defineProps({ empleados: Array });
const page = usePage();
const canManage = computed(() => page.props.auth?.can?.['empleados.manage'] ?? false);

const editando = ref(false);
const empleadoId = ref(null);
const searchQuery = ref('');
const filtroEstado = ref('activos');
const criterioOrdenDirectorio = ref('num_asc');

const normalizarNumeroEmpleado = (numero) => {
    const texto = String(numero || '').trim();
    return texto.replace(/^0+/, '') || texto || '';
};

const valorNumeroEmpleado = (empleado) => {
    const valor = parseInt(normalizarNumeroEmpleado(empleado.numero_empleado || empleado.numero_empleado_baja), 10);
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
        if (filtroEstado.value === 'activos') return Boolean(Number(emp.estatus ?? 0));
        if (filtroEstado.value === 'prestamo') return Boolean(Number(emp.estatus ?? 0)) && Number(emp.saldo_prestamo ?? 0) > 0;
        if (filtroEstado.value === 'papelera') return !Boolean(Number(emp.estatus ?? 0));
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

const empleadosActivos = computed(() => props.empleados.filter(emp => Boolean(Number(emp.estatus ?? 0))).length);
const empleadosBaja = computed(() => props.empleados.length - empleadosActivos.value);
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
const empleadosConDeuda = computed(() => props.empleados.filter(emp => Boolean(Number(emp.estatus ?? 0)) && tieneDeuda(emp)).length);

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
const eliminarEmpleado = (id, nombre) => { if (confirm(`¿Dar de baja a ${nombre}?`)) router.delete(route('empleados.destroy', id)); };
const restaurarEmpleado = (id, nombre) => { if (confirm(`¿Restaurar a ${nombre}?`)) router.put(route('empleados.restaurar', id)); };
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
                    <h2 class="font-['Sora'] text-2xl font-extrabold text-slate-900">Directorio de Empleados</h2>
                </div>
            </div>
        </template>

        <div class="space-y-8">
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
                            editando ? 'bg-gradient-to-r from-amber-500 to-amber-400 shadow-amber-500/30 hover:shadow-amber-500/50' : 'bg-gradient-to-r from-blue-600 to-blue-500 shadow-blue-500/30 hover:shadow-blue-500/50'
                        ]">
                            <i :class="['ti text-xl', editando ? 'ti-device-floppy' : 'ti-user-plus']"></i>
                            {{ form.processing ? 'Procesando...' : (editando ? 'Guardar Cambios' : 'Registrar Empleado') }}
                        </button>
                    </div>
                </form>
            </section>

            <!-- Directorio (Tabla Bento) -->
            <section class="rounded-3xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/50 p-6 flex flex-col lg:flex-row justify-between gap-4">
                    <div>
                        <h3 class="font-['Sora'] text-lg font-bold text-slate-900">{{ tituloDirectorio }}</h3>
                        <p class="text-xs font-medium text-slate-500">{{ empleadosFiltrados.length }} colaborador(es) • {{ empleadosConDeuda }} con deuda</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <!-- Filtros (Pills) -->
                        <div class="flex rounded-xl bg-slate-100/80 p-1">
                            <button @click="filtroEstado = 'activos'" :class="filtroEstado === 'activos' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 sm:flex-none rounded-lg px-4 py-1.5 text-[11px] font-bold uppercase tracking-wider transition-all">
                                Activos ({{ empleadosActivos }})
                            </button>
                            <button @click="filtroEstado = 'papelera'" :class="filtroEstado === 'papelera' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 sm:flex-none rounded-lg px-4 py-1.5 text-[11px] font-bold uppercase tracking-wider transition-all">
                                Bajas ({{ empleadosBaja }})
                            </button>
                            <button @click="filtroEstado = 'prestamo'" :class="filtroEstado === 'prestamo' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 sm:flex-none rounded-lg px-4 py-1.5 text-[11px] font-bold uppercase tracking-wider transition-all">
                                Préstamos ({{ empleadosConDeuda }})
                            </button>
                        </div>
                        
                        <!-- Buscador -->
                        <div class="relative w-full sm:w-64">
                            <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input v-model="searchQuery" type="text" class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-4 text-sm font-semibold text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="Buscar empleado..." />
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
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
                                        <div class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-blue-100 bg-blue-50 text-xs font-black text-blue-600 shadow-sm">
                                            <span>{{ empleado.numero_empleado || empleado.numero_empleado_baja || 'S/N' }}</span>
                                            <img
                                                v-if="clavesFotoEmpleado(empleado).length"
                                                :key="`foto-${empleado.id}-${empleado.numero_empleado || empleado.numero_empleado_baja || empleado.id}`"
                                                :src="fotoEmpleadoSrc(empleado)"
                                                :alt="`Foto de ${empleado.nombre_completo}`"
                                                loading="lazy"
                                                decoding="async"
                                                class="absolute inset-0 h-full w-full object-cover"
                                                @load="mostrarFotoEmpleado"
                                                @error="probarSiguienteFotoEmpleado(empleado, $event)"
                                            />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-slate-900">{{ empleado.nombre_completo }}</span>
                                                <span v-if="!Boolean(Number(empleado.estatus ?? 0))" class="rounded-md bg-rose-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-rose-600 border border-rose-200">Inactivo</span>
                                            </div>
                                            <span class="text-xs font-semibold text-slate-400">
                                                No. empleado: {{ empleado.numero_empleado || empleado.numero_empleado_baja || 'S/N' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700">{{ empleado.puesto || 'No asignado' }}</div>
                                    <div v-if="Boolean(Number(empleado.estatus ?? 0))" class="text-xs font-semibold text-emerald-600">{{ empleado.antiguedad_anios }} año(s) activos</div>
                                    <div v-else class="text-xs font-semibold text-rose-500">Baja: {{ empleado.fecha_baja || 'S/F' }}</div>
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
                                    <div v-if="empleado.fecha_ingreso" class="w-36 flex flex-col gap-1.5 rounded-xl border border-slate-100 bg-slate-50/50 p-2.5">
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
                                        <button v-if="canManage" @click="editarEmpleado(empleado)" class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500 hover:bg-amber-50 hover:text-amber-600 border border-slate-200 transition-all" title="Editar">
                                            <i class="ti ti-pencil"></i>
                                        </button>
                                        <button v-if="canManage && Boolean(Number(empleado.estatus ?? 0))" @click="eliminarEmpleado(empleado.id, empleado.nombre_completo)" class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500 hover:bg-rose-50 hover:text-rose-600 border border-slate-200 transition-all" title="Dar baja">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                        <button v-else-if="canManage" @click="restaurarEmpleado(empleado.id, empleado.nombre_completo)" class="flex h-8 items-center justify-center rounded-lg bg-slate-800 px-3 text-xs font-bold text-white hover:bg-slate-700 transition-all">
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
            </section>
        </div>
    </AuthenticatedLayout>
</template>
