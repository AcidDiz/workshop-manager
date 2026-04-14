<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { GraduationCap, LayoutGrid } from 'lucide-vue-next';
import { computed } from 'vue';
import ShellInsetSidebar from '@/components/layout/ShellInsetSidebar.vue';
import { dashboard } from '@/routes';
import app from '@/routes/app';
import appWorkshops from '@/routes/app/workshops';
import type { NavItem } from '@/types';

const page = usePage();

const primaryDashboardHref = computed(() =>
    page.props.auth.workshop_permissions.view
        ? app.dashboard.url()
        : dashboard.url(),
);

const mainNavItems = computed<NavItem[]>(() => {
    const href = primaryDashboardHref.value;

    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href,
            icon: LayoutGrid,
        },
    ];

    if (page.props.auth.workshop_permissions.view) {
        items.push({
            title: 'Workshops',
            href: appWorkshops.index.url(),
            icon: GraduationCap,
        });
    }

    return items;
});
</script>

<template>
    <ShellInsetSidebar
        :primary-dashboard-href="primaryDashboardHref"
        :main-nav-items="mainNavItems"
    >
        <slot />
    </ShellInsetSidebar>
</template>
