<script setup>
import { router } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, ref, watch } from 'vue'

const props = defineProps({
    initialOperations: {
        type: Array,
        default: () => [],
    },
    highlightedId: {
        type: String,
        default: '',
    },
})

const open = ref(false)
const operations = ref([...props.initialOperations])
const actionInProgress = ref('')
const toast = ref(null)
const unreadIds = ref(new Set())
let timer = null
let toastTimer = null

const activeCount = computed(() => operations.value.filter(item => ['queued', 'running'].includes(item.status)).length)
const failedCount = computed(() => operations.value.filter(item => item.status === 'failed').length)
const hasFinished = computed(() => operations.value.some(item => !['queued', 'running'].includes(item.status)))
const attentionCount = computed(() => Math.max(unreadIds.value.size, activeCount.value, failedCount.value))

const labels = {
    attendance_import_preview: 'Análisis de CSV',
    attendance_import_approval: 'Aprobación de asistencias',
    mass_export: 'Exportación masiva',
}

const statusLabel = status => ({
    queued: 'En espera',
    running: 'Procesando',
    completed: 'Completada',
    failed: 'Falló',
    consumed: 'Aplicada',
    cancelled: 'Cancelada',
}[status] || status)

const statusClass = status => ({
    queued: 'status-info',
    running: 'status-info',
    completed: 'status-success',
    failed: 'status-danger',
    consumed: 'status-neutral',
    cancelled: 'status-neutral',
}[status] || 'status-neutral')

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const showToast = operation => {
    if (!operation) return

    clearTimeout(toastTimer)
    toast.value = operation
    unreadIds.value = new Set([...unreadIds.value, operation.id])
    toastTimer = setTimeout(() => {
        toast.value = null
    }, ['completed', 'failed'].includes(operation.status) ? 9000 : 5500)
}

const toggleCenter = () => {
    open.value = !open.value

    if (open.value) {
        unreadIds.value = new Set()
        toast.value = null
        clearTimeout(toastTimer)
    }
}

const openFromToast = () => {
    open.value = true
    unreadIds.value = new Set()
    toast.value = null
    clearTimeout(toastTimer)
}

const refresh = async () => {
    try {
        const response = await fetch(route('operaciones.index', { limit: 12 }), {
            headers: { Accept: 'application/json' },
        })

        if (response.ok) {
            const data = await response.json()
            const updatedOperations = data.operations || []
            const previousStatuses = new Map(operations.value.map(item => [item.id, item.status]))

            updatedOperations.forEach(operation => {
                const previousStatus = previousStatuses.get(operation.id)
                if (previousStatus && previousStatus !== operation.status && ['completed', 'failed'].includes(operation.status)) {
                    showToast(operation)
                }
            })

            operations.value = updatedOperations
        }
    } catch (error) {
        // La siguiente actualización recuperará el estado cuando vuelva la conexión.
    }
}

const dismissOperation = async operation => {
    if (['queued', 'running'].includes(operation.status)
        && !window.confirm('¿Cancelar esta operación en curso?')) {
        return
    }

    actionInProgress.value = operation.id

    try {
        const response = await fetch(route('operaciones.dismiss', operation.id), {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        })

        if (response.ok) {
            operations.value = operations.value.filter(item => item.id !== operation.id)
            router.reload({ only: ['systemContext'], preserveScroll: true, preserveState: true })
            schedule()
        }
    } finally {
        actionInProgress.value = ''
    }
}

const dismissFinished = async () => {
    actionInProgress.value = 'all'

    try {
        const response = await fetch(route('operaciones.dismiss-all'), {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        })

        if (response.ok) {
            operations.value = operations.value.filter(item => ['queued', 'running'].includes(item.status))
            router.reload({ only: ['systemContext'], preserveScroll: true, preserveState: true })
        }
    } finally {
        actionInProgress.value = ''
    }
}

const schedule = () => {
    clearTimeout(timer)

    if (activeCount.value > 0) {
        timer = setTimeout(async () => {
            await refresh()
            schedule()
        }, 2500)
    }
}

watch(
    () => props.initialOperations,
    value => {
        operations.value = [...(value || [])]
        schedule()
    },
    { deep: true },
)

watch(() => props.highlightedId, id => {
    if (id) {
        refresh().then(() => {
            showToast(operations.value.find(operation => operation.id === id) || {
                id,
                type: 'mass_export',
                status: 'queued',
                message: 'La tarea quedó registrada y continuará en segundo plano.',
            })
        }).finally(schedule)
    }
}, { immediate: true })

