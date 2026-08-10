<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import OperationCenter from '@/Components/OperationCenter.vue'
import AppBreadcrumbs from '@/Components/AppBreadcrumbs.vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const can = computed(() => page.props.auth?.can ?? {})
const preferences = computed(() => user.value?.preferences ?? {})
const systemContext = computed(() => page.props.systemContext ?? {})
const flash = computed(() => page.props.flash ?? {})

const isSidebarOpenMobile = ref(false)
const isSidebarCollapsedDesktop = ref(Boolean(preferences.value.sidebar_collapsed))
const openGroups = ref(new Set(['Principal', 'Operación', 'Sistema', 'Seguridad']))
const globalSearch = ref('')
const searchFocused = ref(false)
const showNotifications = ref(false)
const showPreferences = ref(false)
const showUserMenu = ref(false)
const online = ref(typeof navigator === 'undefined' ? true : navigator.onLine)
const toastVisible = ref(false)
let toastTimer = null
let sessionTimer = null
let heartbeatTimer = null
let lastActivityAt = Date.now()
const sessionLifetimeMs = 60 * 60 * 1000
const heartbeatEveryMs = 5 * 60 * 1000
const activityEvents = ['pointerdown', 'keydown', 'touchstart', 'scroll']
const activityStorageKey = 'promatec-nominas-last-activity'

const navItems = [
    {
        label: 'Principal',
        icon: 'ti-home',
        links: [
            { name: 'Panel', route: 'dashboard', icon: 'ti-layout-dashboard', permission: 'dashboard.view' },
            { name: 'Empleados', route: 'empleados.index', icon: 'ti-users', permission: 'empleados.view' },
        ],
    },
    {
        label: 'Operación',
        icon: 'ti-briefcase',
        links: [
            { name: 'Asistencias', route: 'asistencias.index', icon: 'ti-calendar-check', permission: 'asistencias.view' },
            { name: 'Nóminas', route: 'nominas.index', icon: 'ti-report-money', permission: 'nominas.view' },
        ],
    },
    {
        label: 'Sistema',
        icon: 'ti-settings',
        links: [
            { name: 'Reglas laborales', route: 'reglas-laborales.index', icon: 'ti-adjustments', permission: 'sistema.rules' },
            { name: 'Días festivos', route: 'dias-festivos.index', icon: 'ti-calendar-event', permission: 'sistema.dias_festivos' },
            { name: 'Base de datos', route: 'base-datos.index', icon: 'ti-database', permission: 'sistema.backups' },
            { name: 'Salud del sistema', route: 'sistema.salud', icon: 'ti-heart-rate-monitor', permission: 'sistema.health' },
            { name: 'Preferencias', route: 'profile.edit', icon: 'ti-user-cog' },
        ],
    },
    {
        label: 'Seguridad',
        icon: 'ti-shield-lock',
        links: [
            { name: 'Usuarios', route: 'seguridad.usuarios.index', icon: 'ti-user-shield', permission: 'sistema.users' },
            { name: 'Auditoría', route: 'seguridad.auditoria.index', icon: 'ti-clipboard-list', permission: 'sistema.audit' },
        ],
    },
]

const visibleNavItems = computed(() => navItems
    .map(group => ({
        ...group,
        links: group.links.filter(item => !item.permission || can.value[item.permission]),
    }))
    .filter(group => group.links.length > 0))

const notificationCount = computed(() => {
    const notifications = systemContext.value.notifications || {}
    return Number(notifications.failed_operations || 0) + Number(notifications.integration_failures || 0)
})

const selectedPeriod = computed(() => {
    const value = systemContext.value.selected_period
    if (!value) return 'Periodo actual'

    const date = new Date(`${String(value).substring(0, 10)}T12:00:00`)
    return Number.isNaN(date.getTime())
        ? String(value)
        : new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }).format(date)
})

