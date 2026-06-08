<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
  totalEmpleados:    { type: Number, default: 0 },
  semanaContable:    { type: Number, default: 0 },
  gastoSemanal:      { type: [Number, String], default: '0.00' },
  corteSemana:       { type: String, default: 'Miércoles a martes' },
  nominasPendientes: { type: Number, default: 0 },
  kpis:              Object,
  graficaAsistencia: Array,
  graficaExtras:     Object
})

const kpisDashboard = computed(() => props.kpis ?? { faltas: 0, cumpleaneros: [] })
const cumpleanerosMes = computed(() => kpisDashboard.value.cumpleaneros ?? [])
const graficaExtrasDatos = computed(() => props.graficaExtras ?? { categorias: [], datos: [] })
const barSeries = computed(() => [{ name: 'Horas Extra', data: graficaExtrasDatos.value.datos ?? [] }])

const modules = [
  { name: 'Directorio de personal', desc: 'Alta, edición y consulta', route: 'empleados.index', icon: 'ti-address-book', tone: 'bg-blue-50 text-blue-600 border-blue-100' },
  { name: 'Control de asistencias', desc: 'Captura entradas y salidas', route: 'asistencias.index', icon: 'ti-clock-check', tone: 'bg-teal-50 text-teal-600 border-teal-100' },
  { name: 'Generar nóminas', desc: 'Calcula pagos y recibos', route: 'nominas.index', icon: 'ti-file-invoice', tone: 'bg-amber-50 text-amber-600 border-amber-100' },
]

// Configuración ApexCharts (Light Theme Corporativo)
const donutOptions = {
  chart: { type: 'donut', fontFamily: 'Sora, sans-serif', background: 'transparent' },
  theme: { mode: 'light' },
  labels: ['Asistencias', 'Faltas', 'Vacaciones', 'Incapacidades'],
  colors: ['#0d9488', '#f43f5e', '#3b82f6', '#8b5cf6'],
  dataLabels: { enabled: false },
  stroke: { colors: ['#ffffff'], width: 2 },
  legend: { position: 'bottom', markers: { radius: 12 } }
};

const barOptions = computed(() => ({
  chart: { type: 'bar', fontFamily: 'Sora, sans-serif', toolbar: { show: false }, background: 'transparent' },
  theme: { mode: 'light' },
  colors: ['#3b82f6'],
  plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: graficaExtrasDatos.value.categorias ?? [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#64748b', fontWeight: 500 } }
  },
  yaxis: { title: { text: 'Horas Extra Totales', style: { color: '#64748b' } } },
  grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
}));
</script>

