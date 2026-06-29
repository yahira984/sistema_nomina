<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, defineAsyncComponent } from 'vue'

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'))
const page = usePage()
const can = computed(() => page.props.auth?.can ?? {})

const props = defineProps({
  totalEmpleados:    { type: Number, default: 0 },
  semanaContable:    { type: Number, default: 0 },
  gastoSemanal:      { type: [Number, String], default: '0.00' },
  corteSemana:       { type: String, default: 'Jueves a miércoles' },
  nominasPendientes: { type: Number, default: 0 },
  kpis:              Object,
  graficaAsistencia: Array,
  graficaExtras:     Object,
  retardosControl:   { type: Object, default: () => ({}) },
  tempranoControl:   { type: Object, default: () => ({}) },
  finanzasNomina:    { type: Object, default: () => ({}) },
  operatividad:      { type: Object, default: () => ({}) },
  recursosHumanos:   { type: Object, default: () => ({}) },
  diasFestivos:      { type: Object, default: () => ({}) },
  avanceLaboralAnual:{ type: Object, default: () => ({}) }
})

const kpisDashboard = computed(() => props.kpis ?? { faltas: 0, cumpleaneros: [] })
const cumpleanerosMes = computed(() => kpisDashboard.value.cumpleaneros ?? [])
const graficaExtrasDatos = computed(() => props.graficaExtras ?? { categorias: [], datos: [] })
const barSeries = computed(() => [{ name: 'Horas Extra', data: graficaExtrasDatos.value.datos ?? [] }])
const periodosControl = [
  { key: 'semana', title: 'Semana', icon: 'ti-calendar-week' },
  { key: 'mes', title: 'Mes', icon: 'ti-calendar-month' },
  { key: 'anio', title: 'Año', icon: 'ti-calendar-stats' },
]
const controlVacio = () => ({ periodo: '', inicio: '', fin: '', lider: null, ranking: [] })
const normalizarControl = (data) => {
  const base = { ...controlVacio(), ...(data ?? {}) }
  const ranking = Array.isArray(base.ranking) && base.ranking.length > 0
    ? base.ranking
    : (base.lider ? [base.lider] : [])

  return { ...base, ranking }
}
const retardosControlData = computed(() => props.retardosControl ?? {})
const tempranoControlData = computed(() => props.tempranoControl ?? {})
const finanzasData = computed(() => props.finanzasNomina ?? {})
const operatividadData = computed(() => props.operatividad ?? {})
const rhData = computed(() => props.recursosHumanos ?? {})
const diasFestivosData = computed(() => props.diasFestivos ?? { mes: [], proximos: [] })
const festivosDelMes = computed(() => diasFestivosData.value.mes ?? [])
const proximosFestivos = computed(() => diasFestivosData.value.proximos ?? [])
const avanceLaboralData = computed(() => props.avanceLaboralAnual ?? {
  anio: new Date().getFullYear(),
  dias_calendario: 0,
  domingos: 0,
  festivos_descontados: 0,
  dias_laborables: 0,
  dias_transcurridos: 0,
  dias_pendientes: 0,
  porcentaje: 0,
  festivos: [],
})
const avanceLaboralPorcentaje = computed(() => Math.max(0, Math.min(100, Number(avanceLaboralData.value.porcentaje || 0))))
const avanceLaboralStyle = computed(() => ({
  background: `conic-gradient(#0d9488 ${avanceLaboralPorcentaje.value * 3.6}deg, #e2e8f0 0deg)`,
}))
const desgloseGasto = computed(() => finanzasData.value.desgloseGasto ?? { labels: [], datos: [], porcentajes: [], total: 0 })
const prestamosData = computed(() => finanzasData.value.prestamos ?? { capitalPrestado: 0, recuperadoMes: 0 })
const comparativaData = computed(() => finanzasData.value.comparativa ?? { labels: [], datos: [], actual: 0, anterior: 0, variacion: 0 })
const mapaCalorData = computed(() => operatividadData.value.mapaCalor ?? { labels: [], series: [] })
const puntualidadData = computed(() => operatividadData.value.puntualidad ?? { porcentaje: 0, perfectos: 0, evaluados: 0 })
const rotacionData = computed(() => rhData.value.rotacion ?? { labels: [], altas: [], bajas: [], totalAltas: 0, totalBajas: 0 })
const pasivoVacacional = computed(() => rhData.value.pasivoVacacional ?? { diasPendientes: 0, empleadosConSaldo: 0 })
const antiguedadData = computed(() => rhData.value.antiguedad ?? { labels: [], datos: [] })
const desgloseSinDatos = computed(() => (desgloseGasto.value.datos ?? []).reduce((total, item) => total + Number(item || 0), 0) <= 0)
const desgloseLabels = computed(() => desgloseSinDatos.value ? ['Sin nominas'] : (desgloseGasto.value.labels ?? []))
const desgloseSeries = computed(() => desgloseSinDatos.value ? [1] : (desgloseGasto.value.datos ?? []))
const puntualidadSeries = computed(() => [Number(puntualidadData.value.porcentaje || 0)])
const comparativaSeries = computed(() => [{ name: 'Gasto neto', data: comparativaData.value.datos ?? [] }])
const rotacionSeries = computed(() => [
  { name: 'Altas', data: rotacionData.value.altas ?? [] },
  { name: 'Bajas', data: rotacionData.value.bajas ?? [] },
])
const antiguedadSeries = computed(() => [{ name: 'Empleados', data: antiguedadData.value.datos ?? [] }])
const retardosItems = computed(() => periodosControl.map(item => ({
  ...item,
  tone: 'border-amber-200 bg-amber-50 text-amber-800',
  data: normalizarControl(retardosControlData.value[item.key]),
})))
const tempranoItems = computed(() => periodosControl.map(item => ({
  ...item,
  tone: 'border-emerald-200 bg-emerald-50 text-emerald-800',
  data: normalizarControl(tempranoControlData.value[item.key]),
})))

