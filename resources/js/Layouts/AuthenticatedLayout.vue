<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth.user)
const can = computed(() => page.props.auth.can ?? {})

// Estados del menú
const isSidebarOpenMobile = ref(false)
const isSidebarCollapsedDesktop = ref(false)

const navItems = [
  {
    label: 'Principal',
    links: [
      { name: 'Panel',        route: 'dashboard',         icon: 'ti-layout-dashboard', permission: 'dashboard.view' },
      { name: 'Empleados',    route: 'empleados.index',   icon: 'ti-users', permission: 'empleados.view' },
      { name: 'Asistencias',  route: 'asistencias.index', icon: 'ti-calendar-check', permission: 'asistencias.view' },
      { name: 'Nóminas',      route: 'nominas.index',     icon: 'ti-report-money' },
    ]
  },
  {
    label: 'Sistema',
    links: [
      { name: 'Configuración', route: 'profile.edit', icon: 'ti-settings' },
      { name: 'Dias festivos', route: 'dias-festivos.index', icon: 'ti-calendar-event' },
      { name: 'Base de datos', route: 'base-datos.index', icon: 'ti-database' },
    ]
  },
  {
    label: 'Seguridad',
    links: [
      { name: 'Usuarios', route: 'seguridad.usuarios.index', icon: 'ti-user-shield' },
      { name: 'Auditoria', route: 'seguridad.auditoria.index', icon: 'ti-clipboard-list' },
    ]
  }
]

const routePermissions = {
  dashboard: 'dashboard.view',
  'empleados.index': 'empleados.view',
  'asistencias.index': 'asistencias.view',
  'nominas.index': 'nominas.view',
  'dias-festivos.index': 'sistema.dias_festivos',
  'base-datos.index': 'sistema.backups',
  'seguridad.usuarios.index': 'sistema.users',
  'seguridad.auditoria.index': 'sistema.audit',
}

const visibleNavItems = computed(() => navItems
  .map(group => ({
    ...group,
    links: group.links.filter(item => {
      const permission = item.permission || routePermissions[item.route]

      return !permission || can.value[permission]
    }),
  }))
  .filter(group => group.links.length > 0)
)

function isActive(routeName) {
  return route().current(routeName) || route().current(routeName + '.*')
}

function toggleSidebar() {
  if (window.innerWidth < 1024) {
    isSidebarOpenMobile.value = !isSidebarOpenMobile.value
  } else {
    isSidebarCollapsedDesktop.value = !isSidebarCollapsedDesktop.value
  }
}
</script>

<template>
  <div class="flex min-h-screen bg-slate-50 font-['DM_Sans'] text-slate-800">
    
    <!-- Overlay Mobile -->
    <div v-if="isSidebarOpenMobile" class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden" @click="isSidebarOpenMobile = false"></div>

    <!-- Sidebar Premium -->
    <aside :class="[
      'fixed inset-y-0 left-0 z-50 flex flex-col bg-white border-r border-slate-200/60 shadow-[4px_0_24px_rgba(0,0,0,0.02)] transition-all duration-300 lg:sticky lg:top-0 lg:h-screen',
      isSidebarCollapsedDesktop && !isSidebarOpenMobile ? 'w-20' : 'w-72',
      isSidebarOpenMobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]">
      <!-- Logo Area -->
      <div class="flex h-20 shrink-0 items-center gap-3 border-b border-slate-100 px-5">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white shadow-md shadow-blue-500/10 border border-slate-100 p-1.5">
          <img :src="'/img/lugarth.png'" alt="LUGARTH" class="h-full w-full object-contain" />
        </div>
        <div v-show="!isSidebarCollapsedDesktop || isSidebarOpenMobile" class="flex flex-col whitespace-nowrap transition-opacity">
          <span class="font-['Sora'] text-sm font-extrabold tracking-wide text-slate-900 leading-tight">PROMATEC</span>
          <span class="text-[10px] font-black tracking-[0.2em] text-blue-600">LUGARTH</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto overflow-x-hidden p-4 custom-scrollbar">
        <template v-for="group in visibleNavItems" :key="group.label">
          <p v-show="!isSidebarCollapsedDesktop || isSidebarOpenMobile" class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ group.label }}</p>
          <div v-show="isSidebarCollapsedDesktop && !isSidebarOpenMobile" class="my-4 h-px w-full bg-slate-100"></div>

          <Link v-for="item in group.links" :key="item.route" :href="route(item.route)" :title="isSidebarCollapsedDesktop && !isSidebarOpenMobile ? item.name : ''"
            :class="[
              'group flex items-center gap-3 rounded-2xl px-3 py-3 mb-1.5 transition-all duration-300',
              isActive(item.route) 
                ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-lg shadow-blue-500/25 font-bold' 
                : 'text-slate-500 hover:bg-blue-50/50 hover:text-blue-700 font-semibold'
            ]">
            <i :class="['ti text-xl transition-transform group-hover:scale-110 shrink-0', item.icon, isActive(item.route) ? 'text-white' : '']"></i>
            <span v-show="!isSidebarCollapsedDesktop || isSidebarOpenMobile" class="whitespace-nowrap text-sm">{{ item.name }}</span>
          </Link>
        </template>
      </nav>
    </aside>

    <!-- Main Wrapper -->
    <div class="flex min-w-0 flex-1 flex-col">
      <!-- Topbar Glassmorphism -->
      <header class="sticky top-0 z-30 flex h-20 shrink-0 items-center justify-between border-b border-slate-200/70 bg-white/90 px-4 shadow-sm shadow-slate-200/40 backdrop-blur-lg sm:px-8">
        <div class="flex items-center gap-4">
          <button @click="toggleSidebar" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition-all hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-500/10">
            <i class="ti ti-menu-2 text-xl"></i>
          </button>
          <div class="hidden items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 lg:flex">
            <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span> Sistema en línea
          </div>
        </div>

        <div class="flex items-center gap-4 sm:gap-6">
          <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
              <p class="text-sm font-bold text-slate-900">{{ user?.name }}</p>
              <p class="text-[11px] font-semibold text-slate-400">{{ user?.role_label || user?.email }}</p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 text-sm font-black text-white shadow-md shadow-indigo-500/20">
              {{ user?.name?.charAt(0)?.toUpperCase() ?? 'U' }}
            </div>
          </div>
          <div class="h-8 w-px bg-slate-200"></div>
          <Link :href="route('logout')" method="post" as="button" class="group flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-slate-500 transition-all hover:bg-rose-50 hover:text-rose-600">
            <i class="ti ti-logout text-xl transition-transform group-hover:translate-x-1"></i>
            <span class="hidden sm:inline">Salir</span>
          </Link>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-x-hidden p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-[1500px]">
          <section v-if="$slots.header" class="mb-6 rounded-3xl border border-slate-200/60 bg-white p-6 shadow-sm sm:mb-8 sm:p-8">
            <slot name="header" />
          </section>
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