<template>
  <Head title="Panel Principal" />

  <AuthenticatedLayout>

    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="font-['Sora'] text-2xl font-bold text-slate-900 tracking-tight">Resumen Operativo</h1>
        <p class="text-sm font-medium text-slate-500 mt-1">Sistema PROMATEC-LUGARTH · Semana {{ semanaContable }}</p>
      </div>
      <div class="flex items-center gap-2 bg-blue-50 border border-blue-100 px-4 py-2 rounded-full shadow-sm">
        <i class="ti ti-calendar text-blue-600 text-lg"></i>
        <span class="text-sm font-bold text-blue-700">Corte: {{ corteSemana }}</span>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform"><i class="ti ti-users text-6xl text-blue-600"></i></div>
        <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-4 border border-blue-100"><i class="ti ti-users"></i></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Personal activo</p>
        <p class="font-['Sora'] text-3xl font-extrabold text-slate-800">{{ totalEmpleados }}</p>
      </div>

      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform"><i class="ti ti-calendar-stats text-6xl text-teal-600"></i></div>
        <div class="h-12 w-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl mb-4 border border-teal-100"><i class="ti ti-calendar-stats"></i></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Semana contable</p>
        <p class="font-['Sora'] text-3xl font-extrabold text-slate-800">No. {{ semanaContable }}</p>
      </div>

      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform"><i class="ti ti-cash text-6xl text-amber-500"></i></div>
        <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mb-4 border border-amber-100"><i class="ti ti-cash"></i></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Gasto semanal</p>
        <p class="font-['Sora'] text-3xl font-extrabold text-slate-800">${{ gastoSemanal }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">

      <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
          <h2 class="font-['Sora'] text-base font-bold text-slate-800">Módulos principales</h2>
          <p class="text-xs text-slate-500 mt-0.5">Accesos directos de operación</p>
        </div>
        <div class="p-2">
          <Link v-for="mod in modules" :key="mod.route" :href="route(mod.route)" class="flex items-center gap-4 p-4 rounded-xl hover:bg-slate-50 transition-colors group">
            <div :class="['h-12 w-12 rounded-xl flex items-center justify-center text-xl shrink-0 border', mod.tone]">
              <i :class="['ti', mod.icon]"></i>
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-slate-800 group-hover:text-blue-600 transition-colors">{{ mod.name }}</p>
              <p class="text-xs font-medium text-slate-500 mt-0.5">{{ mod.desc }}</p>
            </div>
            <i class="ti ti-chevron-right text-slate-300 group-hover:text-blue-500 group-hover:translate-x-1 transition-all"></i>
          </Link>
        </div>
      </div>

      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
        <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
          <h2 class="font-['Sora'] text-base font-bold text-slate-800">Estado del sistema</h2>
          <p class="text-xs text-slate-500 mt-0.5">Indicadores en tiempo real</p>
        </div>
        <div class="p-6 flex-1 flex flex-col">
          <div class="space-y-4 flex-1">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
              <span class="text-sm font-semibold text-slate-600">Faltas del mes</span>
              <span :class="['px-3 py-1 rounded-full text-xs font-bold border', kpisDashboard.faltas > 0 ? 'bg-rose-50 text-rose-600 border-rose-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200']">
                {{ kpisDashboard.faltas }} faltas
              </span>
            </div>
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
              <span class="text-sm font-semibold text-slate-600">Estatus nóminas</span>
              <span class="px-3 py-1 rounded-full text-xs font-bold border bg-emerald-50 text-emerald-600 border-emerald-200">Al día</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-slate-600">Pendientes de pago</span>
              <span :class="['px-3 py-1 rounded-full text-xs font-bold border', nominasPendientes > 0 ? 'bg-amber-50 text-amber-600 border-amber-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200']">
                {{ nominasPendientes }} pendiente(s)
              </span>
            </div>
          </div>
          <div class="mt-6 flex gap-3 pt-6 border-t border-slate-100">
            <Link :href="route('nominas.index')" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold py-2.5 rounded-lg text-center transition-colors shadow-sm">
              <i class="ti ti-file-plus mr-1" aria-hidden="true"></i>
              Nueva nómina
            </Link>
            <Link :href="route('empleados.index')" class="flex-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold py-2.5 rounded-lg text-center transition-colors shadow-sm">
              <i class="ti ti-user-plus mr-1" aria-hidden="true"></i>
              Nuevo empleado
            </Link>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-['Sora'] text-sm font-bold text-slate-800 mb-1">Tipos de Asistencia</h2>
        <p class="text-xs text-slate-500 mb-6">Distribución del mes actual</p>
        <div class="flex justify-center"><VueApexCharts width="100%" height="280" :options="donutOptions" :series="graficaAsistencia" /></div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-['Sora'] text-sm font-bold text-slate-800 mb-1">Tendencia de Horas Extra</h2>
        <p class="text-xs text-slate-500 mb-4">Histórico general últimos 7 días</p>
        <VueApexCharts width="100%" height="280" :options="barOptions" :series="barSeries" />
      </div>

      <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl border border-indigo-100 shadow-sm p-6 relative overflow-hidden">
        <i class="ti ti-confetti absolute -right-4 -bottom-4 text-8xl text-indigo-500/10"></i>
        <div class="relative z-10">
          <div class="flex items-center gap-2 mb-1">
            <i class="ti ti-cake text-indigo-600 text-xl"></i>
            <h2 class="font-['Sora'] text-sm font-bold text-indigo-950">Próximos Cumpleaños</h2>
          </div>
          <p class="text-xs text-indigo-600/70 font-medium mb-6">Colaboradores de este mes</p>

          <div v-if="cumpleanerosMes.length > 0" class="space-y-3 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
            <div v-for="emp in cumpleanerosMes" :key="emp.id" class="bg-white/60 backdrop-blur-sm border border-white/80 p-3 rounded-xl flex items-center justify-between shadow-sm">
              <div class="min-w-0 pr-3">
                <p class="text-sm font-bold text-indigo-950 truncate">{{ emp.nombre_completo }}</p>
                <p class="text-[10px] font-semibold text-indigo-400 mt-0.5 uppercase tracking-wider">ID: {{ emp.numero_empleado || emp.id }}</p>
              </div>
              <span :class="['px-2.5 py-1 rounded-md text-xs font-bold border whitespace-nowrap', emp.es_hoy ? 'bg-indigo-600 text-white border-indigo-700 shadow-md animate-pulse' : 'bg-indigo-100 text-indigo-700 border-indigo-200']">
                {{ emp.es_hoy ? '¡Es hoy!' : `Día ${emp.dia}` }}
              </span>
            </div>
          </div>

          <div v-else class="flex flex-col items-center justify-center py-10 opacity-60">
            <i class="ti ti-mood-sad text-4xl text-indigo-300 mb-2"></i>
            <p class="text-sm font-semibold text-indigo-800">No hay pasteles este mes</p>
          </div>
        </div>
      </div>

    </div>

  </AuthenticatedLayout>
</template>

<style scoped>
/* Scrolbar personalizada para la lista de cumpleaños */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.5); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.5); }
</style>