const formatoMinutos = (minutos) => {
  const total = Number(minutos || 0)
  const horas = Math.floor(total / 60)
  const resto = total % 60

  return horas > 0 ? `${horas} h ${resto} min` : `${resto} min`
}

const formatoMoneda = (valor) => Number(valor || 0).toLocaleString('es-MX', {
  style: 'currency',
  currency: 'MXN',
  maximumFractionDigits: 2,
})

const formatoNumero = (valor) => Number(valor || 0).toLocaleString('es-MX', {
  maximumFractionDigits: 1,
})

const textoDiasRestantes = (dias) => {
  const total = Number(dias || 0)
  if (total === 0) return 'Hoy'
  if (total === 1) return 'Manana'
  return `En ${total} dias`
}

const modules = [
  { name: 'Directorio de personal', desc: 'Alta, edición y consulta', route: 'empleados.index', icon: 'ti-address-book', tone: 'bg-blue-50 text-blue-600 border-blue-100', permission: 'empleados.view' },
  { name: 'Control de asistencias', desc: 'Captura entradas y salidas', route: 'asistencias.index', icon: 'ti-clock-check', tone: 'bg-teal-50 text-teal-600 border-teal-100', permission: 'asistencias.view' },
  { name: 'Generar nóminas', desc: 'Calcula pagos y recibos', route: 'nominas.index', icon: 'ti-file-invoice', tone: 'bg-amber-50 text-amber-600 border-amber-100', permission: 'nominas.view' },
]
const visibleModules = computed(() => modules.filter(mod => can.value[mod.permission]))

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

const desgloseGastoOptions = computed(() => ({
  chart: { type: 'donut', fontFamily: 'Sora, sans-serif', background: 'transparent' },
  labels: desgloseLabels.value,
  colors: desgloseSinDatos.value ? ['#cbd5e1'] : ['#0f766e', '#2563eb', '#f59e0b', '#8b5cf6'],
  dataLabels: { enabled: false },
  stroke: { colors: ['#ffffff'], width: 3 },
  legend: {
    position: 'bottom',
    fontSize: '11px',
    fontWeight: 700,
    labels: { colors: '#475569' },
    markers: { radius: 8 }
  },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Total',
            formatter: () => formatoMoneda(desgloseGasto.value.total || 0)
          }
        }
      }
    }
  },
  tooltip: { y: { formatter: (value) => formatoMoneda(value) } }
}))

