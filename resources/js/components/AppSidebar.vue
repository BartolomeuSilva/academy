<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { GraduationCap, LayoutGrid } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as adminCourses } from '@/routes/admin/courses';
import type { NavItem } from '@/types';

const dashboardUrl = dashboard().url;

const mainNavItems: NavItem[] = [
    {
        title: 'Início',
        href: dashboardUrl,
        icon: LayoutGrid,
    },
];

const page = usePage();

const adminNavItems = computed<NavItem[]>(() =>
    page.props.auth.user?.role === 'admin'
        ? [
              {
                  title: 'Cursos',
                  href: adminCourses(),
                  icon: GraduationCap,
              },
          ]
        : [],
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain
                v-if="adminNavItems.length"
                :items="adminNavItems"
                label="Administração"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
