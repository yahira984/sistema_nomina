<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    rules: { type: Array, default: () => [] },
    calendar: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    year: Number,
})

const activeTab = ref('rules')
const editingRule = ref(null)
const selectedYear = ref(props.year)
const days = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mié' },
    { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' },
    { value: 6, label: 'Sáb' },
    { value: 7, label: 'Dom' },
]

const behaviorOptions = [
    {
        key: 'turno_24x24',
        label: 'Turno de 24 horas por 24 de descanso',
        icon: 'ti-clock-24',
        help: 'Alterna un día laboral y uno de descanso. El sistema solo exigirá asistencia en los días que correspondan a la rotación.',
        onLabel: 'Sí, usar 24×24',
        offLabel: 'No usar 24×24',
    },
    {
        key: 'sin_horas_extra',
        label: 'No calcular horas extra',
        icon: 'ti-clock-x',
        help: 'Las horas extra quedarán en cero en Asistencias y no se pagarán como percepción adicional en Nómina.',
        onLabel: 'No calcular extras',
        offLabel: 'Sí calcular extras',
    },
    {
        key: 'sin_retardos',
        label: 'No calcular retardos',
        icon: 'ti-alarm-off',
        help: 'No acumula minutos tarde y Nómina no aplicará descuento por retardo a las personas alcanzadas por esta regla.',
        onLabel: 'No calcular retardos',
        offLabel: 'Sí calcular retardos',
    },
    {
        key: 'pago_por_hora_topado',
        label: 'Limitar pago por horas semanales',
        icon: 'ti-cash-banknote-off',
        help: 'Úsalo únicamente para personal pagado por hora con un máximo semanal. Las horas se pagan hasta el tope y no se separan como pago extra doble.',
        onLabel: 'Sí, limitar horas',
        offLabel: 'No limitar horas',
    },
]

const ruleForm = useForm({
    name: '',
    scope_type: 'position',
    scope_value: '',
    empleado_id: '',
    turno_24x24: null,
    sin_horas_extra: null,
    sin_retardos: null,
    pago_por_hora_topado: null,
    tope_horas_semanales: null,
    use_schedule: false,
    hora_entrada: '',
    hora_salida: '',
    hora_salida_jueves: '17:30',
    dias_laborales: [],
    fecha_referencia_turno: '',
    priority: 100,
    active: true,
})

const calendarForm = useForm({
    date: `${props.year}-01-01`,
    kind: 'non_working',
    scope_type: 'global',
    empleado_id: '',
    position: '',
    shift: '',
    name: '',
    notes: '',
    active: true,
})

const scopeLabel = rule => {
    if (rule.scope_type === 'employee') return rule.empleado?.nombre_completo || 'Empleado'
    if (rule.scope_type === 'employee_number') return `Empleado #${rule.scope_value}`
    if (rule.scope_type === 'position') return `Puesto: ${rule.scope_value}`
    if (rule.scope_type === 'position_contains') return `Puesto contiene: ${rule.scope_value}`
    return 'Todo el personal'
}

const normalized = value => String(value || '').trim().toLocaleUpperCase('es-MX')

const scopeHelp = computed(() => ({
    position: 'Se aplicará solamente cuando el nombre del puesto coincida por completo.',
    position_contains: 'Se aplicará a cualquier puesto que incluya el texto escrito. Ejemplo: “VIGILANCIA” también encuentra “VIGILANCIA NOCTURNA”.',
    employee: 'Afectará únicamente al empleado seleccionado, aunque después cambie su número.',
    employee_number: 'Afectará al trabajador que tenga ese número. Es útil para una excepción individual rápida.',
    global: 'Afectará a todo el personal. Usa una prioridad baja para que las excepciones individuales puedan reemplazarla.',
}[ruleForm.scope_type]))

const affectedEmployees = computed(() => props.employees.filter(employee => {
    if (ruleForm.scope_type === 'global') return true
    if (ruleForm.scope_type === 'employee') return Number(employee.id) === Number(ruleForm.empleado_id)
    if (ruleForm.scope_type === 'employee_number') {
        return normalized(employee.numero_empleado).replace(/^0+/, '') === normalized(ruleForm.scope_value).replace(/^0+/, '')
    }
    if (ruleForm.scope_type === 'position') return normalized(employee.puesto) === normalized(ruleForm.scope_value)
    if (ruleForm.scope_type === 'position_contains') {
        return normalized(ruleForm.scope_value) !== '' && normalized(employee.puesto).includes(normalized(ruleForm.scope_value))
    }
    return false
}))

