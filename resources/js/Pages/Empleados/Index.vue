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
    sueldo_por_hora: '',
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
    form.sueldo_por_hora = empleado.sueldo_por_hora;
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
                                <label class="field-label">Sueldo / hora ($) <span class="text-rose-500">*</span></label>
                                <input v-model="form.sueldo_por_hora" type="number" step="0.01" required class="field-input-soft" />
                            </div>

                            <div>
                                <label class="field-label">Banco</label>
                                <input v-model="form.banco" type="text" class="field-input-soft" placeholder="BBVA, Banamex..." />
                            </div>

                            <div class="md:col-span-2">
                                <label class="field-label">Cuenta bancaria o CLABE</label>
                                <input v-model="form.numero_cuenta" type="text" class="field-input-soft" placeholder="18 dígitos o tarjeta" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="field-label">NSS</label>
                                <input v-model="form.nss" type="text" class="field-input-soft" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="field-label">RFC</label>
                                <input v-model="form.rfc" type="text" class="field-input-soft" />
                            </div>

                            <div class="flex justify-end md:col-span-4">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    :class="editando ? 'btn-warning' : 'btn-accent'"
                                >
                                    <svg v-if="!form.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3m-1 4-3 3m0 0-3-3m3 3V4" />
                                    </svg>
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
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                            <input v-model="searchQuery" type="text" class="field-input-soft pl-10" placeholder="Buscar por nombre o número..." />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Puesto / cuenta bancaria</th>
                                    <th>Tarifa</th>
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
                                        <div class="mt-1 flex items-center gap-1 text-xs text-slate-500">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3Z" />
                                            </svg>
                                            {{ empleado.banco ? empleado.banco + ' - ' : '' }}{{ empleado.numero_cuenta || 'Sin cuenta registrada' }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <span class="status-pill status-success">
                                            ${{ empleado.sueldo_por_hora }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="editarEmpleado(empleado)" class="icon-button" title="Editar" type="button">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.232 5.232 3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732Z" />
                                                </svg>
                                            </button>
                                            <button @click="eliminarEmpleado(empleado.id, empleado.nombre_completo)" class="icon-button-danger" title="Eliminar" type="button">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 7-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="empleadosFiltrados.length === 0">
                                    <td colspan="4" class="empty-state">
                                        No se encontraron empleados con esa búsqueda.
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
