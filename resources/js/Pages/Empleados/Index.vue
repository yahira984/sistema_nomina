<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    empleados: Array
});

const editando = ref(false);
const empleadoId = ref(null);
const searchQuery = ref('');

const form = useForm({
    numero_empleado: '',
    nombre_completo: '',
    puesto: '',
    fecha_ingreso: '', 
    forma_pago: 'Efectivo',
    es_estudiante: false, // NUEVO INTERRUPTOR
    sueldo_semanal: '', 
    sueldo_por_hora: '', 
    saldo_prestamo: '', 
    cuota_prestamo: '', 
    descuento_imss: '', 
    descuento_isr: '', 
    descuento_infonavit: '', 
    banco: '',
    numero_cuenta: '',
    nss: '',
    rfc: ''
});

const empleadosFiltrados = computed(() => {
    if (!searchQuery.value) return props.empleados;
    return props.empleados.filter(emp => {
        const query = searchQuery.value.toLowerCase();
        const nombreMatch = emp.nombre_completo.toLowerCase().includes(query);
        const numeroMatch = emp.numero_empleado && emp.numero_empleado.toLowerCase().includes(query);
        return nombreMatch || numeroMatch;
    });
});

const submitForm = () => {
    if (editando.value) {
        form.put(route('empleados.update', empleadoId.value), {
            onSuccess: () => cancelarEdicion()
        });
    } else {
        form.post(route('empleados.store'), {
            onSuccess: () => form.reset()
        });
    }
};