const targetDescription = computed(() => {
    if (ruleForm.scope_type === 'global') return 'Todo el personal activo'
    if (ruleForm.scope_type === 'employee') {
        const employee = props.employees.find(item => Number(item.id) === Number(ruleForm.empleado_id))
        return employee ? `#${employee.numero_empleado} · ${employee.nombre_completo}` : 'Falta seleccionar un empleado'
    }
    if (ruleForm.scope_type === 'employee_number') return ruleForm.scope_value ? `Número de empleado ${ruleForm.scope_value}` : 'Falta escribir el número'
    if (ruleForm.scope_type === 'position') return ruleForm.scope_value ? `Puesto exacto: ${ruleForm.scope_value}` : 'Falta seleccionar el puesto'
    return ruleForm.scope_value ? `Puestos que contienen: ${ruleForm.scope_value}` : 'Falta escribir el texto del puesto'
})

const selectedDayLabels = computed(() => days
    .filter(day => ruleForm.dias_laborales.includes(day.value))
    .map(day => day.label)
    .join(', '))

const ruleImpacts = computed(() => {
    const impacts = []

    if (ruleForm.turno_24x24 === true) impacts.push('Asistencia: alterna 24 horas de trabajo y 24 de descanso; no marca falta en el día de descanso.')
    if (ruleForm.turno_24x24 === false) impacts.push('Asistencia: cancela un turno 24×24 heredado y utiliza los días laborales normales o personalizados.')
    if (ruleForm.sin_horas_extra === true) impacts.push('Asistencia y Nómina: horas extra y su pago quedan en cero.')
    if (ruleForm.sin_horas_extra === false) impacts.push('Asistencia y Nómina: permite calcular y pagar las horas extra.')
    if (ruleForm.sin_retardos === true) impacts.push('Asistencia y Nómina: no acumula retardos ni aplica su descuento.')
    if (ruleForm.sin_retardos === false) impacts.push('Asistencia y Nómina: permite calcular retardos y su descuento cuando corresponda.')
    if (ruleForm.pago_por_hora_topado === true) impacts.push(`Nómina: limita el pago por hora a ${ruleForm.tope_horas_semanales || 48} horas semanales.`)
    if (ruleForm.pago_por_hora_topado === false) impacts.push('Nómina: cancela un tope heredado y usa el esquema normal de pago.')
    if (ruleForm.use_schedule) {
        impacts.push(`Asistencia: espera jornada ${ruleForm.hora_entrada || '--:--'} a ${ruleForm.hora_salida || '--:--'}; los jueves sale a ${ruleForm.hora_salida_jueves || ruleForm.hora_salida || '--:--'}.`)
    }

    return impacts
})

const ruleFlags = rule => [
    rule.turno_24x24 ? '24×24' : null,
    rule.sin_horas_extra ? 'Sin H.E.' : null,
    rule.sin_retardos ? 'Sin retardos' : null,
    rule.pago_por_hora_topado ? `Tope ${rule.tope_horas_semanales || 48} h` : null,
    rule.hora_entrada || rule.hora_salida ? `Horario ${String(rule.hora_entrada || '--:--').substring(0, 5)}–${String(rule.hora_salida || '--:--').substring(0, 5)}` : null,
].filter(Boolean)

const submitRule = () => {
    ruleForm.transform(data => ({
        ...data,
        hora_entrada: data.use_schedule ? data.hora_entrada : null,
        hora_salida: data.use_schedule ? data.hora_salida : null,
        hora_salida_jueves: data.use_schedule ? data.hora_salida_jueves : null,
        dias_laborales: data.use_schedule ? data.dias_laborales : null,
    }))

    const options = {
        preserveScroll: true,
        onSuccess: resetRule,
    }

    if (editingRule.value) {
        ruleForm.put(route('reglas-laborales.update', editingRule.value.id), options)
    } else {
        ruleForm.post(route('reglas-laborales.store'), options)
    }
}

