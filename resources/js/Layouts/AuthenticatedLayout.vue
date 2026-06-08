<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth.user)

// Estados del menú
const isSidebarOpenMobile = ref(false)
const isSidebarCollapsedDesktop = ref(false)

const navItems = [
  {
    label: 'Principal',
    links: [
      { name: 'Panel',        route: 'dashboard',         icon: 'ti-layout-dashboard' },
      { name: 'Empleados',    route: 'empleados.index',   icon: 'ti-users' },
      { name: 'Asistencias',  route: 'asistencias.index', icon: 'ti-calendar-check' },
      { name: 'Nóminas',      route: 'nominas.index',     icon: 'ti-report-money' },
    ]
  },
  {
    label: 'Sistema',
    links: [
      { name: 'Configuración', route: 'profile.edit', icon: 'ti-settings' },
      { name: 'Base de datos', route: 'base-datos.index', icon: 'ti-database' },
    ]
  }
]

function isActive(routeName) {
  return route().current(routeName) || route().current(routeName + '.*')
}

// Función para el botón hamburguesa
function toggleSidebar() {
  if (window.innerWidth < 1024) {
    isSidebarOpenMobile.value = !isSidebarOpenMobile.value
  } else {
    isSidebarCollapsedDesktop.value = !isSidebarCollapsedDesktop.value
  }
}
</script>

<template>
  <div class="layout-root">

    <div v-if="isSidebarOpenMobile" class="sidebar-overlay" @click="isSidebarOpenMobile = false"></div>

    <aside :class="['sidebar', isSidebarCollapsedDesktop ? 'sidebar--collapsed' : '', isSidebarOpenMobile ? 'sidebar--mobile-open' : '']">

      <div class="sidebar-logo">
        <div class="logo-badge">
          <i class="ti ti-file-invoice" aria-hidden="true"></i>
        </div>
        <div class="logo-text" v-show="!isSidebarCollapsedDesktop">
          <p class="logo-name">PROMATEC</p>
          <p class="logo-sub">LUGARTH</p>
        </div>
      </div>

      <nav class="sidebar-nav custom-scrollbar">
        <template v-for="group in navItems" :key="group.label">
          <p v-show="!isSidebarCollapsedDesktop" class="nav-section-label">{{ group.label }}</p>
          <div v-show="isSidebarCollapsedDesktop" class="nav-section-divider"></div>

          <Link
            v-for="item in group.links"
            :key="item.route"
            :href="route(item.route)"
            :class="['nav-item', { 'nav-item--active': isActive(item.route) }]"
            :title="isSidebarCollapsedDesktop ? item.name : ''"
          >
            <i :class="['ti', item.icon]" aria-hidden="true"></i>
            <span v-show="!isSidebarCollapsedDesktop" class="nav-item-text">{{ item.name }}</span>
          </Link>
        </template>
      </nav>

    </aside>

    <div class="main-wrapper">

      <header class="topbar">
        <div class="topbar-left">
          <button class="hamburger-btn" @click="toggleSidebar" aria-label="Alternar menú">
            <i class="ti ti-menu-2" aria-hidden="true"></i>
          </button>
          <p class="hidden sm:block topbar-system-name">Control de Nóminas</p>
          <div class="hidden xl:flex topbar-health">
            <span class="health-dot"></span>
            Sistema activo
          </div>
        </div>

        <div class="topbar-right">
          <div class="user-profile">
            <div class="user-info hidden sm:block">
              <p class="user-name">{{ user?.name }}</p>
              <p class="user-email">{{ user?.email }}</p>
            </div>
            <div class="user-avatar">{{ user?.name?.charAt(0)?.toUpperCase() ?? 'U' }}</div>
          </div>

          <div class="topbar-divider"></div>

          <Link :href="route('logout')" method="post" as="button" class="logout-btn" title="Cerrar sesión">
            <i class="ti ti-logout" aria-hidden="true"></i>
            <span class="hidden sm:inline">Salir</span>
          </Link>
        </div>
      </header>

      <main class="page-content">
        <div class="page-container">
          <section v-if="$slots.header" class="content-header">
            <slot name="header" />
          </section>
          <slot />
        </div>
      </main>

    </div>
  </div>
</template>

<style scoped>
/* ─── Fuentes & Reset ──────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }

.layout-root {
  display: flex;
  min-height: 100vh;
  background:
    linear-gradient(180deg, #f8fafc 0%, #eef4f8 100%);
  font-family: 'DM Sans', sans-serif;
  color: #334155;
}

/* ─── Sidebar ──────────────────────────────── */
.sidebar {
  width: 260px;
  background: #0b1220;
  border-right: 1px solid rgba(148, 163, 184, 0.18);
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
  z-index: 40;
  flex-shrink: 0;
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar--collapsed {
  width: 80px;
}

.sidebar-overlay {
  display: none;
}

/* ─── Logo ─────────────────────────────────── */
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.16);
  height: 72px; /* Alineado con el topbar */
  overflow: hidden;
}

