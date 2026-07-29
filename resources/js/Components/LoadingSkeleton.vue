<script setup>
defineProps({
    rows: { type: Number, default: 4 },
})
</script>

<template>
    <div class="animate-pulse space-y-3" role="status" aria-label="Cargando contenido">
        <div v-for="row in rows" :key="row" class="h-14 rounded-lg bg-slate-200/80 dark:bg-slate-700/70"></div>
        <span class="sr-only">Cargando...</span>
    </div>
</template>