const editarEmpleado = (empleado) => {
    editando.value = true;
    empleadoId.value = empleado.id;
    form.numero_empleado = empleado.numero_empleado || '';
    form.nombre_completo = empleado.nombre_completo;
    form.puesto = empleado.puesto || '';
    form.fecha_ingreso = empleado.fecha_ingreso || '';
    form.forma_pago = empleado.forma_pago || 'Efectivo';
    form.es_estudiante = empleado.sueldo_por_hora > 0; // Si gana por hora, es estudiante
    form.sueldo_semanal = empleado.sueldo_semanal || '';
    form.sueldo_por_hora = empleado.sueldo_por_hora || '';
    form.saldo_prestamo = empleado.saldo_prestamo || ''; 
    form.cuota_prestamo = empleado.cuota_prestamo || ''; 
    form.descuento_imss = empleado.descuento_imss || ''; 
    form.descuento_isr = empleado.descuento_isr || ''; 
    form.descuento_infonavit = empleado.descuento_infonavit || ''; 
    form.banco = empleado.banco || '';
    form.numero_cuenta = empleado.numero_cuenta || '';
    form.nss = empleado.nss || '';
    form.rfc = empleado.rfc || '';

    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelarEdicion = () => {
    editando.value = false;
    empleadoId.value = null;
    form.reset();
};

const eliminarEmpleado = (id, nombre) => {
    if (confirm(`¿Estás seguro de eliminar a ${nombre}?`)) {
        router.delete(route('empleados.destroy', id));
    }
};
</script>

<template>
    <Head title="Control de Empleados" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('dashboard')" class="icon-button" aria-label="Volver al panel">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19 3 12m0 0 7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <p class="text-sm font-semibold text-teal-700">Gestión de personal</p>
                    <h2 class="text-2xl font-semibold text-slate-950">Directorio de Personal</h2>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-8">
                <section class="app-panel" :class="editando ? 'ring-2 ring-amber-400/70' : ''">
                    <div class="panel-header">
                        <div class="flex items-start gap-3">
                            <div :class="editando ? 'soft-icon-amber' : 'soft-icon-blue'">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM3 20a6 6 0 0 1 12 0v1H3v-1Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="panel-title">{{ editando ? 'Actualizar expediente' : 'Alta de trabajador' }}</h3>
                                <p class="panel-subtitle">Captura los datos base para asistencia, pago y recibos.</p>
                            </div>
                        </div>

                        <button v-if="editando" @click="cancelarEdicion" class="btn-secondary" type="button">
                            Cancelar edición
                        </button>
                    </div>

                    <div class="p-5 sm:p-6">
                        <form @submit.prevent="submitForm" class="grid grid-cols-1 gap-5 md:grid-cols-4">
                            <div>
                                <label class="field-label">No. empleado</label>
                                <input v-model="form.numero_empleado" type="text" class="field-input-soft" placeholder="Ej. 84" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="field-label">Nombre completo <span class="text-rose-500">*</span></label>
                                <input v-model="form.nombre_completo" type="text" required class="field-input-soft" />
                            </div>

                            <div>
                                <label class="field-label">Puesto</label>
                                <input v-model="form.puesto" type="text" class="field-input-soft" />
                            </div>

                            <div>
                                <label class="field-label">Fecha de Ingreso</label>
                                <input v-model="form.fecha_ingreso" type="date" class="field-input-soft" />
                            </div>

                            <div>
                                <label class="field-label">Forma de pago <span class="text-rose-500">*</span></label>
                                <select v-model="form.forma_pago" required class="field-input-soft">
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Deposito">Depósito / Transferencia</option>
                                </select>
                            </div>

                            <div class="md:col-span-2 flex items-center pl-2 pt-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.es_estudiante" class="w-5 h-5 rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                                    <span class="text-sm font-semibold text-slate-700">Modalidad Estudiante (Pago por hora)</span>
                                </label>
                            </div>

                            <template v-if="form.es_estudiante">
                                <div class="md:col-span-2">
                                    <label class="field-label text-teal-700">Tarifa por Hora Estudiante ($) <span class="text-rose-500">*</span></label>
                                    <input v-model="form.sueldo_por_hora" type="number" step="0.01" class="field-input-soft border-teal-200 focus:border-teal-400 focus:ring-teal-400/20" placeholder="Ej. 27.00" />
                                </div>
                                <div class="md:col-span-2"></div>
                            </template>
                            <template v-else>
                                <div class="md:col-span-2">
                                    <label class="field-label text-teal-700">Sueldo Semanal Base ($) <span class="text-rose-500">*</span></label>
                                    <input v-model="form.sueldo_semanal" type="number" step="0.01" class="field-input-soft border-teal-200 focus:border-teal-400 focus:ring-teal-400/20" placeholder="Ej. 1000.00" />
                                </div>
                                <div class="md:col-span-2"></div>
                            </template>

                            <div>
                                <label class="field-label text-amber-700">Deuda Total Préstamo ($)</label>
                                <input v-model="form.saldo_prestamo" type="number" step="0.01" class="field-input-soft border-amber-200" />
                            </div>

                            <div>
                                <label class="field-label">Desc. Préstamo x Sem ($)</label>
                                <input v-model="form.cuota_prestamo" type="number" step="0.01" class="field-input-soft" />
                            </div>

                            <div>
                                <label class="field-label">Desc. IMSS ($)</label>
                                <input v-model="form.descuento_imss" type="number" step="0.01" class="field-input-soft" />
                            </div>
                            
                            <div>
                                <label class="field-label">Desc. ISR ($)</label>
                                <input v-model="form.descuento_isr" type="number" step="0.01" class="field-input-soft" />
                            </div>

                            <div class="md:col-span-4">
                                <label class="field-label">Desc. INFONAVIT ($)</label>
                                <input v-model="form.descuento_infonavit" type="number" step="0.01" class="field-input-soft" />
                            </div>

                            <template v-if="form.forma_pago === 'Deposito'">
                                <div class="col-span-1 md:col-span-2">
                                    <label class="field-label">Banco <span class="text-rose-500">*</span></label>
                                    <input v-model="form.banco" type="text" :required="form.forma_pago === 'Deposito'" class="field-input-soft" placeholder="BBVA, Banamex..." />
                                </div>

                                <div class="col-span-1 md:col-span-2">
                                    <label class="field-label">Cuenta bancaria o CLABE <span class="text-rose-500">*</span></label>
                                    <input v-model="form.numero_cuenta" type="text" :required="form.forma_pago === 'Deposito'" class="field-input-soft" placeholder="18 dígitos o tarjeta" />
                                </div>
                            </template>

                            <div class="md:col-span-2">
                                <label class="field-label">NSS</label>
                                <input v-model="form.nss" type="text" class="field-input-soft" placeholder="11 dígitos" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="field-label">RFC</label>
                                <input v-model="form.rfc" type="text" class="field-input-soft" placeholder="12 o 13 caracteres" />
                            </div>

                            <div class="flex justify-end md:col-span-4 mt-2">
                                <button type="submit" :disabled="form.processing" :class="editando ? 'btn-warning' : 'btn-accent'">
                                    {{ form.processing ? 'Guardando...' : (editando ? 'Actualizar expediente' : 'Registrar empleado') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="app-panel">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Directorio activo</h3>
                            <p class="panel-subtitle">{{ empleadosFiltrados.length }} trabajador(es) encontrados</p>
                        </div>
                        <div class="relative w-full lg:w-96">
                            <input v-model="searchQuery" type="text" class="field-input-soft pl-4" placeholder="Buscar por nombre o número..." />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Puesto / Antigüedad</th>
                                    <th>Tarifa de Pago</th>
                                    <th>Control Vacaciones</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="empleado in empleadosFiltrados" :key="empleado.id">
                                    <td class="whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 min-w-10 max-w-16 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-2 text-xs font-bold text-blue-700">
                                                {{ empleado.numero_empleado || 'S/N' }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="truncate font-semibold text-slate-950">{{ empleado.nombre_completo }}</div>
                                                <div class="text-xs text-slate-500">ID sistema: #{{ empleado.id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="font-medium text-slate-900">{{ empleado.puesto || 'No asignado' }}</div>
                                        <div class="mt-1 text-xs font-semibold text-teal-600">
                                            {{ empleado.antiguedad_anios }} año(s) en la empresa
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span class="status-pill status-success w-max">
                                            <span v-if="empleado.sueldo_por_hora > 0">Estudiante: ${{ empleado.sueldo_por_hora }} / hr</span>
                                            <span v-else>${{ empleado.sueldo_semanal }} / sem</span>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div v-if="empleado.fecha_ingreso" class="flex flex-col gap-1 text-xs">
                                            <span class="font-bold text-slate-700">🌴 Totales: {{ empleado.dias_vacaciones_totales }} días</span>
                                            <span class="text-rose-600">Tomados: {{ empleado.dias_vacaciones_tomados }}</span>
                                            <span class="text-emerald-600 font-bold">Restan: {{ empleado.dias_vacaciones_restantes }}</span>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 italic">
                                            Falta fecha de ingreso
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <button @click="editarEmpleado(empleado)" class="btn-secondary text-xs mr-2">Editar</button>
                                        <button @click="eliminarEmpleado(empleado.id, empleado.nombre_completo)" class="btn-danger text-xs">Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>