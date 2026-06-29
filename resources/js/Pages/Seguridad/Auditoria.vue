<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  logs: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  users: { type: Array, default: () => [] },
  events: { type: Array, default: () => [] },
  canDeleteAudit: { type: Boolean, default: false },
})

const page = usePage()
const deletingId = ref(null)
const purging = ref(false)
const flashSuccess = computed(() => page.props.flash?.success)

const form = useForm({
  user_id: props.filters.user_id || '',
  event: props.filters.event || '',
  search: props.filters.search || '',
  from: props.filters.from || '',
  to: props.filters.to || '',
})

const filterPayload = () => ({
  user_id: form.user_id || undefined,
  event: form.event || undefined,
  search: form.search || undefined,
  from: form.from || undefined,
  to: form.to || undefined,
})

const hasFilters = computed(() => Object.values(filterPayload()).some(Boolean))

const applyFilters = () => {
  router.get(route('seguridad.auditoria.index'), filterPayload(), {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  form.reset()
  router.get(route('seguridad.auditoria.index'))
}

const deleteLog = (log) => {
  if (!props.canDeleteAudit) return
  if (!confirm(`¿Borrar este registro de auditoria?\n\n${log.summary}`)) return

  deletingId.value = log.id
  router.delete(route('seguridad.auditoria.destroy', log.id), {
    preserveScroll: true,
    onFinish: () => {
      deletingId.value = null
    },
  })
}

const purgeLogs = () => {
  if (!props.canDeleteAudit) return

  const scope = hasFilters.value ? 'los registros filtrados' : 'todos los registros de auditoria'
  if (!confirm(`¿Borrar ${scope}? Esta accion no se puede deshacer.`)) return

  purging.value = true
  router.delete(route('seguridad.auditoria.purge'), {
    data: filterPayload(),
    preserveScroll: true,
    onFinish: () => {
      purging.value = false
    },
  })
}

const jsonPreview = (value) => {
  if (!value || (Array.isArray(value) && value.length === 0)) {
    return ''
  }

  return JSON.stringify(value, null, 2)
}

const areaIcon = (area) => ({
  Acceso: 'ti-login-2',
  Empleados: 'ti-users',
  Asistencias: 'ti-calendar-check',
  Nominas: 'ti-report-money',
  Seguridad: 'ti-user-shield',
  Auditoria: 'ti-clipboard-list',
  'Base de datos': 'ti-database',
}[area] || 'ti-activity')
</script>

<template>
  <Head title="Auditoria" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <Link :href="route('dashboard')" class="icon-button" aria-label="Volver al panel">
          <i class="ti ti-arrow-left" aria-hidden="true"></i>
        </Link>
        <div class="min-w-0">
          <p class="page-kicker">
            <i class="ti ti-clipboard-list" aria-hidden="true"></i>
            Seguridad
          </p>
          <h2 class="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">Bitacora de auditoria</h2>
          <p class="mt-1 text-sm font-semibold text-slate-500">Actividad del sistema explicada en lenguaje simple.</p>
        </div>
      </div>
    </template>

    <div class="page-shell">
      <div class="content-wrap space-y-6">
        <div v-if="flashSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700">
          <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
          {{ flashSuccess }}
        </div>

        <section class="app-panel">
          <form class="grid gap-4 p-5 sm:p-6 lg:grid-cols-6 lg:items-end" @submit.prevent="applyFilters">
            <div class="lg:col-span-2">
              <label class="field-label">Buscar</label>
              <input v-model="form.search" type="text" class="field-input-soft" placeholder="Nombre, modulo, ruta o descripcion" />
            </div>
            <div>
              <label class="field-label">Usuario</label>
              <select v-model="form.user_id" class="field-input-soft">
                <option value="">Todos</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
              </select>
            </div>
            <div>
              <label class="field-label">Accion</label>
              <select v-model="form.event" class="field-input-soft">
                <option value="">Todas</option>
                <option v-for="event in events" :key="event.key" :value="event.key">{{ event.label }}</option>
              </select>
            </div>
            <div>
              <label class="field-label">Desde</label>
              <input v-model="form.from" type="date" class="field-input-soft" />
            </div>
            <div>
              <label class="field-label">Hasta</label>
              <input v-model="form.to" type="date" class="field-input-soft" />
            </div>
            <div class="flex flex-wrap gap-2 lg:col-span-6">
              <button type="submit" class="btn-accent">
                <i class="ti ti-filter" aria-hidden="true"></i>
                Filtrar
              </button>
              <button type="button" class="btn-secondary" @click="clearFilters">
                Limpiar filtros
              </button>
              <button v-if="canDeleteAudit" type="button" class="btn-danger" :disabled="purging" @click="purgeLogs">
                <i class="ti ti-trash" aria-hidden="true"></i>
                {{ purging ? 'Borrando...' : (hasFilters ? 'Borrar filtrados' : 'Borrar todo') }}
              </button>
            </div>
          </form>
        </section>

        <section class="app-panel">
          <div class="panel-header">
            <div>
              <h3 class="panel-title">Actividad reciente</h3>
              <p class="panel-subtitle">{{ logs.total || 0 }} registro(s) encontrados.</p>
            </div>
          </div>

          <div class="divide-y divide-slate-100">
            <article v-for="log in logs.data" :key="log.id" class="p-5 sm:p-6">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-2xl text-blue-700">
                  <i :class="['ti', areaIcon(log.area)]" aria-hidden="true"></i>
                </div>

                <div class="min-w-0 flex-1">
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-2">
                        <span class="status-pill border-blue-200 bg-blue-50 text-blue-700">{{ log.area }}</span>
                        <span class="status-pill border-slate-200 bg-slate-50 text-slate-700">{{ log.action }}</span>
                      </div>
                      <h4 class="mt-3 text-base font-black text-slate-950">{{ log.summary }}</h4>
                      <p class="mt-1 text-sm font-semibold text-slate-500">
                        {{ log.created_at_human }} · {{ log.user?.name || 'Sistema' }}
                        <span v-if="log.user?.display_email"> · {{ log.user.display_email }}</span>
                      </p>
                    </div>

                    <button v-if="canDeleteAudit" type="button" class="btn-danger shrink-0" :disabled="deletingId === log.id" @click="deleteLog(log)">
                      <i class="ti ti-trash" aria-hidden="true"></i>
                      {{ deletingId === log.id ? 'Borrando...' : 'Borrar' }}
                    </button>
                  </div>

                  <div class="mt-4 grid gap-3 text-sm md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                      <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Registro</p>
                      <p class="mt-1 font-black text-slate-900">{{ log.auditable_label }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                      <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">IP</p>
                      <p class="mt-1 font-black text-slate-900">{{ log.ip_address || 'Sin dato' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                      <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Ruta</p>
                      <p class="mt-1 truncate font-black text-slate-900">{{ log.route || log.method || 'Sistema' }}</p>
                    </div>
                  </div>

                  <div v-if="log.changes.length" class="mt-4 rounded-2xl border border-amber-100 bg-amber-50 p-4">
                    <p class="mb-3 text-xs font-black uppercase tracking-wider text-amber-700">Cambios detectados</p>
                    <div class="grid gap-2">
                      <div v-for="change in log.changes" :key="`${log.id}-${change.field}`" class="rounded-xl border border-white bg-white p-3">
                        <p class="text-xs font-black uppercase tracking-wider text-slate-400">{{ change.field }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">
                          <span class="text-rose-700">{{ change.before }}</span>
                          <i class="ti ti-arrow-right mx-2 text-slate-400" aria-hidden="true"></i>
                          <span class="text-emerald-700">{{ change.after }}</span>
                        </p>
                      </div>
                    </div>
                  </div>

                  <details class="mt-4 rounded-2xl border border-slate-200 bg-slate-50">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-black text-slate-700">
                      Detalle tecnico
                    </summary>
                    <div class="space-y-3 border-t border-slate-200 bg-white p-4">
                      <div class="grid gap-3 text-xs font-bold text-slate-500 sm:grid-cols-2">
                        <p>Evento: <span class="text-slate-900">{{ log.event }}</span></p>
                        <p>ID auditoria: <span class="text-slate-900">#{{ log.id }}</span></p>
                      </div>
                      <div v-if="jsonPreview(log.old_values)">
                        <p class="mb-1 text-[10px] font-black uppercase tracking-wider text-slate-400">Antes</p>
                        <pre class="max-h-48 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{{ jsonPreview(log.old_values) }}</pre>
                      </div>
                      <div v-if="jsonPreview(log.new_values)">
                        <p class="mb-1 text-[10px] font-black uppercase tracking-wider text-slate-400">Despues</p>
                        <pre class="max-h-48 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{{ jsonPreview(log.new_values) }}</pre>
                      </div>
                      <div v-if="jsonPreview(log.metadata)">
                        <p class="mb-1 text-[10px] font-black uppercase tracking-wider text-slate-400">Metadata</p>
                        <pre class="max-h-48 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100">{{ jsonPreview(log.metadata) }}</pre>
                      </div>
                      <a v-if="log.url" :href="log.url" target="_blank" class="inline-flex text-xs font-bold text-blue-700">
                        Abrir URL registrada
                      </a>
                    </div>
                  </details>
                </div>
              </div>
            </article>

            <div v-if="logs.data.length === 0" class="empty-state">
              No hay registros de auditoria con estos filtros.
            </div>
          </div>

          <div v-if="logs.links?.length > 3" class="flex flex-wrap gap-2 border-t border-slate-100 p-4">
            <Link
              v-for="link in logs.links"
              :key="link.label"
              :href="link.url || '#'"
              :class="[
                'rounded-lg border px-3 py-2 text-xs font-black',
                link.active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600',
                !link.url ? 'pointer-events-none opacity-50' : '',
              ]"
              v-html="link.label"
            />
          </div>
        </section>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