.logo-badge {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  background: linear-gradient(135deg, #14b8a6, #3b82f6);
  box-shadow: 0 12px 26px rgba(20, 184, 166, 0.22);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo-badge i { font-size: 22px; color: #fff; }

.logo-text {
  display: flex;
  flex-direction: column;
  white-space: nowrap;
}

.logo-name {
  font-family: 'Sora', sans-serif;
  font-size: 14px;
  font-weight: 700;
  color: #f8fafc;
  letter-spacing: 0.05em;
  line-height: 1.2;
}

.logo-sub {
  font-size: 10px;
  color: #94a3b8;
  letter-spacing: 0.1em;
  font-weight: 600;
}

/* ─── Navegación ───────────────────────────── */
.sidebar-nav {
  flex: 1;
  padding: 20px 12px;
  overflow-y: auto;
  overflow-x: hidden;
}

.nav-section-label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 0 12px;
  margin: 16px 0 8px;
  white-space: nowrap;
}

.nav-section-divider {
  height: 1px;
  background-color: rgba(148, 163, 184, 0.18);
  margin: 16px 12px 8px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  color: #9aa7b8;
  text-decoration: none;
  transition: all 0.2s ease;
  margin-bottom: 4px;
  white-space: nowrap;
}

.nav-item i {
  font-size: 20px;
  flex-shrink: 0;
  transition: color 0.2s ease;
}

.nav-item:hover {
  background-color: rgba(148, 163, 184, 0.10);
  color: #f8fafc;
}

.nav-item:hover i {
  color: #0d9488;
}

.nav-item--active {
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.18), rgba(59, 130, 246, 0.15));
  color: #ffffff;
  box-shadow: inset 3px 0 0 #14b8a6;
  font-weight: 600;
}

.nav-item--active i {
  color: #5eead4;
}

/* Centrar iconos cuando está colapsado */
.sidebar--collapsed .nav-item {
  justify-content: center;
  padding: 12px 0;
}

/* ─── Main Content & Topbar ────────────────── */
.main-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 72px;
  padding: 0 24px;
  background: rgba(255, 255, 255, 0.92);
  border-bottom: 1px solid rgba(226, 232, 240, 0.86);
  position: sticky;
  top: 0;
  z-index: 30;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
  backdrop-filter: blur(16px);
}

.topbar-left, .topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.hamburger-btn {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #64748b;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.hamburger-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.topbar-system-name {
  font-family: 'Sora', sans-serif;
  font-weight: 600;
  color: #0f172a;
  font-size: 15px;
}

.topbar-health {
  align-items: center;
  gap: 8px;
  border: 1px solid #d1fae5;
  background: #ecfdf5;
  color: #047857;
  border-radius: 999px;
  padding: 7px 12px;
  font-size: 12px;
  font-weight: 700;
}

.health-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #10b981;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.14);
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-info {
  text-align: right;
}

.user-name {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.user-email {
  font-size: 11px;
  color: #64748b;
  font-weight: 500;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  background: linear-gradient(135deg, #0d9488, #2563eb);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  font-weight: 700;
  color: #fff;
  box-shadow: 0 2px 6px rgba(13, 148, 136, 0.2);
}

.topbar-divider {
  width: 1px;
  height: 32px;
  background-color: #e2e8f0;
  margin: 0 8px;
}

.logout-btn {
  background: transparent;
  border: 1px solid transparent;
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 10px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.logout-btn i { font-size: 20px; }

.logout-btn:hover {
  color: #e11d48;
  background-color: #fff1f2;
  border-color: #fecdd3;
}

/* ─── Contenedor de la Página ──────────────── */
.page-content {
  flex: 1;
  padding: 28px;
  overflow-y: auto;
}

.page-container {
  max-width: 1400px;
  margin: 0 auto;
}

.content-header {
  margin-bottom: 24px;
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 18px;
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.94));
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
  padding: 20px 22px;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* ─── Responsive (Celulares y Tablets) ─────── */
@media (max-width: 1024px) {
  .sidebar {
    position: fixed;
    left: -260px;
    width: 260px;
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .sidebar--mobile-open {
    left: 0;
    box-shadow: 10px 0 25px rgba(0,0,0,0.1);
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(2px);
    z-index: 35;
  }

  .page-content {
    padding: 24px 16px;
  }

  .topbar {
    padding: 0 16px;
  }
}
</style>