watch(activeCount, schedule, { immediate: true })
onBeforeUnmount(() => {
    clearTimeout(timer)
    clearTimeout(toastTimer)
})
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="topbar-icon"
            title="Centro de operaciones"
            aria-label="Abrir centro de operaciones"
            :aria-expanded="open"
            @click="toggleCenter"
        >
            <i class="ti ti-progress-check text-lg" aria-hidden="true"></i>
            <span v-if="attentionCount" class="notification-count">
                {{ attentionCount }}
            </span>
        </button>

        <button
            v-if="toast"
            type="button"
            class="fixed right-4 top-20 z-[70] flex w-[min(calc(100vw-2rem),360px)] items-start gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-2xl transition dark:border-slate-700 dark:bg-slate-900 sm:right-6"
            @click="openFromToast"
        >
            <span :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-lg', toast.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : toast.status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700']">
                <i :class="['ti text-xl', toast.status === 'completed' ? 'ti-circle-check' : toast.status === 'failed' ? 'ti-alert-circle' : 'ti-loader-2 animate-spin']" aria-hidden="true"></i>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-black text-slate-950 dark:text-white">{{ labels[toast.type] || 'Nueva operación' }}</span>
                <span class="mt-1 block text-xs font-semibold leading-5 text-slate-600 dark:text-slate-300">{{ toast.message }}</span>
                <span class="mt-2 block text-[11px] font-black text-blue-700 dark:text-blue-300">Consultar en el centro de operaciones</span>
            </span>
            <i class="ti ti-chevron-right mt-1 text-slate-400" aria-hidden="true"></i>
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-50 mt-2 w-[min(92vw,390px)] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
        >
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                <div>
                    <p class="text-sm font-extrabold text-slate-950 dark:text-white">Operaciones</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Importaciones, Firebase y archivos</p>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        v-if="hasFinished"
                        type="button"
                        class="icon-button"
                        title="Quitar operaciones terminadas"
                        aria-label="Quitar operaciones terminadas"
                        :disabled="actionInProgress === 'all'"
                        @click="dismissFinished"
                    >
                        <i class="ti ti-trash" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="icon-button" title="Actualizar" aria-label="Actualizar operaciones" @click="refresh">
                        <i class="ti ti-refresh" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="max-h-[420px] overflow-y-auto">
                <div v-if="operations.length === 0" class="empty-state py-10">
                    <i class="ti ti-circle-check mb-2 text-2xl text-emerald-600" aria-hidden="true"></i>
                    <p>No hay procesos recientes.</p>
                </div>

                <article
                    v-for="operation in operations"
                    :key="operation.id"
                    :class="[
                        'border-b border-slate-100 px-4 py-3 last:border-b-0 dark:border-slate-800',
                        operation.id === highlightedId ? 'bg-blue-50 dark:bg-blue-950/30' : '',
                    ]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900 dark:text-white">
                                {{ labels[operation.type] || operation.type }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ operation.message }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <span :class="['status-pill', statusClass(operation.status)]">
                                {{ statusLabel(operation.status) }}
                            </span>
                            <button
                                type="button"
                                class="icon-button !h-7 !w-7"
                                :title="['queued', 'running'].includes(operation.status) ? 'Cancelar operación' : 'Quitar aviso'"
                                :aria-label="['queued', 'running'].includes(operation.status) ? 'Cancelar operación' : 'Quitar aviso'"
                                :disabled="actionInProgress === operation.id"
                                @click="dismissOperation(operation)"
                            >
                                <i class="ti ti-x" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div v-if="['queued', 'running'].includes(operation.status)" class="mt-3">
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div
                                class="h-full rounded-full bg-blue-600 transition-all duration-300"
                                :style="{ width: `${Math.max(3, operation.progress || 0)}%` }"
                            ></div>
                        </div>
                        <p class="mt-1 text-right text-[11px] font-bold text-blue-700 dark:text-blue-300">
                            {{ operation.progress || 0 }}%
                        </p>
                    </div>

                    <a
                        v-if="operation.download_url"
                        :href="operation.download_url"
                        class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-blue-700 hover:text-blue-900 dark:text-blue-300"
                    >
                        <i class="ti ti-download" aria-hidden="true"></i>
                        Descargar {{ operation.download_name }}
                    </a>

                    <p v-if="operation.status === 'failed'" class="mt-2 text-xs font-semibold text-rose-700 dark:text-rose-300">
                        {{ operation.error }}
                    </p>
                </article>
            </div>
        </div>
    </div>
</template>
