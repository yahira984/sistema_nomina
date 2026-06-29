<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  users: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
  permissions: { type: Array, default: () => [] },
  roleDefaults: { type: Object, default: () => ({}) },
})

const page = usePage()
const processingId = ref(null)
const deletingId = ref(null)
const forms = reactive({})

const authUser = computed(() => page.props.auth?.user ?? {})
const flashSuccess = computed(() => page.props.flash?.success)
const generalError = computed(() => page.props.errors?.user)
const pendingCount = computed(() => props.users.filter(user => user.status === 'pending').length)
const activeCount = computed(() => props.users.filter(user => user.status === 'approved').length)

const syncForms = () => {
  props.users.forEach((user) => {
    forms[user.id] = {
      role: user.role || 'consulta',
      permissions: [...(user.custom_permissions?.length ? user.custom_permissions : user.permissions || [])],
      approved: user.status !== 'pending',
      disabled: user.status === 'disabled',
    }

    if (user.is_recovery_admin) {
      forms[user.id].role = 'admin'
      forms[user.id].approved = true
      forms[user.id].disabled = false
      forms[user.id].permissions = []
    }
  })
}

syncForms()
watch(() => props.users, syncForms, { deep: true })

const statusClass = (status) => ({
  pending: 'border-amber-200 bg-amber-50 text-amber-700',
  approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
  disabled: 'border-rose-200 bg-rose-50 text-rose-700',
}[status] || 'border-slate-200 bg-slate-50 text-slate-600')

const statusLabel = (status) => ({
  pending: 'Pendiente',
  approved: 'Activo',
  disabled: 'Deshabilitado',
}[status] || 'Sin estado')

const setRoleDefaults = (userId) => {
  const role = forms[userId]?.role
  forms[userId].permissions = [...(props.roleDefaults[role] || [])]
}

const togglePermission = (userId, permission, checked) => {
  const current = new Set(forms[userId].permissions || [])

  if (checked) {
    current.add(permission)
  } else {
    current.delete(permission)
  }

  forms[userId].permissions = [...current]
}

const saveUser = (user) => {
  if (user.is_recovery_admin) {
    forms[user.id].role = 'admin'
    forms[user.id].approved = true
    forms[user.id].disabled = false
    forms[user.id].permissions = []
  }

  processingId.value = user.id
  router.put(route('seguridad.usuarios.update', user.id), forms[user.id], {
    preserveScroll: true,
    onFinish: () => {
      processingId.value = null
    },
  })
}

const canDeleteUser = (user) => {
  return authUser.value?.is_admin && !user.is_recovery_admin && Number(authUser.value?.id) !== Number(user.id)
}

const deleteUser = (user) => {
  if (!canDeleteUser(user)) return
  if (!confirm(`¿Borrar al usuario ${user.name}? Esta accion no se puede deshacer.`)) return

  deletingId.value = user.id
  router.delete(route('seguridad.usuarios.destroy', user.id), {
    preserveScroll: true,
    onFinish: () => {
      deletingId.value = null
    },
  })
}
</script>

