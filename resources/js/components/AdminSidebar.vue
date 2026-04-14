<script setup lang="ts">
import { GraduationCap, LayoutGrid } from 'lucide-vue-next';
import ShellInsetSidebar from '@/components/layout/ShellInsetSidebar.vue';
import admin from '@/routes/admin';
import adminWorkshops from '@/routes/admin/workshops';
import type { NavItem } from '@/types';

const primaryDashboardHref = admin.dashboard.url();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: primaryDashboardHref,
        icon: LayoutGrid,
    },
    {
        title: 'Workshops',
        href: adminWorkshops.index.url(),
        icon: GraduationCap,
    },
];
</script>

<template>
    <ShellInsetSidebar
        :primary-dashboard-href="primaryDashboardHref"
        :main-nav-items="mainNavItems"
    >
        <slot />
    </ShellInsetSidebar>
</template>
