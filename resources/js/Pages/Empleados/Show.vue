<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    empleado: Object
});

const tabActiva = ref('perfil');

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
    return sueldoPorHora > 0 ? (sueldoPorHora * 48).toFixed(2) : '0.00';
});
</script>

<template>
    <Head :title="`Expediente | ${empleado.nombre_completo}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('empleados.index')" class="icon-button" aria-label="Volver">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19 3 12m0 0 7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <p class="text-sm font-semibold text-teal-700">Expediente Digital</p>
                    <h2 class="text-2xl font-semibold text-slate-950">Perfil del Empleado</h2>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-6">
                
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col md:flex-row items-start md:items-center gap-6 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-16 bg-gradient-to-r from-teal-500 to-emerald-600 opacity-20"></div>
                    
                    <div class="h-24 w-24 rounded-full bg-gradient-to-br from-teal-600 to-emerald-800 text-white flex items-center justify-center text-3xl font-black shadow-lg z-10 border-4 border-white">
                        {{ iniciales }}
                    </div>
                    
                    <div class="flex-1 z-10">
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-2xl font-bold text-slate-900">{{ empleado.nombre_completo }}</h1>
                            <span v-if="empleado.estatus" class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200">Activo</span>
                            <span v-else class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 text-xs font-bold border border-rose-200">Baja</span>
                        </div>
                        <p class="text-slate-600 font-medium mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            {{ empleado.puesto || 'Puesto no asignado' }} • ID: #{{ empleado.numero_empleado || empleado.id }}
                        </p>
                        
                        <div class="mt-4 flex flex-wrap gap-2 border-b border-slate-200 pb-2">
                            <button @click="tabActiva = 'perfil'" :class="tabActiva === 'perfil' ? 'bg-teal-50 text-teal-700 border-teal-200 font-bold' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-all">
                                <i class="ti ti-id" aria-hidden="true"></i>
                                Datos Personales
                            </button>
                            <button @click="tabActiva = 'nomina'" :class="tabActiva === 'nomina' ? 'bg-blue-50 text-blue-700 border-blue-200 font-bold' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-all">
                                <i class="ti ti-report-money" aria-hidden="true"></i>
                                Nómina y Puesto
                            </button>
                            <button @click="tabActiva = 'asistencia'" :class="tabActiva === 'asistencia' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-all">
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
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">CURP</p><p class="font-medium">{{ empleado.curp || 'No registrado' }}</p></div>
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Fecha de Nacimiento</p><p class="font-medium">{{ empleado.fecha_nacimiento || 'No registrada' }}</p></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><p class="text-xs text-slate-500 uppercase font-semibold">Género</p><p class="font-medium">{{ empleado.genero || '--' }}</p></div>
                                <div><p class="text-xs text-slate-500 uppercase font-semibold">Estado Civil</p><p class="font-medium">{{ empleado.estado_civil || '--' }}</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4 flex items-center gap-2">
                            <i class="ti ti-phone-call text-xl text-teal-600" aria-hidden="true"></i>
                            Contacto y Emergencias
                        </h3>
                        <div class="space-y-4">
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Teléfono</p><p class="font-medium">{{ empleado.telefono || 'No registrado' }}</p></div>
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Correo Electrónico</p><p class="font-medium">{{ empleado.correo || 'No registrado' }}</p></div>
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Dirección</p><p class="font-medium">{{ empleado.direccion || 'No registrada' }}</p></div>
                            
                            <div class="mt-4 pt-4 border-t border-rose-100 bg-rose-50/50 -mx-5 px-5 pb-2">
                                <p class="text-xs text-rose-600 uppercase font-bold mb-2">En caso de emergencia</p>
                                <div><p class="text-xs text-slate-500">Contactar a:</p><p class="font-bold text-slate-800">{{ empleado.contacto_emergencia_nombre || 'No registrado' }}</p></div>
                                <div class="mt-1"><p class="text-xs text-slate-500">Teléfono:</p><p class="font-bold text-slate-800">{{ empleado.contacto_emergencia_telefono || '--' }}</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-show="tabActiva === 'nomina'" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in">
                    <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">Esquema de Pago</h3>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Salario Base</p>
                                <p class="text-2xl font-black text-emerald-600">
                                    <span v-if="esEstudiante">${{ empleado.sueldo_por_hora }} <span class="text-sm font-medium text-slate-500">/ hora</span></span>
                                    <span v-else>${{ sueldoSemanalMostrado }} <span class="text-sm font-medium text-slate-500">/ semana</span></span>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-semibold">Método de Pago</p>
                                <p class="text-lg font-bold text-slate-800">{{ empleado.forma_pago }}</p>
                                <p v-if="empleado.forma_pago === 'Deposito'" class="text-sm text-slate-500">{{ empleado.banco }} - Cta: {{ empleado.numero_cuenta }}</p>
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
                        <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">Deducciones Activas</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center"><span class="text-sm text-slate-600">IMSS</span><span class="font-bold text-rose-600">-${{ empleado.descuento_imss || '0.00' }}</span></div>
                            <div class="flex justify-between items-center"><span class="text-sm text-slate-600">ISR</span><span class="font-bold text-rose-600">-${{ empleado.descuento_isr || '0.00' }}</span></div>
                            <div class="flex justify-between items-center"><span class="text-sm text-slate-600">INFONAVIT</span><span class="font-bold text-rose-600">-${{ empleado.descuento_infonavit || '0.00' }}</span></div>
                            <div class="border-t border-slate-100 pt-3 mt-3">
                                <p class="text-xs text-slate-500 uppercase font-semibold mb-1">Préstamo Empresarial</p>
                                <div class="flex justify-between items-center"><span class="text-sm text-amber-600">Deuda Total</span><span class="font-bold text-amber-700">${{ empleado.saldo_prestamo || '0.00' }}</span></div>
                                <div class="flex justify-between items-center"><span class="text-sm text-slate-600">Abono Semanal</span><span class="font-bold">-${{ empleado.cuota_prestamo || '0.00' }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-show="tabActiva === 'asistencia'" class="space-y-6 animate-fade-in">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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
                            <p v-if="empleado.ajuste_vacaciones !== 0" class="text-xs text-emerald-700 mt-1">Incluye ajuste: {{ empleado.ajuste_vacaciones }}</p>
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
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