<template>
  <Head title="Usuarios y Permisos" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <Link :href="route('dashboard')" class="icon-button" aria-label="Volver al panel">
          <i class="ti ti-arrow-left" aria-hidden="true"></i>
        </Link>
        <div class="min-w-0">
          <p class="page-kicker">
            <i class="ti ti-user-shield" aria-hidden="true"></i>
            Seguridad
          </p>
          <h2 class="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">Usuarios y permisos</h2>
        </div>
      </div>
    </template>

    <div class="page-shell">
      <div class="content-wrap space-y-6">
        <div v-if="flashSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700">
          <i class="ti ti-circle-check mr-2" aria-hidden="true"></i>
          {{ flashSuccess }}
        </div>
        <div v-if="generalError" class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
          <i class="ti ti-alert-triangle mr-2" aria-hidden="true"></i>
          {{ generalError }}
        </div>

        <section class="grid gap-4 md:grid-cols-3">
          <div class="metric-card">
            <p class="metric-label">Pendientes</p>
            <p class="metric-value text-2xl">{{ pendingCount }}</p>
          </div>
          <div class="metric-card">
            <p class="metric-label">Activos</p>
            <p class="metric-value text-2xl">{{ activeCount }}</p>
          </div>
          <div class="metric-card">
            <p class="metric-label">Total</p>
            <p class="metric-value text-2xl">{{ users.length }}</p>
          </div>
        </section>

        <section class="app-panel">
          <div class="panel-header">
            <div>
              <h3 class="panel-title">Control de acceso</h3>
              <p class="panel-subtitle">Aprueba cuentas nuevas, deshabilita accesos y ajusta permisos.</p>
            </div>
          </div>

          <div class="divide-y divide-slate-100">
            <article v-for="user in users" :key="user.id" class="p-5 sm:p-6">
              <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="min-w-0 space-y-4">
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-2">
                        <h4 class="truncate text-base font-black text-slate-950">{{ user.name }}</h4>
                        <span :class="['status-pill', statusClass(user.status)]">{{ statusLabel(user.status) }}</span>
                        <span v-if="user.is_recovery_admin" class="status-pill border-blue-200 bg-blue-50 text-blue-700">Recuperacion</span>
                      </div>
                      <p class="mt-1 break-words text-sm font-semibold text-slate-500">{{ user.display_email }}</p>
                      <p class="mt-1 text-xs font-bold text-slate-400">
                        Ultimo acceso: {{ user.last_login_at || 'Sin registro' }}
                        <span v-if="user.last_login_ip"> · {{ user.last_login_ip }}</span>
                      </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                      <button type="button" class="btn-accent w-full sm:w-auto" :disabled="processingId === user.id" @click="saveUser(user)">
                        <i class="ti ti-device-floppy" aria-hidden="true"></i>
                        {{ processingId === user.id ? 'Guardando...' : 'Guardar' }}
                      </button>
                      <button v-if="canDeleteUser(user)" type="button" class="btn-danger w-full sm:w-auto" :disabled="deletingId === user.id" @click="deleteUser(user)">
                        <i class="ti ti-trash" aria-hidden="true"></i>
                        {{ deletingId === user.id ? 'Borrando...' : 'Borrar' }}
                      </button>
                    </div>
                  </div>

                  <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                      <label class="field-label">Rol</label>
                      <select v-model="forms[user.id].role" class="field-input-soft" :disabled="user.is_recovery_admin" @change="setRoleDefaults(user.id)">
                        <option v-for="role in roles" :key="role.key" :value="role.key">{{ role.label }}</option>
                      </select>
                    </div>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700">
                      <input v-model="forms[user.id].approved" type="checkbox" :disabled="user.is_recovery_admin" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                      Aprobado
                    </label>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700">
                      <input v-model="forms[user.id].disabled" type="checkbox" :disabled="user.is_recovery_admin" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500" />
                      Deshabilitado
                    </label>
                  </div>

                  <details class="rounded-2xl border border-slate-200 bg-slate-50">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-black text-slate-800">
                      Permisos asignados
                    </summary>
                    <div class="grid gap-4 border-t border-slate-200 bg-white p-4 lg:grid-cols-2">
                      <div v-for="group in permissions" :key="group.group" class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="mb-3 text-xs font-black uppercase tracking-wider text-slate-500">{{ group.group }}</p>
                        <div class="space-y-2">
                          <label v-for="permission in group.items" :key="permission.key" class="flex items-start gap-3 text-sm font-semibold text-slate-700">
                            <input
                              type="checkbox"
                              class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                              :checked="forms[user.id].permissions?.includes(permission.key)"
                              :disabled="forms[user.id].role === 'admin' || user.is_recovery_admin"
                              @change="togglePermission(user.id, permission.key, $event.target.checked)"
                            />
                            <span>{{ permission.label }}</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </details>
                </div>

                <aside class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                  <p class="text-xs font-black uppercase tracking-wider text-slate-400">Resumen</p>
                  <dl class="mt-4 space-y-3 text-sm">
                    <div>
                      <dt class="font-bold text-slate-500">Creado</dt>
                      <dd class="font-black text-slate-900">{{ user.created_at || '-' }}</dd>
                    </div>
                    <div>
                      <dt class="font-bold text-slate-500">Aprobado</dt>
                      <dd class="font-black text-slate-900">{{ user.approved_at || '-' }}</dd>
                    </div>
                    <div>
                      <dt class="font-bold text-slate-500">Permisos efectivos</dt>
                      <dd class="font-black text-slate-900">{{ user.permissions?.length || 0 }}</dd>
                    </div>
                  </dl>
                </aside>
              </div>
            </article>

            <div v-if="users.length === 0" class="empty-state">
              No hay usuarios registrados.
            </div>
          </div>
        </section>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
