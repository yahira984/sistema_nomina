<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  totalEmpleados:    { type: Number, default: 0 },
  semanaContable:    { type: Number, default: 0 },
  gastoSemanal:      { type: [Number, String], default: '0.00' },
  corteSemana:       { type: String, default: 'Miércoles a martes' },
  nominasPendientes: { type: Number, default: 0 },
})

const modules = [
  {
    name:  'Directorio de personal',
    desc:  'Alta, edición y consulta de trabajadores, puestos y tarifas',
    route: 'empleados.index',
    icon:  'ti-address-book',
    color: 'blue',
  },
  {
    name:  'Control de asistencias',
    desc:  'Captura entradas y salidas · Calcula jornadas semanales',
    route: 'asistencias.index',
    icon:  'ti-clock-check',
    color: 'teal',
  },
  {
    name:  'Generar nóminas',
    desc:  'Calcula pagos, marca estatus y genera recibos PDF',
    route: 'nominas.index',
    icon:  'ti-file-invoice',
    color: 'amber',
  },
]
</script>

<template>
  <Head title="Panel Principal" />

  <AuthenticatedLayout>

    <!-- ── Encabezado ── -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Resumen operativo</h1>
        <p class="page-sub">Sistema PROMATEC-LUGARTH · Semana {{ semanaContable }}</p>
      </div>
      <div class="week-chip">
        <i class="ti ti-calendar" aria-hidden="true"></i>
        Corte: {{ corteSemana }}
      </div>
    </div>

    <!-- ── Estadísticas ── -->
    <div class="stats-grid">

      <div class="stat-card stat-card--blue">
        <div class="stat-icon stat-icon--blue">
          <i class="ti ti-users" aria-hidden="true"></i>
        </div>
        <p class="stat-label">Personal activo</p>
        <p class="stat-value">{{ totalEmpleados }}</p>
        <p class="stat-sub">Colaboradores registrados</p>
      </div>

      <div class="stat-card stat-card--teal">
        <div class="stat-icon stat-icon--teal">
          <i class="ti ti-calendar-stats" aria-hidden="true"></i>
        </div>
        <p class="stat-label">Semana contable</p>
        <p class="stat-value">No. {{ semanaContable }}</p>
        <p class="stat-sub">Período en curso</p>
      </div>

      <div class="stat-card stat-card--amber">
        <div class="stat-icon stat-icon--amber">
          <i class="ti ti-cash" aria-hidden="true"></i>
        </div>
        <p class="stat-label">Gasto semanal</p>
        <p class="stat-value">${{ gastoSemanal }}</p>
        <p class="stat-sub">Total neto dispersado</p>
      </div>

    </div>

    <!-- ── Módulos + Estado ── -->
    <div class="bottom-grid">

      <!-- Módulos -->
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Módulos principales</h2>
          <p class="card-sub">Accede a las tareas clave desde aquí</p>
        </div>

        <Link
          v-for="mod in modules"
          :key="mod.route"
          :href="route(mod.route)"
          class="module-row"
        >
          <div :class="['mod-icon', `mod-icon--${mod.color}`]">
            <i :class="['ti', mod.icon]" aria-hidden="true"></i>
          </div>
          <div class="mod-text">
            <p class="mod-name">{{ mod.name }}</p>
            <p class="mod-desc">{{ mod.desc }}</p>
          </div>
          <i class="ti ti-chevron-right mod-arrow" aria-hidden="true"></i>
        </Link>
      </div>

      <!-- Estado del sistema -->
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Estado del sistema</h2>
          <p class="card-sub">Indicadores en tiempo real</p>
        </div>

        <div class="status-row">
          <span class="status-label">Nóminas procesadas</span>
          <span class="badge badge--green">Al día</span>
        </div>
        <div class="status-row">
          <span class="status-label">Asistencias</span>
          <span class="badge badge--blue">Activo</span>
        </div>
        <div class="status-row">
          <span class="status-label">PDFs pendientes</span>
          <span :class="['badge', nominasPendientes > 0 ? 'badge--amber' : 'badge--green']">
            {{ nominasPendientes }} pendiente{{ nominasPendientes !== 1 ? 's' : '' }}
          </span>
        </div>
        <div class="status-row">
          <span class="status-label">Semana contable</span>
          <span class="status-val">No. {{ semanaContable }} / {{ new Date().getFullYear() }}</span>
        </div>
        <div class="status-row">
          <span class="status-label">Colaboradores activos</span>
          <span class="status-val">{{ totalEmpleados }}</span>
        </div>

        <!-- Acceso rápido -->
        <div class="quick-actions">
          <Link :href="route('nominas.index')" class="quick-btn">
            <i class="ti ti-file-plus" aria-hidden="true"></i>
            Nueva nómina
          </Link>
          <Link :href="route('empleados.index')" class="quick-btn quick-btn--outline">
            <i class="ti ti-user-plus" aria-hidden="true"></i>
            Nuevo empleado
          </Link>
        </div>
      </div>

    </div>

  </AuthenticatedLayout>
</template>

<style scoped>
/* ─── Tipografía ───────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap');

* { box-sizing: border-box; }

/* ─── Encabezado ───────────────────────────── */
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 28px;
}

.page-title {
  font-family: 'Sora', sans-serif;
  font-size: 22px;
  font-weight: 700;
  color: #fff;
  line-height: 1.2;
}

.page-sub {
  font-size: 13px;
  color: #3d5575;
  margin-top: 4px;
}