const isActive = routeName => route().current(routeName) || route().current(`${routeName}.*`)
const groupIsOpen = label => openGroups.value.has(label)
const flatNavItems = computed(() => visibleNavItems.value.flatMap(group => group.links.map(link => ({ ...link, group: group.label }))))
const activeNavItem = computed(() => flatNavItems.value.find(item => isActive(item.route)))
const breadcrumbs = computed(() => activeNavItem.value ? [{ label: activeNavItem.value.group }, { label: activeNavItem.value.name }] : [])
const normalizedSearch = computed(() => globalSearch.value.trim().toLocaleLowerCase('es-MX'))
const moduleMatches = computed(() => normalizedSearch.value
    ? flatNavItems.value.filter(item => `${item.name} ${item.group}`.toLocaleLowerCase('es-MX').includes(normalizedSearch.value)).slice(0, 5)
    : [])
const quickAccess = computed(() => {
    const selected = Array.isArray(preferences.value.quick_access) ? preferences.value.quick_access : ['asistencias.index', 'nominas.index', 'empleados.index']
    return selected.map(routeName => flatNavItems.value.find(item => item.route === routeName)).filter(Boolean).slice(0, 5)
})

const toggleGroup = label => {
    const next = new Set(openGroups.value)
    next.has(label) ? next.delete(label) : next.add(label)
    openGroups.value = next
}

const toggleSidebar = () => {
    if (window.innerWidth < 1024) {
        isSidebarOpenMobile.value = !isSidebarOpenMobile.value
        return
    }

    isSidebarCollapsedDesktop.value = !isSidebarCollapsedDesktop.value
    persistPreferences({ sidebar_collapsed: isSidebarCollapsedDesktop.value })
}

const applyPreferences = () => {
    const root = document.documentElement
    const theme = preferences.value.theme || localStorage.getItem('app-theme') || 'system'
    const dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)
    root.classList.toggle('dark', dark)
    root.classList.toggle('density-compact', (preferences.value.density || 'comfortable') === 'compact')
    localStorage.setItem('app-theme', theme)
}

const persistPreferences = values => {
    router.patch(route('preferencias.update'), values, {
        preserveScroll: true,
        preserveState: true,
        only: ['auth', 'flash'],
    })
}

const setTheme = theme => {
    user.value.preferences.theme = theme
    applyPreferences()
    persistPreferences({ theme })
}

const setDensity = density => {
    user.value.preferences.density = density
    applyPreferences()
    persistPreferences({ density })
}

const searchEmployees = () => {
    const search = globalSearch.value.trim()
    if (!search) return
    router.get(route('empleados.index'), { search, status: 'todos' })
    globalSearch.value = ''
}

const openModule = item => {
    globalSearch.value = ''
    searchFocused.value = false
    router.visit(route(item.route))
}


const showToast = () => {
    clearTimeout(toastTimer)
    toastVisible.value = Boolean(flash.value.success || flash.value.error)
    if (toastVisible.value) toastTimer = setTimeout(() => { toastVisible.value = false }, 4200)
}

const updateOnline = () => { online.value = navigator.onLine }

const readSharedActivity = () => {
    const stored = Number(window.localStorage.getItem(activityStorageKey) || 0)
    return Number.isFinite(stored) ? stored : 0
}

const scheduleSessionExpiration = () => {
    window.clearTimeout(sessionTimer)
    const elapsed = Date.now() - Math.max(lastActivityAt, readSharedActivity())
    const remaining = Math.max(1000, sessionLifetimeMs - elapsed)
    sessionTimer = window.setTimeout(confirmSessionExpiration, remaining)
}

const registerActivity = () => {
    lastActivityAt = Date.now()
    window.localStorage.setItem(activityStorageKey, String(lastActivityAt))
    scheduleSessionExpiration()
}

const syncActivity = event => {
    if (event.key !== activityStorageKey) return
    lastActivityAt = Math.max(lastActivityAt, Number(event.newValue || 0))
    scheduleSessionExpiration()
}

const confirmSessionExpiration = async () => {
    const sharedActivityAt = Math.max(lastActivityAt, readSharedActivity())
    if (Date.now() - sharedActivityAt < sessionLifetimeMs) {
        scheduleSessionExpiration()
        return
    }

    try {
        await window.axios.get(route('session.keep-alive'), {
            headers: { 'X-Session-Check': '1' },
        })
        lastActivityAt = Date.now()
        window.localStorage.setItem(activityStorageKey, String(lastActivityAt))
        scheduleSessionExpiration()
    } catch (error) {
        // El interceptor global redirige solo cuando Laravel confirma que la sesión terminó.
    }
}

