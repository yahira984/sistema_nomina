<script setup>
const props = defineProps({
    meta: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['change'])

const go = page => {
    const target = Math.max(1, Math.min(Number(props.meta.last_page || 1), Number(page || 1)))
    if (target !== Number(props.meta.current_page || 1)) emit('change', target)
}
</script>

<template>
    <nav
        v-if="Number(meta.last_page || 1) > 1"
        class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700"
        aria-label="Paginación"
    >
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
            {{ meta.from || 0 }}-{{ meta.to || 0 }} de {{ meta.total || 0 }}
        </p>
        <div class="flex items-center gap-2">
            <button class="btn-secondary px-3 py-2" :disabled="meta.current_page <= 1" @click="go(meta.current_page - 1)">
                <i class="ti ti-chevron-left" aria-hidden="true"></i>
                Anterior
            </button>
            <span class="min-w-20 text-center text-xs font-bold text-slate-700 dark:text-slate-200">
                {{ meta.current_page }} / {{ meta.last_page }}
            </span>
            <button class="btn-secondary px-3 py-2" :disabled="meta.current_page >= meta.last_page" @click="go(meta.current_page + 1)">
                Siguiente
                <i class="ti ti-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </nav>
</template>