const editRule = rule => {
    editingRule.value = rule
    Object.assign(ruleForm, {
        name: rule.name || '',
        scope_type: rule.scope_type || 'position',
        scope_value: rule.scope_value || '',
        empleado_id: rule.empleado_id || '',
        turno_24x24: rule.turno_24x24 === null ? null : Boolean(rule.turno_24x24),
        sin_horas_extra: rule.sin_horas_extra === null ? null : Boolean(rule.sin_horas_extra),
        sin_retardos: rule.sin_retardos === null ? null : Boolean(rule.sin_retardos),
        pago_por_hora_topado: rule.pago_por_hora_topado === null ? null : Boolean(rule.pago_por_hora_topado),
        tope_horas_semanales: rule.tope_horas_semanales === null ? null : Number(rule.tope_horas_semanales),
        use_schedule: Boolean(rule.hora_entrada || rule.hora_salida || rule.dias_laborales),
        hora_entrada: rule.hora_entrada ? String(rule.hora_entrada).substring(0, 5) : '',
        hora_salida: rule.hora_salida ? String(rule.hora_salida).substring(0, 5) : '',
        hora_salida_jueves: rule.hora_salida_jueves ? String(rule.hora_salida_jueves).substring(0, 5) : '17:30',
        dias_laborales: rule.dias_laborales || [],
        fecha_referencia_turno: rule.fecha_referencia_turno || '',
        priority: Number(rule.priority || 100),
        active: Boolean(rule.active),
    })
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const resetRule = () => {
    editingRule.value = null
    ruleForm.reset()
    ruleForm.scope_type = 'position'
    ruleForm.turno_24x24 = null
    ruleForm.sin_horas_extra = null
    ruleForm.sin_retardos = null
    ruleForm.pago_por_hora_topado = null
    ruleForm.tope_horas_semanales = null
    ruleForm.use_schedule = false
    ruleForm.hora_entrada = ''
    ruleForm.hora_salida = ''
    ruleForm.hora_salida_jueves = '17:30'
    ruleForm.dias_laborales = []
    ruleForm.priority = 100
    ruleForm.active = true
}

const removeRule = rule => {
    if (!window.confirm(`Se eliminará la regla "${rule.name}". ¿Continuar?`)) return
    router.delete(route('reglas-laborales.destroy', rule.id), { preserveScroll: true })
}

const submitCalendar = () => {
    calendarForm.post(route('calendario-laboral.store'), {
        preserveScroll: true,
        onSuccess: () => {
            calendarForm.name = ''
            calendarForm.notes = ''
        },
    })
}

const removeCalendarDay = day => {
    if (!window.confirm(`Se eliminará "${day.name}" del calendario. ¿Continuar?`)) return
    router.delete(route('calendario-laboral.destroy', day.id), { preserveScroll: true })
}

const loadYear = () => {
    router.get(route('reglas-laborales.index'), { year: selectedYear.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const activeRules = computed(() => props.rules.filter(rule => rule.active).length)
</script>

<template>
    <Head title="Reglas laborales" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase text-blue-700 dark:text-blue-300">Configuración operativa</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Reglas y calendario laboral</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Define excepciones por puesto o empleado sin editar código.</p>
                </div>
                <div class="status-pill status-success">{{ activeRules }} reglas activas</div>
            </div>
        </template>

        <div class="mb-5 segmented-control max-w-md">
            <button type="button" :class="{ active: activeTab === 'rules' }" @click="activeTab = 'rules'">
                <i class="ti ti-adjustments" aria-hidden="true"></i>
                Reglas especiales
            </button>
            <button type="button" :class="{ active: activeTab === 'calendar' }" @click="activeTab = 'calendar'">
                <i class="ti ti-calendar-event" aria-hidden="true"></i>
                Calendario anual
            </button>
        </div>

        <section v-if="activeTab === 'rules'" class="mb-5 border-y border-blue-200 bg-blue-50 px-4 py-4 dark:border-blue-900 dark:bg-blue-950/30 sm:px-5">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_repeat(3,minmax(0,.8fr))] lg:items-center">
                <div>
                    <p class="text-sm font-extrabold text-blue-950 dark:text-blue-100">Cómo crear una regla sin equivocarte</p>
                    <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">Configura solo aquello que será diferente. El resto puede quedar como “No definir”.</p>
                </div>
                <div class="flex items-start gap-2 text-sm text-blue-900 dark:text-blue-100"><span class="guide-step">1</span><span><strong>Elige a quién</strong><br />Puesto, empleado o todos.</span></div>
                <div class="flex items-start gap-2 text-sm text-blue-900 dark:text-blue-100"><span class="guide-step">2</span><span><strong>Activa el cambio</strong><br />Horario, extras o retardos.</span></div>
                <div class="flex items-start gap-2 text-sm text-blue-900 dark:text-blue-100"><span class="guide-step">3</span><span><strong>Revisa el resumen</strong><br />Confirma el efecto antes de guardar.</span></div>
            </div>
        </section>
        <section v-else class="mb-5 border-y border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100 sm:px-5">
            <p class="font-extrabold">¿Para qué sirve el calendario anual?</p>
            <p class="mt-1 leading-6">Un día <strong>no laborable</strong> no exige asistencia ni genera falta. Un día <strong>laborable extraordinario</strong> sí exige asistencia aunque normalmente fuera descanso. El cambio se toma en cuenta al revisar asistencias y generar nómina.</p>
        </section>

        <div v-if="activeTab === 'rules'" class="grid gap-5 xl:grid-cols-[470px_minmax(0,1fr)]">
            <form class="app-panel self-start" @submit.prevent="submitRule">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">{{ editingRule ? 'Editar regla' : 'Nueva regla' }}</h2>
                        <p class="panel-subtitle">Los cambios se reflejan automáticamente en Asistencias y Nómina.</p>
                    </div>
                    <button v-if="editingRule" type="button" class="icon-button" title="Cancelar edición" @click="resetRule">
                        <i class="ti ti-x" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <label class="block">
                        <span class="field-label">Nombre para identificarla</span>
                        <input v-model="ruleForm.name" class="field-input" required placeholder="Ej. Vigilancia 24×24" />
                        <span class="help-text">Este nombre solo sirve para reconocer la regla; no cambia ningún cálculo.</span>
                    </label>

                    <label class="block">
                        <span class="field-label">¿A quién se aplicará?</span>
                        <select v-model="ruleForm.scope_type" class="field-input">
                            <option value="position">Puesto exacto</option>
                            <option value="position_contains">Puestos que contengan un texto</option>
                            <option value="employee">Empleado específico</option>
                            <option value="employee_number">Número de empleado</option>
                            <option value="global">Todo el personal</option>
                        </select>
                        <span class="help-text">{{ scopeHelp }}</span>
                    </label>

                    <label v-if="ruleForm.scope_type === 'employee'" class="block">
                        <span class="field-label">Empleado</span>
                        <select v-model="ruleForm.empleado_id" class="field-input" required>
                            <option value="">Selecciona...</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                #{{ employee.numero_empleado }} · {{ employee.nombre_completo }}
                            </option>
                        </select>
                        <span class="help-text">{{ affectedEmployees.length ? 'Esta regla afectará solamente a esta persona.' : 'Selecciona una persona para continuar.' }}</span>
                    </label>

                    <label v-else-if="ruleForm.scope_type === 'position'" class="block">
                        <span class="field-label">Puesto</span>
                        <select v-model="ruleForm.scope_value" class="field-input" required>
                            <option value="">Selecciona...</option>
                            <option v-for="position in positions" :key="position" :value="position">{{ position }}</option>
                        </select>
                        <span class="help-text">{{ affectedEmployees.length }} empleado(s) activo(s) tienen actualmente este puesto.</span>
                    </label>

                    <label v-else-if="!['global', 'employee'].includes(ruleForm.scope_type)" class="block">
                        <span class="field-label">{{ ruleForm.scope_type === 'employee_number' ? 'Número' : 'Texto del puesto' }}</span>
                        <input v-model="ruleForm.scope_value" class="field-input" required :placeholder="ruleForm.scope_type === 'employee_number' ? 'Ej. 20' : 'Ej. VIGILANCIA'" />
                        <span class="help-text">{{ affectedEmployees.length }} empleado(s) activo(s) coinciden en este momento.</span>
                    </label>

                    <fieldset class="border-y border-slate-200 py-1 dark:border-slate-700">
                        <legend class="field-label px-1">¿Qué comportamiento será diferente?</legend>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            <label v-for="option in behaviorOptions" :key="option.key" class="rule-option">
                                <span class="flex min-w-0 items-start gap-3">
                                    <span class="rule-option-icon"><i :class="['ti', option.icon]" aria-hidden="true"></i></span>
                                    <span class="min-w-0">
                                        <strong class="block text-sm text-slate-950 dark:text-white">{{ option.label }}</strong>
                                        <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ option.help }}</span>
                                    </span>
                                </span>
                                <select v-model="ruleForm[option.key]" class="field-input rule-option-select" :aria-label="option.label">
                                    <option :value="null">Usar regla general</option>
                                    <option :value="true">{{ option.onLabel }}</option>
                                    <option :value="false">{{ option.offLabel }}</option>
                                </select>
                            </label>
                        </div>
                    </fieldset>

                    <label class="toggle-row">
                        <input v-model="ruleForm.use_schedule" type="checkbox" />
                        <span><strong class="block">Usar horario y días propios</strong><span class="mt-0.5 block text-xs font-medium text-slate-500 dark:text-slate-400">Actívalo si este grupo no trabaja con el horario general de lunes a viernes, 08:00 a 17:30.</span></span>
                    </label>

                    <div v-if="ruleForm.use_schedule" class="grid gap-3 sm:grid-cols-3">
                        <label>
                            <span class="field-label">Entrada</span>
                            <input v-model="ruleForm.hora_entrada" type="time" class="field-input" />
                        </label>
                        <label>
                            <span class="field-label">Salida</span>
                            <input v-model="ruleForm.hora_salida" type="time" class="field-input" />
                            <span class="help-text">Horario habitual.</span>
                        </label>
                        <label>
                            <span class="field-label">Salida del jueves</span>
                            <input v-model="ruleForm.hora_salida_jueves" type="time" class="field-input" />
                            <span class="help-text">Por defecto 17:30. Cámbiala solo si ese puesto tiene otro horario los jueves.</span>
                        </label>
                    </div>

                    <fieldset v-if="ruleForm.use_schedule">
                        <legend class="field-label">Días laborales</legend>
                        <div class="grid grid-cols-7 gap-1">
                            <label v-for="day in days" :key="day.value" class="day-toggle">
                                <input v-model="ruleForm.dias_laborales" type="checkbox" :value="day.value" class="sr-only" />
                                <span>{{ day.label }}</span>
                            </label>
                        </div>
                        <p class="help-text">En estos días el sistema esperará una asistencia o incidencia. Los días no seleccionados no generarán falta.</p>
                    </fieldset>

                    <label v-if="ruleForm.pago_por_hora_topado === true" class="block">
                        <span class="field-label">Máximo de horas pagables por semana</span>
                        <input v-model.number="ruleForm.tope_horas_semanales" type="number" min="0" max="168" step="0.5" class="field-input" placeholder="48" />
                        <span class="help-text">Ejemplo: con 48, Nómina nunca pagará más de 48 horas en esa semana bajo este esquema especial.</span>
                    </label>

                    <label class="block">
                        <span class="field-label">Prioridad de la regla</span>
                        <input v-model.number="ruleForm.priority" type="number" min="1" max="1000" class="field-input" />
                        <span class="help-text">Si dos reglas afectan a la misma persona, gana el valor más alto solo en las opciones que estén definidas. Recomendación: general 100, puesto 300, empleado 500.</span>
                    </label>

                    <label v-if="ruleForm.turno_24x24" class="block">
                        <span class="field-label">Primer día que sí trabaja en la rotación</span>
                        <input v-model="ruleForm.fecha_referencia_turno" type="date" class="field-input" />
                        <span class="help-text">Desde esta fecha se alterna: trabaja, descansa, trabaja, descansa. Si se deja vacío se usa la fecha de ingreso.</span>
                    </label>

                    <section class="border-y border-slate-200 bg-slate-50 px-1 py-4 dark:border-slate-700 dark:bg-slate-900" aria-live="polite">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-extrabold text-slate-950 dark:text-white">Resumen antes de guardar</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ targetDescription }}</p>
                            </div>
                            <span :class="['status-pill', affectedEmployees.length ? 'status-info' : 'status-warning']">{{ affectedEmployees.length }} empleado(s)</span>
                        </div>
                        <ul v-if="ruleImpacts.length" class="mt-3 space-y-2">
                            <li v-for="impact in ruleImpacts" :key="impact" class="flex items-start gap-2 text-xs leading-5 text-slate-700 dark:text-slate-300">
                                <i class="ti ti-check mt-0.5 shrink-0 text-emerald-600" aria-hidden="true"></i>
                                <span>{{ impact }}</span>
                            </li>
                        </ul>
                        <p v-else class="mt-3 text-xs font-semibold text-amber-700 dark:text-amber-300">Todavía no has definido ningún cambio. Activa una opción o personaliza el horario.</p>
                    </section>

                    <div v-if="Object.keys(ruleForm.errors).length" class="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800" role="alert">
                        Revisa los campos marcados antes de guardar.
                    </div>

                    <label class="toggle-row"><input v-model="ruleForm.active" type="checkbox" /> <span><strong>Regla activa</strong><span class="block text-xs font-medium text-slate-500 dark:text-slate-400">Si se desactiva, queda guardada pero no modifica asistencias ni nómina.</span></span></label>
                    <button class="btn-accent w-full" :disabled="ruleForm.processing">
                        <i class="ti ti-device-floppy" aria-hidden="true"></i>
                        {{ editingRule ? 'Guardar cambios' : 'Crear regla' }}
                    </button>
                </div>
            </form>

            <section class="app-panel min-w-0">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Reglas registradas</h2>
                        <p class="panel-subtitle">Toda regla activa se consulta al capturar asistencia, importar CSV y generar nómina.</p>
                    </div>
                </div>

                <div v-if="rules.length === 0" class="empty-state">Todavía no hay reglas especiales.</div>
                <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                    <article v-for="rule in rules" :key="rule.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-slate-950 dark:text-white">{{ rule.name }}</h3>
                                <span :class="['status-pill', rule.active ? 'status-success' : 'status-neutral']">
                                    {{ rule.active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ scopeLabel(rule) }} · Prioridad {{ rule.priority }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span v-for="flag in ruleFlags(rule)" :key="flag" class="status-pill status-info">{{ flag }}</span>
                                <span v-if="ruleFlags(rule).length === 0" class="text-xs text-slate-400">Horario o calendario personalizado</span>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button class="icon-button" title="Editar regla" @click="editRule(rule)"><i class="ti ti-edit" aria-hidden="true"></i></button>
                            <button class="icon-button-danger" title="Eliminar regla" @click="removeRule(rule)"><i class="ti ti-trash" aria-hidden="true"></i></button>
                        </div>
                    </article>
                </div>
            </section>
        </div>

        <div v-else class="grid gap-5 xl:grid-cols-[470px_minmax(0,1fr)]">
            <form class="app-panel self-start" @submit.prevent="submitCalendar">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Agregar día especial</h2>
                        <p class="panel-subtitle">Puede ser descanso o día laboral extraordinario.</p>
                    </div>
                </div>
                <div class="space-y-4 p-5">
                    <label class="block"><span class="field-label">Fecha</span><input v-model="calendarForm.date" type="date" class="field-input" required /></label>
                    <label class="block">
                        <span class="field-label">Tipo</span>
                        <select v-model="calendarForm.kind" class="field-input">
                            <option value="non_working">No laborable</option>
                            <option value="working">Laborable extraordinario</option>
                        </select>
                        <span class="help-text">{{ calendarForm.kind === 'non_working' ? 'No se pedirá asistencia y no se generará falta en esta fecha.' : 'Sí se pedirá asistencia en esta fecha aunque normalmente fuera descanso.' }}</span>
                    </label>
                    <label class="block">
                        <span class="field-label">Aplicar a</span>
                        <select v-model="calendarForm.scope_type" class="field-input">
                            <option value="global">Todo el personal</option>
                            <option value="position">Un puesto</option>
                            <option value="employee">Un empleado</option>
                        </select>
                        <span class="help-text">El alcance permite crear un día especial general o solamente para un puesto o persona.</span>
                    </label>
                    <label v-if="calendarForm.scope_type === 'position'" class="block">
                        <span class="field-label">Puesto</span>
                        <select v-model="calendarForm.position" class="field-input" required><option value="">Selecciona...</option><option v-for="position in positions" :key="position">{{ position }}</option></select>
                    </label>
                    <label v-if="calendarForm.scope_type === 'employee'" class="block">
                        <span class="field-label">Empleado</span>
                        <select v-model="calendarForm.empleado_id" class="field-input" required><option value="">Selecciona...</option><option v-for="employee in employees" :key="employee.id" :value="employee.id">#{{ employee.numero_empleado }} · {{ employee.nombre_completo }}</option></select>
                    </label>
                    <label class="block"><span class="field-label">Nombre del día</span><input v-model="calendarForm.name" class="field-input" required placeholder="Ej. Inventario anual" /><span class="help-text">Usa un nombre que explique por qué esta fecha es diferente.</span></label>
                    <label class="block"><span class="field-label">Notas internas</span><textarea v-model="calendarForm.notes" class="field-input" rows="3" placeholder="Motivo o indicaciones opcionales"></textarea></label>
                    <label class="toggle-row"><input v-model="calendarForm.active" type="checkbox" /> <span><strong>Día especial activo</strong><span class="block text-xs font-medium text-slate-500 dark:text-slate-400">Cuando está activo modifica la validación de asistencias y la nómina de esa fecha.</span></span></label>
                    <button class="btn-accent w-full" :disabled="calendarForm.processing"><i class="ti ti-calendar-plus" aria-hidden="true"></i> Guardar día</button>
                </div>
            </form>

            <section class="app-panel min-w-0">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Calendario {{ year }}</h2>
                        <p class="panel-subtitle">{{ calendar.length }} días configurados.</p>
                    </div>
                    <div class="flex gap-2">
                        <input v-model.number="selectedYear" type="number" min="2020" max="2100" class="field-input w-28" aria-label="Año" />
                        <button class="btn-secondary" @click="loadYear">Ver</button>
                    </div>
                </div>
                <div v-if="calendar.length === 0" class="empty-state">No hay excepciones para este año.</div>
                <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                    <article v-for="day in calendar" :key="day.id" class="flex items-center justify-between gap-4 p-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-lg', day.kind === 'working' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700']">
                                <i :class="['ti text-xl', day.kind === 'working' ? 'ti-briefcase' : 'ti-calendar-off']" aria-hidden="true"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-950 dark:text-white">{{ day.name }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ day.date }} · {{ day.scope_type === 'global' ? 'Todo el personal' : (day.empleado?.nombre_completo || day.position) }}</p>
                            </div>
                        </div>
                        <button class="icon-button-danger" title="Eliminar día" @click="removeCalendarDay(day)"><i class="ti ti-trash" aria-hidden="true"></i></button>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.guide-step {
    @apply inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-700 text-xs font-black text-white dark:bg-blue-500 dark:text-slate-950;
}

.help-text {
    @apply mt-1.5 block text-xs font-medium leading-5 text-slate-500 dark:text-slate-400;
}

.rule-option {
    @apply grid cursor-pointer gap-3 py-3 sm:grid-cols-[minmax(0,1fr)_10rem] sm:items-center;
}

.rule-option-icon {
    @apply inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-lg text-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:text-blue-300;
}

.rule-option-select {
    @apply min-w-0;
}

.toggle-row {
    @apply flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200;
}

.toggle-row input {
    @apply rounded border-slate-300 text-blue-700 focus:ring-blue-500;
}

.day-toggle span {
    @apply flex h-9 cursor-pointer items-center justify-center rounded-md border border-slate-200 text-[11px] font-bold text-slate-500 dark:border-slate-700 dark:text-slate-400;
}

.day-toggle input:checked + span {
    @apply border-blue-600 bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-200;
}
</style>