const handleVisibility = () => {
    if (document.visibilityState !== 'visible') return
    registerActivity()
    keepSessionAlive()
}

const keepSessionAlive = async () => {
    if (Date.now() - lastActivityAt > heartbeatEveryMs + 30000) return

    try {
        await window.axios.get(route('session.keep-alive'), {
            headers: { 'X-Session-Heartbeat': '1' },
        })
    } catch (error) {
        // El interceptor global gestiona sesiones vencidas; los fallos de red no cierran la sesión.
    }
}

watch(preferences, () => nextTick(applyPreferences), { deep: true })
watch(flash, showToast, { deep: true, immediate: true })

onMounted(() => {
    applyPreferences()
    const active = visibleNavItems.value.find(group => group.links.some(item => isActive(item.route)))
    openGroups.value = new Set(active ? [active.label] : ['Principal'])
    window.addEventListener('online', updateOnline)
    window.addEventListener('offline', updateOnline)
    window.addEventListener('storage', syncActivity)
    document.addEventListener('visibilitychange', handleVisibility)
    activityEvents.forEach(event => window.addEventListener(event, registerActivity, { passive: true }))
    registerActivity()
    heartbeatTimer = window.setInterval(keepSessionAlive, heartbeatEveryMs)
})

onBeforeUnmount(() => {
    window.removeEventListener('online', updateOnline)
    window.removeEventListener('offline', updateOnline)
    window.removeEventListener('storage', syncActivity)
    document.removeEventListener('visibilitychange', handleVisibility)
    activityEvents.forEach(event => window.removeEventListener(event, registerActivity))
    window.clearTimeout(sessionTimer)
    window.clearInterval(heartbeatTimer)
    clearTimeout(toastTimer)
})
</script>

