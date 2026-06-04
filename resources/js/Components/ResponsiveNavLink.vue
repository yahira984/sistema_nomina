<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full rounded-lg bg-slate-950 px-4 py-3 text-start text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-teal-500'
        : 'block w-full rounded-lg px-4 py-3 text-start text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-teal-500',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
