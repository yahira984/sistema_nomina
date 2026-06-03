<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    empleadosActivos: Number,
    semanaActual: Number,
    totalPagado: Number
});
</script>

<template>
    <Head title="Panel Principal" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-gray-800 leading-tight">Bienvenido al Sistema PROMATEC-LUGARTH</h2>
        </template>

        <div class="py-10 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Tarjetas de Resumen (Gradients Premium) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Tarjeta 1: Empleados -->
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg shadow-blue-200 overflow-hidden text-white">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-blue-100 text-sm font-bold uppercase tracking-wider">Personal Activo</p>
                                    <h3 class="text-4xl font-extrabold mt-2">{{ empleadosActivos }}</h3>
                                    <p class="text-blue-100 text-sm mt-1">Colaboradores registrados</p>
                                </div>
                                <div class="p-3 bg-white/20 rounded-xl">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Semana -->
                    <div class="bg-gradient-to-br from-orange-400 to-pink-500 rounded-2xl shadow-lg shadow-orange-200 overflow-hidden text-white">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-orange-100 text-sm font-bold uppercase tracking-wider">Semana Contable</p>
                                    <h3 class="text-4xl font-extrabold mt-2">No. {{ semanaActual }}</h3>
                                    <p class="text-orange-100 text-sm mt-1">Corte: Miércoles a Martes</p>
                                </div>
                                <div class="p-3 bg-white/20 rounded-xl">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 3: Dinero -->
                    <div class="bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl shadow-lg shadow-emerald-200 overflow-hidden text-white">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-emerald-100 text-sm font-bold uppercase tracking-wider">Gasto Nómina Sem. {{ semanaActual }}</p>
                                    <h3 class="text-4xl font-extrabold mt-2">${{ totalPagado.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</h3>
                                    <p class="text-emerald-100 text-sm mt-1">Total neto dispersado</p>
                                </div>
                                <div class="p-3 bg-white/20 rounded-xl">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Accesos Rápidos (Botones Interactivos) -->
                <div>
                    <h3 class="text-xl font-bold text-gray-800 mb-6">Módulos Principales</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <!-- Botón Empleados -->
                        <Link :href="route('empleados.index')" class="group block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:shadow-blue-100 hover:-translate-y-1 transition-all duration-300">
                            <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900 mb-2">Directorio de Personal</h4>
                            <p class="text-sm text-gray-500">Agrega, edita o elimina a los trabajadores de la empresa y sus sueldos.</p>
                            <div class="mt-4 flex items-center text-sm font-bold text-blue-600">
                                Ir al módulo <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </div>
                        </Link>

                        <!-- Botón Asistencias -->
                        <Link :href="route('asistencias.index')" class="group block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:shadow-emerald-100 hover:-translate-y-1 transition-all duration-300">
                            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900 mb-2">Control de Asistencias</h4>
                            <p class="text-sm text-gray-500">Registra horas de entrada y salida para calcular la jornada semanal.</p>
                            <div class="mt-4 flex items-center text-sm font-bold text-emerald-600">
                                Ir al módulo <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </div>
                        </Link>

                        <!-- Botón Nóminas -->
                        <Link :href="route('nominas.index')" class="group block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:shadow-orange-100 hover:-translate-y-1 transition-all duration-300">
                            <div class="w-14 h-14 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900 mb-2">Generar Nóminas</h4>
                            <p class="text-sm text-gray-500">Calcula horas regulares, horas extra y genera los recibos en PDF listos para imprimir.</p>
                            <div class="mt-4 flex items-center text-sm font-bold text-orange-600">
                                Ir al módulo <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </div>
                        </Link>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>