<template>
    <div class="app-frame">
        <div
            v-if="isSidebarOpenMobile"
            class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"
            @click="isSidebarOpenMobile = false"
        ></div>

        <aside
            :class="[
                'app-sidebar',
                isSidebarCollapsedDesktop && !isSidebarOpenMobile ? 'lg:w-[76px]' : 'lg:w-[248px]',
                isSidebarOpenMobile ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-4 dark:border-slate-800">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900">
                    <img :src="'/img/lugarth.png'" alt="LUGARTH" class="h-full w-full object-contain" />
                </div>
                <div v-if="!isSidebarCollapsedDesktop || isSidebarOpenMobile" class="min-w-0">
                    <p class="truncate text-sm font-extrabold text-slate-950 dark:text-white">PROMATEC</p>
                    <p class="text-[10px] font-bold uppercase text-blue-700 dark:text-blue-300">LUGARTH</p>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto p-2.5" aria-label="Navegación principal">
                <section v-for="group in visibleNavItems" :key="group.label" class="mb-2">
                    <button
                        v-if="!isSidebarCollapsedDesktop || isSidebarOpenMobile"
                        type="button"
                        class="flex w-full items-center justify-between rounded-md px-2.5 py-2 text-xs font-bold uppercase text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                        :aria-expanded="groupIsOpen(group.label)"
                        @click="toggleGroup(group.label)"
                    >
                        <span class="flex items-center gap-2">
                            <i :class="['ti text-base', group.icon]" aria-hidden="true"></i>
                            {{ group.label }}
                        </span>
                        <i :class="['ti text-sm', groupIsOpen(group.label) ? 'ti-chevron-up' : 'ti-chevron-down']" aria-hidden="true"></i>
                    </button>

                    <div v-show="groupIsOpen(group.label) || isSidebarCollapsedDesktop" class="mt-1 space-y-1">
                        <Link
                            v-for="item in group.links"
                            :key="item.route"
                            :href="route(item.route)"
                            :title="isSidebarCollapsedDesktop && !isSidebarOpenMobile ? item.name : ''"
                            :class="['nav-link', isActive(item.route) ? 'nav-link-active' : '']"
                            @click="isSidebarOpenMobile = false"
                        >
                            <i :class="['ti shrink-0 text-lg', item.icon]" aria-hidden="true"></i>
                            <span v-if="!isSidebarCollapsedDesktop || isSidebarOpenMobile" class="truncate">{{ item.name }}</span>
                        </Link>
                    </div>
                </section>
            </nav>

            <div class="border-t border-slate-200 p-3 dark:border-slate-800">
                <div v-if="!isSidebarCollapsedDesktop || isSidebarOpenMobile" class="flex items-center justify-between gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                    <span class="truncate">{{ user?.role_label }}</span>
                    <span class="shrink-0 font-mono text-[10px]">v{{ page.props.appVersion }}</span>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="app-topbar">
                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" class="topbar-icon" title="Contraer menú" aria-label="Contraer o abrir menú" @click="toggleSidebar">
                        <i class="ti ti-menu-2 text-lg" aria-hidden="true"></i>
                    </button>

                    <div class="hidden min-w-0 items-center gap-2 border-l border-slate-200 pl-3 md:flex dark:border-slate-700">
                        <i class="ti ti-calendar-week text-blue-700 dark:text-blue-300" aria-hidden="true"></i>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase text-slate-400">Periodo</p>
                            <p class="truncate text-xs font-bold text-slate-800 dark:text-slate-100">{{ selectedPeriod }}</p>
                        </div>
                    </div>
                </div>

                <form class="relative mx-3 hidden max-w-md flex-1 lg:block" role="search" @submit.prevent="searchEmployees">
                    <label class="relative block">
                        <span class="sr-only">Buscar empleado</span>
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                        <input
                            v-model="globalSearch"
                            type="search"
                            class="field-input h-10 py-2 pl-9"
                            aria-label="Buscar módulos o empleados"
                            @focus="searchFocused = true"
                            @blur="setTimeout(() => searchFocused = false, 180)"
                            @keydown.esc="globalSearch = ''; searchFocused = false"
                            placeholder="Buscar por nombre, número o puesto"
                        />
                    </label>
                    <div v-if="searchFocused && normalizedSearch" class="global-search-results">
                        <p class="search-result-heading">Módulos</p>
                        <button v-for="item in moduleMatches" :key="item.route" type="button" class="search-result-row" @mousedown.prevent="openModule(item)">
                            <i :class="['ti text-lg text-blue-700 dark:text-blue-300', item.icon]"></i>
                            <span><strong>{{ item.name }}</strong><small>{{ item.group }}</small></span>
                            <kbd>Enter</kbd>
                        </button>
                        <button type="submit" class="search-result-row border-t border-slate-100 dark:border-slate-800">
                            <i class="ti ti-user-search text-lg text-teal-700"></i>
                            <span><strong>Buscar “{{ globalSearch }}”</strong><small>En empleados</small></span>
                        </button>
                    </div>
                </form>

                <div class="flex items-center gap-1.5">
                    <span
                        :class="[
                            'hidden items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-bold sm:flex',
                            online
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                                : 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300',
                        ]"
                    >
                        <span :class="['h-2 w-2 rounded-full', online ? 'bg-emerald-500' : 'bg-rose-500']"></span>
                        {{ online ? 'En línea' : 'Sin conexión' }}
                    </span>

                    <OperationCenter
                        :initial-operations="$page.props.operaciones || $page.props.operations || []"
                        :highlighted-id="flash.operation_id || ''"
                    />

                    <div class="relative">
                        <button type="button" class="topbar-icon" title="Notificaciones" aria-label="Abrir notificaciones" @click="showNotifications = !showNotifications">
                            <i class="ti ti-bell text-lg" aria-hidden="true"></i>
                            <span v-if="notificationCount" class="notification-count">{{ notificationCount }}</span>
                        </button>
                        <div v-if="showNotifications" class="topbar-menu">
                            <p class="topbar-menu-title">Notificaciones</p>
                            <p v-if="!notificationCount" class="p-4 text-sm text-slate-500 dark:text-slate-400">Todo está en orden.</p>
                            <Link v-if="systemContext.notifications?.failed_operations" :href="can['sistema.health'] ? route('sistema.salud') : route('dashboard')" class="topbar-menu-row">
                                <span>Operaciones con error</span>
                                <strong>{{ systemContext.notifications.failed_operations }}</strong>
                            </Link>
                            <Link v-if="systemContext.notifications?.integration_failures && can['sistema.health']" :href="route('sistema.salud')" class="topbar-menu-row">
                                <span>Sincronizaciones pendientes</span>
                                <strong>{{ systemContext.notifications.integration_failures }}</strong>
                            </Link>
                        </div>
                    </div>

                    <div class="relative">
                        <button type="button" class="topbar-icon" title="Preferencias de vista" aria-label="Abrir preferencias de vista" @click="showPreferences = !showPreferences">
                            <i class="ti ti-adjustments-horizontal text-lg" aria-hidden="true"></i>
                        </button>
                        <div v-if="showPreferences" class="topbar-menu w-72">
                            <p class="topbar-menu-title">Vista</p>
                            <div class="p-3">
                                <p class="mb-2 text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Tema</p>
                                <div class="segmented-control">
                                    <button v-for="theme in ['light', 'dark', 'system']" :key="theme" type="button" :class="{ active: preferences.theme === theme }" @click="setTheme(theme)">
                                        {{ { light: 'Claro', dark: 'Oscuro', system: 'Sistema' }[theme] }}
                                    </button>
                                </div>
                                <p class="mb-2 mt-4 text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Densidad</p>
                                <div class="segmented-control">
                                    <button type="button" :class="{ active: preferences.density !== 'compact' }" @click="setDensity('comfortable')">Cómoda</button>
                                    <button type="button" :class="{ active: preferences.density === 'compact' }" @click="setDensity('compact')">Compacta</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative ml-1">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg p-1 text-left transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:hover:bg-slate-800"
                            aria-label="Abrir menú de mi cuenta"
                            :aria-expanded="showUserMenu"
                            @click="showUserMenu = !showUserMenu"
                        >
                            <span class="hidden text-right sm:block">
                                <span class="block max-w-40 truncate text-xs font-bold text-slate-900 dark:text-white">{{ user?.name }}</span>
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400">{{ user?.role_label }}</span>
                            </span>
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700 text-xs font-extrabold text-white">
                                {{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                            </span>
                            <i class="ti ti-chevron-down hidden text-sm text-slate-500 sm:block" aria-hidden="true"></i>
                        </button>

                        <div v-if="showUserMenu" class="topbar-menu w-64">
                            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                                <p class="truncate text-sm font-extrabold text-slate-950 dark:text-white">{{ user?.name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ user?.email }}</p>
                            </div>
                            <Link :href="route('profile.edit')" class="topbar-menu-row gap-3" @click="showUserMenu = false">
                                <span class="flex items-center gap-3"><i class="ti ti-user-cog text-lg text-blue-700" aria-hidden="true"></i>Mi perfil y contraseña</span>
                                <i class="ti ti-chevron-right text-sm text-slate-400" aria-hidden="true"></i>
                            </Link>
                            <Link :href="route('logout')" method="post" as="button" class="topbar-menu-row w-full gap-3 text-rose-700 dark:text-rose-400">
                                <span class="flex items-center gap-3"><i class="ti ti-logout text-lg" aria-hidden="true"></i>Cerrar sesión</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <main class="app-main">
                <div class="mx-auto w-full max-w-[1600px]">
                    <AppBreadcrumbs :items="breadcrumbs" />
                    <nav v-if="quickAccess.length" class="quick-access" aria-label="Accesos rápidos">
                        <span class="quick-access-label">Accesos</span>
                        <Link v-for="item in quickAccess" :key="item.route" :href="route(item.route)" :class="['quick-access-link', isActive(item.route) && 'active']">
                            <i :class="['ti', item.icon]"></i><span>{{ item.name }}</span>
                        </Link>
                    </nav>
                    <section v-if="$slots.header" class="mb-5 border-b border-slate-200 pb-5 dark:border-slate-700">
                        <slot name="header" />
                    </section>
                    <slot />
                </div>
            </main>
        </div>

        <transition name="toast">
            <div
                v-if="toastVisible"
                :class="['app-toast', flash.error ? 'app-toast-error' : 'app-toast-success']"
                role="status"
            >
                <i :class="['ti text-lg', flash.error ? 'ti-alert-circle' : 'ti-circle-check']" aria-hidden="true"></i>
                <span>{{ flash.error || flash.success }}</span>
                <button type="button" class="ml-auto" aria-label="Cerrar mensaje" @click="toastVisible = false">
                    <i class="ti ti-x" aria-hidden="true"></i>
                </button>
            </div>
        </transition>
    </div>
</template>
