<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <nav class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="content-wrap">
                <div class="flex h-16 items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-6">
                        <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-teal-300">
                                <ApplicationLogo class="h-8 w-8" />
                            </span>
                            <span class="hidden min-w-0 sm:block">
                                <span class="block truncate text-sm font-semibold text-slate-950">Sistema de Nóminas</span>
                                <span class="block truncate text-xs font-medium text-slate-500">PROMATEC-LUGARTH</span>
                            </span>
                        </Link>

                        <div class="hidden items-center gap-1 md:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Panel
                            </NavLink>
                            <NavLink :href="route('empleados.index')" :active="route().current('empleados.*')">
                                Empleados
                            </NavLink>
                            <NavLink :href="route('asistencias.index')" :active="route().current('asistencias.*')">
                                Asistencias
                            </NavLink>
                            <NavLink :href="route('nominas.index')" :active="route().current('nominas.*')">
                                Nóminas
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden items-center md:flex">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                                >
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-xs font-bold text-teal-700">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </span>
                                    <span class="max-w-40 truncate">{{ $page.props.auth.user.name }}</span>
                                    <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Cerrar sesión
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <button
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="icon-button md:hidden"
                        type="button"
                        aria-label="Abrir navegación"
                    >
                        <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path
                                :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                class="border-t border-slate-200 bg-white px-4 py-4 md:hidden"
            >
                <div class="space-y-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        Panel
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('empleados.index')" :active="route().current('empleados.*')">
                        Empleados
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('asistencias.index')" :active="route().current('asistencias.*')">
                        Asistencias
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('nominas.index')" :active="route().current('nominas.*')">
                        Nóminas
                    </ResponsiveNavLink>
                </div>

                <div class="mt-4 border-t border-slate-200 pt-4">
                    <div class="flex items-center gap-3 px-2">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-sm font-bold text-teal-700">
                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-950">{{ $page.props.auth.user.name }}</div>
                            <div class="truncate text-xs font-medium text-slate-500">{{ $page.props.auth.user.email }}</div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-2">
                        <ResponsiveNavLink :href="route('profile.edit')">Perfil</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Cerrar sesión
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="border-b border-slate-200 bg-white">
            <div class="content-wrap py-6">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