.week-chip {
  display: flex;
  align-items: center;
  gap: 7px;
  background: rgba(59, 110, 240, 0.12);
  border: 0.5px solid rgba(59, 110, 240, 0.3);
  border-radius: 20px;
  padding: 7px 16px;
  font-size: 12.5px;
  color: #5e8fe8;
  font-weight: 500;
  white-space: nowrap;
}

.week-chip i { font-size: 15px; }

/* ─── Estadísticas ─────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: #0d1225;
  border: 0.5px solid #1e2842;
  border-radius: 14px;
  padding: 22px 20px 18px;
  position: relative;
  overflow: hidden;
  transition: transform 0.15s, border-color 0.15s;
}

.stat-card:hover {
  transform: translateY(-2px);
  border-color: #2a3a5c;
}

.stat-card::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2.5px;
  border-radius: 14px 14px 0 0;
}

.stat-card--blue::after  { background: linear-gradient(90deg, #3b6ef0, #7c3aed); }
.stat-card--teal::after  { background: linear-gradient(90deg, #0ea5a0, #06d6a0); }
.stat-card--amber::after { background: linear-gradient(90deg, #f59e0b, #e07b0b); }

.stat-icon {
  width: 42px;
  height: 42px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.stat-icon--blue  { background: rgba(59, 110, 240, 0.15); }
.stat-icon--teal  { background: rgba(14, 165, 160, 0.15); }
.stat-icon--amber { background: rgba(245, 158, 11, 0.15); }

.stat-icon--blue  i { color: #5b8bef; font-size: 21px; }
.stat-icon--teal  i { color: #0ea5a0; font-size: 21px; }
.stat-icon--amber i { color: #f59e0b; font-size: 21px; }

.stat-label {
  font-size: 10.5px;
  font-weight: 600;
  color: #3d5575;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 6px;
}

.stat-value {
  font-family: 'Sora', sans-serif;
  font-size: 30px;
  font-weight: 700;
  color: #fff;
  line-height: 1;
  margin-bottom: 6px;
}

.stat-sub {
  font-size: 12px;
  color: #3d5575;
}

/* ─── Grid inferior ────────────────────────── */
.bottom-grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 16px;
}

.card {
  background: #0d1225;
  border: 0.5px solid #1e2842;
  border-radius: 14px;
  padding: 22px 20px;
}

.card-header {
  margin-bottom: 18px;
}

.card-title {
  font-family: 'Sora', sans-serif;
  font-size: 14px;
  font-weight: 600;
  color: #d0dff5;
  margin-bottom: 3px;
}

.card-sub {
  font-size: 12px;
  color: #3d5575;
}

/* ─── Módulos ──────────────────────────────── */
.module-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 13px 0;
  border-bottom: 0.5px solid #141c30;
  text-decoration: none;
  transition: padding-left 0.15s;
  cursor: pointer;
}

.module-row:last-child { border-bottom: none; }

.module-row:hover {
  padding-left: 6px;
}

.mod-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.mod-icon--blue  { background: rgba(59, 110, 240, 0.15); }
.mod-icon--teal  { background: rgba(14, 165, 160, 0.15); }
.mod-icon--amber { background: rgba(245, 158, 11, 0.15); }

.mod-icon--blue  i { color: #5b8bef; font-size: 19px; }
.mod-icon--teal  i { color: #0ea5a0; font-size: 19px; }
.mod-icon--amber i { color: #f59e0b; font-size: 19px; }

.mod-text { flex: 1; min-width: 0; }

.mod-name {
  font-size: 13.5px;
  font-weight: 500;
  color: #c0d4f0;
  margin-bottom: 2px;
}

.mod-desc {
  font-size: 11.5px;
  color: #3d5575;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mod-arrow {
  font-size: 17px;
  color: #253550;
  transition: color 0.15s;
}

.module-row:hover .mod-arrow { color: #3b6ef0; }

/* ─── Estado ───────────────────────────────── */
.status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 11px 0;
  border-bottom: 0.5px solid #141c30;
}

.status-row:last-of-type { border-bottom: none; }

.status-label {
  font-size: 12.5px;
  color: #5a7898;
}

.status-val {
  font-size: 13px;
  font-weight: 500;
  color: #c0d4f0;
}

.badge {
  font-size: 10.5px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  letter-spacing: 0.03em;
}

.badge--green { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge--blue  { background: rgba(59, 110, 240, 0.15);  color: #5b8bef; }
.badge--amber { background: rgba(245, 158, 11, 0.15);  color: #f59e0b; }

/* ─── Acciones rápidas ─────────────────────── */
.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 0.5px solid #1e2842;
}

.quick-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  transition: opacity 0.15s, transform 0.1s;
  cursor: pointer;
  border: none;
}

.quick-btn:active { transform: scale(0.98); }

.quick-btn {
  background: linear-gradient(135deg, #3b6ef0, #5a4cf0);
  color: #fff;
}

.quick-btn:hover { opacity: 0.88; }

.quick-btn--outline {
  background: transparent;
  border: 0.5px solid #2a3a5c !important;
  color: #7aa0d4;
}

.quick-btn--outline:hover {
  background: #141c30;
  opacity: 1;
}

.quick-btn i { font-size: 16px; }

/* ─── Responsive ───────────────────────────── */
@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }
  .stats-grid .stat-card:last-child {
    grid-column: 1 / -1;
  }
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .stats-grid .stat-card:last-child {
    grid-column: auto;
  }
  .page-header {
    flex-direction: column;
  }
}
</style>