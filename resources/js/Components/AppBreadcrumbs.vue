<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({ items: { type: Array, default: () => [] } })
const visible = computed(() => props.items.filter(Boolean))
</script>

<template>
    <nav v-if="visible.length" class="app-breadcrumbs" aria-label="Migas de pan">
        <Link :href="route('dashboard')" class="breadcrumb-link" title="Ir al panel">
            <i class="ti ti-home" aria-hidden="true"></i><span class="sr-only">Panel</span>
        </Link>
        <template v-for="(item, index) in visible" :key="`${item.label}-${index}`">
            <i class="ti ti-chevron-right text-xs text-slate-400" aria-hidden="true"></i>
            <Link v-if="item.route && index < visible.length - 1" :href="route(item.route)" class="breadcrumb-link">{{ item.label }}</Link>
            <span v-else class="truncate font-bold text-slate-700 dark:text-slate-200" aria-current="page">{{ item.label }}</span>
        </template>
    </nav>
</template>