const comparativaOptions = computed(() => ({
  chart: { type: 'area', fontFamily: 'Sora, sans-serif', toolbar: { show: false }, background: 'transparent' },
  colors: ['#0d9488'],
  stroke: { width: 3, curve: 'smooth' },
  fill: { type: 'gradient', gradient: { shadeIntensity: 0.35, opacityFrom: 0.3, opacityTo: 0.05 } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: comparativaData.value.labels ?? [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#64748b', fontWeight: 700 } }
  },
  yaxis: { labels: { formatter: (value) => `$${Math.round(value).toLocaleString('es-MX')}` } },
  grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
  tooltip: { y: { formatter: (value) => formatoMoneda(value) } }
}))

const heatmapOptions = computed(() => ({
  chart: { type: 'heatmap', fontFamily: 'Sora, sans-serif', toolbar: { show: false }, background: 'transparent' },
  dataLabels: { enabled: true, style: { colors: ['#0f172a'], fontWeight: 800 } },
  colors: ['#ef4444'],
  plotOptions: {
    heatmap: {
      radius: 6,
      enableShades: true,
      colorScale: {
        ranges: [
          { from: 0, to: 0, color: '#ecfdf5', name: 'Limpio' },
          { from: 1, to: 2, color: '#fef3c7', name: 'Bajo' },
          { from: 3, to: 5, color: '#fed7aa', name: 'Medio' },
          { from: 6, to: 999, color: '#fecaca', name: 'Alto' },
        ]
      }
    }
  },
  xaxis: { labels: { style: { colors: '#64748b', fontWeight: 800 } } },
  yaxis: { labels: { style: { colors: '#334155', fontWeight: 800 } } },
  tooltip: { y: { formatter: (value) => `${value} evento(s)` } }
}))

const puntualidadOptions = computed(() => ({
  chart: { type: 'radialBar', fontFamily: 'Sora, sans-serif', sparkline: { enabled: true }, background: 'transparent' },
  colors: [puntualidadData.value.porcentaje >= 90 ? '#10b981' : puntualidadData.value.porcentaje >= 75 ? '#f59e0b' : '#ef4444'],
  plotOptions: {
    radialBar: {
      startAngle: -135,
      endAngle: 135,
      hollow: { size: '62%' },
      track: { background: '#e2e8f0', strokeWidth: '96%' },
      dataLabels: {
        name: { show: true, offsetY: 28, color: '#64748b', fontSize: '11px', fontWeight: 800 },
        value: { show: true, offsetY: -10, color: '#0f172a', fontSize: '30px', fontWeight: 900, formatter: value => `${Number(value).toFixed(1)}%` },
      }
    }
  },
  labels: ['Puntualidad']
}))

const rotacionOptions = computed(() => ({
  chart: { type: 'bar', stacked: true, fontFamily: 'Sora, sans-serif', toolbar: { show: false }, background: 'transparent' },
  colors: ['#10b981', '#f43f5e'],
  plotOptions: { bar: { borderRadius: 6, columnWidth: '52%' } },
  dataLabels: { enabled: false },
  xaxis: { categories: rotacionData.value.labels ?? [], labels: { style: { colors: '#64748b', fontWeight: 800 } } },
  yaxis: { labels: { formatter: value => Math.round(value) } },
  grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
  legend: { position: 'top', horizontalAlign: 'right', fontWeight: 800 }
}))

const antiguedadOptions = computed(() => ({
  chart: { type: 'bar', fontFamily: 'Sora, sans-serif', toolbar: { show: false }, background: 'transparent' },
  colors: ['#6366f1'],
  plotOptions: { bar: { horizontal: true, borderRadius: 8, barHeight: '52%' } },
  dataLabels: { enabled: true, style: { colors: ['#ffffff'], fontWeight: 900 } },
  xaxis: { categories: antiguedadData.value.labels ?? [], labels: { style: { colors: '#64748b', fontWeight: 700 } } },
  yaxis: { labels: { style: { colors: '#334155', fontWeight: 800 } } },
  grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
  tooltip: { y: { formatter: value => `${value} empleado(s)` } }
}))
</script>

<template>
  <Head title="Panel Principal" />

  <AuthenticatedLayout>

    <div class="mb-6 flex flex-col justify-between gap-4 md:mb-8 md:flex-row md:items-center">
      <div>
        <h1 class="font-['Sora'] text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Resumen Operativo</h1>
        <p class="text-sm font-medium text-slate-500 mt-1">Sistema PROMATEC-LUGARTH · Semana {{ semanaContable }}</p>
      </div>
      <div class="flex w-full items-center justify-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-4 py-2 shadow-sm sm:w-auto">
        <i class="ti ti-calendar text-blue-600 text-lg"></i>
        <span class="text-sm font-bold text-blue-700">Corte: {{ corteSemana }}</span>
      </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 md:mb-8 md:grid-cols-3 md:gap-6">
      <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md sm:p-6">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform"><i class="ti ti-users text-6xl text-blue-600"></i></div>
        <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-4 border border-blue-100"><i class="ti ti-users"></i></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Personal activo</p>
        <p class="font-['Sora'] text-2xl font-extrabold text-slate-800 sm:text-3xl">{{ totalEmpleados }}</p>
      </div>

      <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md sm:p-6">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform"><i class="ti ti-calendar-stats text-6xl text-teal-600"></i></div>
        <div class="h-12 w-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl mb-4 border border-teal-100"><i class="ti ti-calendar-stats"></i></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Semana contable</p>
        <p class="font-['Sora'] text-2xl font-extrabold text-slate-800 sm:text-3xl">No. {{ semanaContable }}</p>
      </div>

      <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md sm:p-6">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform"><i class="ti ti-cash text-6xl text-amber-500"></i></div>
        <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mb-4 border border-amber-100"><i class="ti ti-cash"></i></div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Gasto semanal</p>
        <p class="break-words font-['Sora'] text-2xl font-extrabold text-slate-800 sm:text-3xl">${{ gastoSemanal }}</p>
      </div>
    </div>

    <section class="mb-8 overflow-hidden rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-teal-50 shadow-sm">
      <div class="grid gap-0 lg:grid-cols-12">
        <div class="border-b border-amber-100/70 p-5 sm:p-6 lg:col-span-4 lg:border-b-0 lg:border-r">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-white px-3 py-1 text-[10px] font-black uppercase tracking-wider text-amber-700">
                <i class="ti ti-calendar-event" aria-hidden="true"></i>
                Calendario Mexico
              </p>
              <h2 class="mt-4 font-['Sora'] text-xl font-black text-slate-950">Dias festivos</h2>
              <p class="mt-2 text-sm font-semibold text-slate-500">Oficiales por LFT y ajustes manuales de la empresa.</p>
            </div>
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-amber-200 bg-white text-2xl text-amber-600 shadow-sm">
              <i class="ti ti-confetti" aria-hidden="true"></i>
            </div>
          </div>

          <Link v-if="can['sistema.dias_festivos']" :href="route('dias-festivos.index')" class="mt-5 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black uppercase tracking-wider text-slate-700 shadow-sm transition hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700">
            <i class="ti ti-settings" aria-hidden="true"></i>
            Editar calendario
          </Link>
        </div>

        <div class="p-5 sm:p-6 lg:col-span-5">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-wider text-slate-400">Proximos descansos</p>
              <p class="text-sm font-bold text-slate-700">Los siguientes 5 dias activos</p>
            </div>
            <span class="rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-teal-700">{{ proximosFestivos.length }} activo(s)</span>
          </div>

          <div v-if="proximosFestivos.length > 0" class="grid gap-2">
            <div v-for="dia in proximosFestivos" :key="dia.id" class="flex items-center gap-3 rounded-2xl border border-white bg-white/90 p-3 shadow-sm">
              <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl border border-amber-100 bg-amber-50 text-amber-700">
                <span class="text-[10px] font-black uppercase">{{ dia.fecha_corta?.split(' ')?.[1] || '' }}</span>
                <span class="text-lg font-black leading-none">{{ dia.fecha_corta?.split(' ')?.[0] || '' }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-black text-slate-900">{{ dia.nombre }}</p>
                <p class="truncate text-xs font-semibold capitalize text-slate-500">{{ dia.dia_semana }} · {{ dia.fecha }}</p>
              </div>
              <span class="rounded-lg border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase text-emerald-700">
                {{ textoDiasRestantes(dia.dias_restantes) }}
              </span>
            </div>
          </div>

          <div v-else class="flex min-h-32 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white/70 text-center">
            <i class="ti ti-calendar-off text-3xl text-slate-300" aria-hidden="true"></i>
            <p class="mt-2 text-sm font-bold text-slate-500">Sin festivos proximos generados</p>
          </div>
        </div>

        <div class="border-t border-amber-100/70 p-5 sm:p-6 lg:col-span-3 lg:border-l lg:border-t-0">
          <p class="text-xs font-black uppercase tracking-wider text-slate-400">Este mes</p>
          <div v-if="festivosDelMes.length > 0" class="mt-4 space-y-3">
            <div v-for="dia in festivosDelMes" :key="`mes-${dia.id}`" class="rounded-2xl border border-white bg-white/90 p-3 shadow-sm">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-black text-slate-900">{{ dia.nombre }}</p>
                  <p class="mt-1 text-xs font-semibold capitalize text-slate-500">{{ dia.dia_semana }} · {{ dia.fecha_corta }}</p>
                </div>
                <span :class="['rounded-full border px-2 py-1 text-[9px] font-black uppercase tracking-wider', dia.es_oficial ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-blue-200 bg-blue-50 text-blue-700']">
                  {{ dia.es_oficial ? 'Oficial' : 'Manual' }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-white/70 p-5 text-center">
            <i class="ti ti-calendar-check text-2xl text-slate-300" aria-hidden="true"></i>
            <p class="mt-2 text-sm font-bold text-slate-500">Sin festivos este mes</p>
          </div>
        </div>
      </div>
    </section>

    <section class="mb-8 overflow-hidden rounded-2xl border border-teal-100 bg-white shadow-sm">
      <div class="grid gap-0 xl:grid-cols-12">
        <div class="border-b border-teal-100 bg-gradient-to-br from-teal-50 to-white p-5 sm:p-6 xl:col-span-4 xl:border-b-0 xl:border-r">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-white px-3 py-1 text-[10px] font-black uppercase tracking-wider text-teal-700">
                <i class="ti ti-calendar-stats" aria-hidden="true"></i>
                Avance {{ avanceLaboralData.anio }}
              </p>
              <h2 class="mt-4 font-['Sora'] text-xl font-black text-slate-950">Dias laborales del año</h2>
              <p class="mt-2 text-sm font-semibold text-slate-500">Lunes a sabado, sin domingos ni festivos activos.</p>
            </div>
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-teal-200 bg-white text-2xl text-teal-600 shadow-sm">
              <i class="ti ti-progress-check" aria-hidden="true"></i>
            </div>
          </div>

          <div class="mt-6 rounded-2xl border border-white bg-white p-4 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wider text-slate-400">Formula anual</p>
            <p class="mt-2 text-sm font-black text-slate-900">
              {{ avanceLaboralData.dias_calendario }} dias - {{ avanceLaboralData.domingos }} domingos - {{ avanceLaboralData.festivos_descontados }} festivos
            </p>
            <p class="mt-1 text-lg font-black text-teal-700">
              = {{ avanceLaboralData.dias_laborables }} dias laborables
            </p>
          </div>
        </div>

        <div class="p-5 sm:p-6 xl:col-span-4">
          <div class="flex h-full flex-col items-center justify-center gap-5">
            <div class="relative flex h-56 w-56 items-center justify-center rounded-full p-4 shadow-inner" :style="avanceLaboralStyle">
              <div class="flex h-full w-full flex-col items-center justify-center rounded-full bg-white text-center shadow-sm">
                <p class="font-['Sora'] text-4xl font-black text-slate-950">{{ formatoNumero(avanceLaboralData.dias_transcurridos) }}</p>
                <p class="text-xs font-black uppercase tracking-wider text-slate-400">de {{ formatoNumero(avanceLaboralData.dias_laborables) }}</p>
                <p class="mt-2 rounded-full border border-teal-100 bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">
                  {{ formatoNumero(avanceLaboralPorcentaje) }}%
                </p>
              </div>
            </div>

            <div class="text-center">
              <p class="text-base font-black text-slate-900">Llevamos {{ avanceLaboralData.dias_transcurridos }} de {{ avanceLaboralData.dias_laborables }} dias</p>
              <p class="mt-1 text-sm font-semibold text-slate-500">Quedan {{ avanceLaboralData.dias_pendientes }} dias pendientes por trabajar.</p>
            </div>
          </div>
        </div>

        <div class="border-t border-teal-100 bg-slate-50/60 p-5 sm:p-6 xl:col-span-4 xl:border-l xl:border-t-0">
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-white bg-white p-4 shadow-sm">
              <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Trabajados</p>
              <p class="mt-2 text-2xl font-black text-teal-700">{{ avanceLaboralData.dias_transcurridos }}</p>
            </div>
            <div class="rounded-2xl border border-white bg-white p-4 shadow-sm">
              <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Pendientes</p>
              <p class="mt-2 text-2xl font-black text-amber-700">{{ avanceLaboralData.dias_pendientes }}</p>
            </div>
            <div class="rounded-2xl border border-white bg-white p-4 shadow-sm">
              <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Domingos fuera</p>
              <p class="mt-2 text-2xl font-black text-slate-800">{{ avanceLaboralData.domingos }}</p>
            </div>
            <div class="rounded-2xl border border-white bg-white p-4 shadow-sm">
              <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Festivos fuera</p>
              <p class="mt-2 text-2xl font-black text-blue-700">{{ avanceLaboralData.festivos_descontados }}</p>
            </div>
          </div>

          <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 p-4">
            <p class="text-xs font-black uppercase tracking-wider text-blue-700">Festivos que descuentan</p>
            <div v-if="(avanceLaboralData.festivos || []).length > 0" class="mt-3 max-h-36 space-y-2 overflow-y-auto pr-1">
              <div v-for="dia in avanceLaboralData.festivos" :key="`avance-${dia.id}`" class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2 text-xs shadow-sm">
                <span class="truncate font-black text-slate-800">{{ dia.nombre }}</span>
                <span class="shrink-0 font-bold text-blue-700">{{ dia.fecha_corta }}</span>
              </div>
            </div>
            <p v-else class="mt-3 text-sm font-semibold text-slate-500">Sin festivos laborables activos para descontar.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-xl text-emerald-700">
            <i class="ti ti-report-money" aria-hidden="true"></i>
          </div>
          <div>
            <h2 class="font-['Sora'] text-base font-bold text-slate-800">Finanzas y Nomina</h2>
            <p class="text-xs font-semibold text-slate-500">Gasto, prestamos y comparativa semanal</p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 p-5 sm:p-6 xl:grid-cols-12">
        <article class="rounded-2xl border border-slate-100 bg-slate-50/40 p-4 shadow-sm xl:col-span-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-wider text-slate-400">Desglose del gasto semanal</p>
              <p class="mt-1 text-sm font-bold text-slate-800">{{ formatoMoneda(desgloseGasto.total || 0) }}</p>
            </div>
            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700">Semana {{ semanaContable }}</span>
          </div>
          <VueApexCharts width="100%" height="260" :options="desgloseGastoOptions" :series="desgloseSeries" />
          <div class="grid grid-cols-2 gap-2 text-[11px] font-bold text-slate-600">
            <div v-for="(label, index) in desgloseGasto.labels" :key="label" class="rounded-lg border border-white bg-white px-3 py-2 shadow-sm">
              <span class="block text-slate-400">{{ label }}</span>
              <span>{{ formatoMoneda(desgloseGasto.datos?.[index] || 0) }} · {{ formatoNumero(desgloseGasto.porcentajes?.[index] || 0) }}%</span>
            </div>
          </div>
        </article>

        <article class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-4 shadow-sm xl:col-span-3">
          <div class="mb-4 flex items-center justify-between">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-blue-200 bg-white text-xl text-blue-700 shadow-sm">
              <i class="ti ti-cash-banknote" aria-hidden="true"></i>
            </div>
            <span class="rounded-full border border-blue-200 bg-white px-3 py-1 text-[10px] font-black uppercase tracking-wider text-blue-700">Prestamos</span>
          </div>
          <p class="text-xs font-black uppercase tracking-wider text-blue-500">Capital prestado total</p>
          <p class="mt-1 break-words font-['Sora'] text-2xl font-black text-slate-900">{{ formatoMoneda(prestamosData.capitalPrestado) }}</p>
          <div class="mt-5 rounded-xl border border-emerald-100 bg-white p-3 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600">Recuperado este mes</p>
            <p class="mt-1 text-lg font-black text-emerald-700">{{ formatoMoneda(prestamosData.recuperadoMes) }}</p>
          </div>
          <p class="mt-3 text-[11px] font-semibold text-slate-500">Se calcula con saldos actuales y nominas marcadas como pagadas.</p>
        </article>

        <article class="rounded-2xl border border-teal-100 bg-white p-4 shadow-sm xl:col-span-4">
          <div class="mb-3 flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-wider text-slate-400">Comparativa de nomina</p>
              <p class="mt-1 text-sm font-bold text-slate-800">Semana actual vs anteriores</p>
            </div>
            <span :class="['rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-wider', Number(comparativaData.variacion || 0) > 0 ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700']">
              {{ Number(comparativaData.variacion || 0) > 0 ? '+' : '' }}{{ formatoMoneda(comparativaData.variacion) }}
            </span>
          </div>
          <VueApexCharts width="100%" height="245" :options="comparativaOptions" :series="comparativaSeries" />
          <div class="grid grid-cols-2 gap-2 text-center">
            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
              <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Semana pasada</p>
              <p class="text-sm font-black text-slate-800">{{ formatoMoneda(comparativaData.anterior) }}</p>
            </div>
            <div class="rounded-xl border border-teal-100 bg-teal-50 px-3 py-2">
              <p class="text-[10px] font-black uppercase tracking-wider text-teal-600">Semana actual</p>
              <p class="text-sm font-black text-teal-800">{{ formatoMoneda(comparativaData.actual) }}</p>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-xl text-amber-700">
            <i class="ti ti-activity-heartbeat" aria-hidden="true"></i>
          </div>
          <div>
            <h2 class="font-['Sora'] text-base font-bold text-slate-800">Operatividad y Asistencias</h2>
            <p class="text-xs font-semibold text-slate-500">Puntualidad global y dias con mas incidencias</p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 p-5 sm:p-6 lg:grid-cols-5">
        <article class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4 shadow-sm lg:col-span-2">
          <div class="mb-2 flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-wider text-emerald-600">Tasa de puntualidad global</p>
              <p class="mt-1 text-sm font-semibold text-slate-500">Sin estudiantes ni empleados exentos</p>
            </div>
            <i class="ti ti-gauge text-2xl text-emerald-600" aria-hidden="true"></i>
          </div>
          <VueApexCharts width="100%" height="235" :options="puntualidadOptions" :series="puntualidadSeries" />
          <div class="grid grid-cols-2 gap-2 text-center">
            <div class="rounded-xl border border-white bg-white px-3 py-2 shadow-sm">
              <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Perfectos</p>
              <p class="text-lg font-black text-emerald-700">{{ puntualidadData.perfectos }}</p>
            </div>
            <div class="rounded-xl border border-white bg-white px-3 py-2 shadow-sm">
              <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Evaluados</p>
              <p class="text-lg font-black text-slate-800">{{ puntualidadData.evaluados }}</p>
            </div>
          </div>
        </article>

        <article class="rounded-2xl border border-slate-100 bg-slate-50/40 p-4 shadow-sm lg:col-span-3">
          <div class="mb-3 flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-wider text-slate-400">Mapa de calor de ausentismo</p>
              <p class="mt-1 text-sm font-bold text-slate-800">Faltas y retardos por dia del mes</p>
            </div>
            <span class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-rose-700">Riesgo visual</span>
          </div>
          <VueApexCharts width="100%" height="260" :options="heatmapOptions" :series="mapaCalorData.series || []" />
        </article>
      </div>
    </section>

    <section class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-xl text-indigo-700">
            <i class="ti ti-users-group" aria-hidden="true"></i>
          </div>
          <div>
            <h2 class="font-['Sora'] text-base font-bold text-slate-800">Recursos Humanos</h2>
            <p class="text-xs font-semibold text-slate-500">Rotacion, vacaciones pendientes y antiguedad del equipo</p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 p-5 sm:p-6 xl:grid-cols-12">
        <article class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm xl:col-span-5">
          <div class="mb-3 flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-wider text-slate-400">Termometro de rotacion</p>
              <p class="mt-1 text-sm font-bold text-slate-800">Altas vs bajas del mes</p>
            </div>
            <div class="flex gap-2 text-[10px] font-black uppercase tracking-wider">
              <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-emerald-700">+{{ rotacionData.totalAltas }}</span>
              <span class="rounded-full border border-rose-200 bg-rose-50 px-2 py-1 text-rose-700">-{{ rotacionData.totalBajas }}</span>
            </div>
          </div>
          <VueApexCharts width="100%" height="245" :options="rotacionOptions" :series="rotacionSeries" />
        </article>

        <article class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-4 shadow-sm xl:col-span-3">
          <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl border border-amber-200 bg-white text-xl text-amber-700 shadow-sm">
            <i class="ti ti-beach" aria-hidden="true"></i>
          </div>
          <p class="text-xs font-black uppercase tracking-wider text-amber-600">Pasivo vacacional</p>
          <p class="mt-1 font-['Sora'] text-3xl font-black text-slate-900">{{ formatoNumero(pasivoVacacional.diasPendientes) }}</p>
          <p class="text-sm font-bold text-slate-500">dias pendientes</p>
          <div class="mt-5 rounded-xl border border-white bg-white px-3 py-2 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Empleados con saldo</p>
            <p class="text-lg font-black text-amber-700">{{ pasivoVacacional.empleadosConSaldo }}</p>
          </div>
        </article>

        <article class="rounded-2xl border border-indigo-100 bg-slate-50/40 p-4 shadow-sm xl:col-span-4">
          <div class="mb-3">
            <p class="text-xs font-black uppercase tracking-wider text-slate-400">Distribucion por antiguedad</p>
            <p class="mt-1 text-sm font-bold text-slate-800">Experiencia acumulada del equipo</p>
          </div>
          <VueApexCharts width="100%" height="250" :options="antiguedadOptions" :series="antiguedadSeries" />
        </article>
      </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">

      <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
          <h2 class="font-['Sora'] text-base font-bold text-slate-800">Módulos principales</h2>
          <p class="text-xs text-slate-500 mt-0.5">Accesos directos de operación</p>
        </div>
        <div class="p-2">
          <Link v-for="mod in visibleModules" :key="mod.route" :href="route(mod.route)" class="flex items-center gap-4 p-4 rounded-xl hover:bg-slate-50 transition-colors group">
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
        <div class="flex flex-1 flex-col p-5 sm:p-6">
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
          <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row">
            <Link v-if="can['nominas.view']" :href="route('nominas.index')" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold py-2.5 rounded-lg text-center transition-colors shadow-sm">
              <i class="ti ti-file-plus mr-1" aria-hidden="true"></i>
              Nueva nómina
            </Link>
            <Link v-if="can['empleados.view']" :href="route('empleados.index')" class="flex-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold py-2.5 rounded-lg text-center transition-colors shadow-sm">
              <i class="ti ti-user-plus mr-1" aria-hidden="true"></i>
              Nuevo empleado
            </Link>
          </div>
        </div>
      </div>
    </div>

    <section class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-xl text-amber-700">
            <i class="ti ti-clock-exclamation" aria-hidden="true"></i>
          </div>
          <div>
            <h2 class="font-['Sora'] text-base font-bold text-slate-800">Top 3 mas impuntuales</h2>
            <p class="text-xs font-semibold text-slate-500">Sin estudiantes, sin sabados y sin empleados exentos</p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 p-5 sm:p-6 lg:grid-cols-3">
        <article v-for="item in retardosItems" :key="item.key" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div :class="['inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-black uppercase tracking-wide', item.tone]">
              <i :class="['ti', item.icon]" aria-hidden="true"></i>
              {{ item.title }}
            </div>
            <span class="text-[11px] font-bold text-slate-400">{{ item.data.inicio }} - {{ item.data.fin }}</span>
          </div>

          <div v-if="item.data.ranking.length" class="space-y-2">
            <div v-for="(empleado, index) in item.data.ranking" :key="empleado.empleado_id" class="rounded-xl border border-amber-100 bg-amber-50/45 px-3 py-3">
              <div class="flex items-start gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-black text-amber-700 shadow-sm">{{ index + 1 }}</span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-black text-slate-900">#{{ empleado.numero_empleado || 'S/N' }} - {{ empleado.nombre_completo }}</p>
                  <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ empleado.dias }} dia(s) con retardo</p>
                </div>
                <div class="shrink-0 text-right">
                  <p class="text-sm font-black text-rose-700">{{ formatoMinutos(empleado.minutos) }}</p>
                  <p class="text-[10px] font-bold uppercase text-slate-400">Total tarde</p>
                </div>
              </div>
              <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                <div class="rounded-lg bg-white px-2 py-2">
                  <p class="text-[10px] font-bold uppercase text-slate-400">Mayor retardo</p>
                  <p class="text-sm font-black text-amber-700">{{ formatoMinutos(empleado.peor_retardo) }}</p>
                </div>
                <div class="rounded-lg bg-white px-2 py-2">
                  <p class="text-[10px] font-bold uppercase text-slate-400">Fecha</p>
                  <p class="truncate text-sm font-black text-slate-800">{{ empleado.fecha_peor_retardo || 'S/F' }}</p>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="flex min-h-28 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 text-center">
            <i class="ti ti-circle-check text-2xl text-emerald-500" aria-hidden="true"></i>
            <p class="mt-2 text-sm font-bold text-slate-600">Sin retardos</p>
          </div>
        </article>
      </div>
    </section>

    <section class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-xl text-emerald-700">
            <i class="ti ti-clock-check" aria-hidden="true"></i>
          </div>
          <div>
            <h2 class="font-['Sora'] text-base font-bold text-slate-800">Top 3 mas puntuales</h2>
            <p class="text-xs font-semibold text-slate-500">A las 08:00 o antes, sin estudiantes, sin sabados y sin exentos</p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 p-5 sm:p-6 lg:grid-cols-3">
        <article v-for="item in tempranoItems" :key="item.key" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div :class="['inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-black uppercase tracking-wide', item.tone]">
              <i :class="['ti', item.icon]" aria-hidden="true"></i>
              {{ item.title }}
            </div>
            <span class="text-[11px] font-bold text-slate-400">{{ item.data.inicio }} - {{ item.data.fin }}</span>
          </div>

          <div v-if="item.data.ranking.length" class="space-y-2">
            <div v-for="(empleado, index) in item.data.ranking" :key="empleado.empleado_id" class="rounded-xl border border-emerald-100 bg-emerald-50/45 px-3 py-3">
              <div class="flex items-start gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-black text-emerald-700 shadow-sm">{{ index + 1 }}</span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-black text-slate-900">#{{ empleado.numero_empleado || 'S/N' }} - {{ empleado.nombre_completo }}</p>
                  <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ empleado.dias }} dia(s) puntual(es)</p>
                </div>
                <div class="shrink-0 text-right">
                  <p class="text-sm font-black text-emerald-700">{{ formatoMinutos(empleado.minutos) }}</p>
                  <p class="text-[10px] font-bold uppercase text-slate-400">Total antes</p>
                </div>
              </div>
              <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                <div class="rounded-lg bg-white px-2 py-2">
                  <p class="text-[10px] font-bold uppercase text-slate-400">Mayor antic.</p>
                  <p class="text-sm font-black text-teal-700">{{ formatoMinutos(empleado.mayor_anticipacion) }}</p>
                </div>
                <div class="rounded-lg bg-white px-2 py-2">
                  <p class="text-[10px] font-bold uppercase text-slate-400">Fecha</p>
                  <p class="truncate text-sm font-black text-slate-800">{{ empleado.fecha_mayor_anticipacion || 'S/F' }}</p>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="flex min-h-28 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 text-center">
            <i class="ti ti-clock-off text-2xl text-slate-400" aria-hidden="true"></i>
            <p class="mt-2 text-sm font-bold text-slate-600">Sin registros puntuales</p>
          </div>
        </article>
      </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="font-['Sora'] text-sm font-bold text-slate-800 mb-1">Tipos de Asistencia</h2>
        <p class="text-xs text-slate-500 mb-6">Distribución del mes actual</p>
        <div class="flex justify-center"><VueApexCharts width="100%" height="280" :options="donutOptions" :series="graficaAsistencia" /></div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="font-['Sora'] text-sm font-bold text-slate-800 mb-1">Tendencia de Horas Extra</h2>
        <p class="text-xs text-slate-500 mb-4">Histórico general últimos 7 días</p>
        <VueApexCharts width="100%" height="280" :options="barOptions" :series="barSeries" />
      </div>

      <div class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-purple-50 p-5 shadow-sm sm:p-6">
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
