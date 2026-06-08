<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth.user)

const sidebarOpen = ref(false)

const navItems = [
  {
    label: 'Principal',
    links: [
      { name: 'Panel',        route: 'dashboard',          icon: 'ti-layout-dashboard' },
      { name: 'Empleados',    route: 'empleados.index',    icon: 'ti-users' },
      { name: 'Asistencias',  route: 'asistencias.index',  icon: 'ti-calendar-check' },
      { name: 'Nóminas',      route: 'nominas.index',      icon: 'ti-report-money' },
    ]
  },
  {
    label: 'Sistema',
    links: [
      { name: 'Configuración', route: 'profile.edit', icon: 'ti-settings' },
    ]
  }
]

function isActive(routeName) {
  return route().current(routeName) || route().current(routeName + '.*')
}
</script>

<template>
  <div class="layout-root">

    <!-- ── Google Fonts ── -->
    <link
      href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap"
      rel="stylesheet"
    />

    <!-- ── Mobile overlay ── -->
    <div
      v-if="sidebarOpen"
      class="sidebar-overlay"
      @click="sidebarOpen = false"
    />

    <!-- ══════════ SIDEBAR ══════════ -->
    <aside :class="['sidebar', { 'sidebar--open': sidebarOpen }]">

      <!-- Logo -->
      <div class="sidebar-logo">
        <div class="logo-badge">
          <i class="ti ti-file-invoice" aria-hidden="true"></i>
        </div>
        <div>
          <p class="logo-name">PROMATEC</p>
          <p class="logo-sub">LUGARTH · NÓMINAS</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="sidebar-nav">
        <template v-for="group in navItems" :key="group.label">
          <p class="nav-section-label">{{ group.label }}</p>
          <Link
            v-for="item in group.links"
            :key="item.route"
            :href="route(item.route)"
            :class="['nav-item', { 'nav-item--active': isActive(item.route) }]"
          >
            <i :class="['ti', item.icon]" aria-hidden="true"></i>
            <span>{{ item.name }}</span>
          </Link>
        </template>
      </nav>

      <!-- Footer usuario -->
      <div class="sidebar-footer">
        <div class="user-avatar">{{ user?.name?.charAt(0)?.toUpperCase() ?? 'U' }}</div>
        <div class="user-info">
          <p class="user-name">{{ user?.name }}</p>
          <p class="user-email">{{ user?.email }}</p>
        </div>
        <Link :href="route('logout')" method="post" as="button" class="logout-btn" title="Cerrar sesión">
          <i class="ti ti-logout" aria-hidden="true"></i>
        </Link>
      </div>
    </aside>

    <!-- ══════════ CONTENIDO PRINCIPAL ══════════ -->
    <div class="main-wrapper">

      <!-- Topbar mobile -->
      <header class="topbar">
        <button class="topbar-menu-btn" @click="sidebarOpen = !sidebarOpen" aria-label="Abrir menú">
          <i class="ti ti-menu-2" aria-hidden="true"></i>
        </button>
        <p class="topbar-title">Sistema de Nóminas</p>
        <div class="topbar-avatar">{{ user?.name?.charAt(0)?.toUpperCase() ?? 'U' }}</div>
      </header>

      <!-- Slot de página -->
      <main class="page-content">
        <slot />
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
  background: #0a0e1a;
  font-family: 'DM Sans', sans-serif;
  color: #e2e8f0;
}

/* ─── Sidebar ──────────────────────────────── */
.sidebar {
  width: 230px;
  min-height: 100vh;
  background: #0d1225;
  border-right: 0.5px solid #1e2842;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
  z-index: 40;
  flex-shrink: 0;
}

.sidebar-overlay {
  display: none;
}

/* ─── Logo ─────────────────────────────────── */
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 22px 20px 20px;
  border-bottom: 0.5px solid #1e2842;
}

.logo-badge {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  background: linear-gradient(135deg, #3b6ef0, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo-badge i { font-size: 20px; color: #fff; }

.logo-name {
  font-family: 'Sora', sans-serif;
  font-size: 12px;
  font-weight: 700;
  color: #fff;
  letter-spacing: 0.06em;
  line-height: 1.2;
}

.logo-sub {
  font-size: 10px;
  color: #3d5575;
  letter-spacing: 0.08em;
}

/* ─── Navegación ───────────────────────────── */
.sidebar-nav {
  flex: 1;
  padding: 14px 12px;
  overflow-y: auto;
}

.nav-section-label {
  font-size: 10px;
  font-weight: 600;
  color: #253550;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 0 8px;
  margin: 16px 0 6px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 9px;
  font-size: 13.5px;
  font-weight: 400;
  color: #5a7898;
  text-decoration: none;
  transition: background 0.15s, color 0.15s, padding-left 0.15s;
  margin-bottom: 2px;
}

.nav-item i { font-size: 18px; }

.nav-item:hover {
  background: #141c30;
  color: #94b4e0;
  padding-left: 16px;
}

.nav-item--active {
  background: rgba(59, 110, 240, 0.14);
  color: #5b8bef;
  font-weight: 500;
}

.nav-item--active i { color: #5b8bef; }

/* ─── Footer sidebar ───────────────────────── */
.sidebar-footer {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 14px;
  border-top: 0.5px solid #1e2842;
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b6ef0, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 600;
  color: #fff;
  flex-shrink: 0;
}

.user-info { flex: 1; overflow: hidden; }

.user-name {
  font-size: 12.5px;
  font-weight: 500;
  color: #c0d4f0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 10.5px;
  color: #3d5575;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.logout-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #3d5575;
  font-size: 18px;
  display: flex;
  align-items: center;
  padding: 4px;
  border-radius: 6px;
  transition: color 0.15s, background 0.15s;
}

.logout-btn:hover {
  color: #e05858;
  background: rgba(224, 88, 88, 0.1);
}

/* ─── Main content ─────────────────────────── */
.main-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.topbar {
  display: none;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  background: #0d1225;
  border-bottom: 0.5px solid #1e2842;
  position: sticky;
  top: 0;
  z-index: 30;
}

.topbar-menu-btn {
  background: none;
  border: none;
  color: #94b4e0;
  font-size: 22px;
  cursor: pointer;
  display: flex;
  align-items: center;
}

.topbar-title {
  font-family: 'Sora', sans-serif;
  font-size: 14px;
  font-weight: 600;
  color: #d0dff5;
}

.topbar-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b6ef0, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  color: #fff;
}

.page-content {
  flex: 1;
  padding: 32px;
}

/* ─── Responsive ───────────────────────────── */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    left: -230px;
    transition: left 0.25s ease;
    height: 100vh;
  }

  .sidebar--open {
    left: 0;
    box-shadow: 4px 0 40px rgba(0,0,0,0.6);
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 35;
  }

  .topbar {
    display: flex;
  }

  .page-content {
    padding: 20px 16px;
  }
}
</style>