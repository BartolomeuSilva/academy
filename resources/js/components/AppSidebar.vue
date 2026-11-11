<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Flag,
    GraduationCap,
    MessageSquare,
    MessagesSquare,
    Users,
} from '@lucide/vue';
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
import { useStudentNav } from '@/composables/useStudentNav';
import { index as catalog } from '@/routes/courses';
import { index as adminComments } from '@/routes/admin/comments';
import { index as adminReports } from '@/routes/admin/community/reports';
import { index as adminSpaces } from '@/routes/admin/community/spaces';
import { index as adminCourses } from '@/routes/admin/courses';
import { index as adminUsers } from '@/routes/admin/users';
import type { NavItem } from '@/types';

const catalogUrl = catalog().url;

const mainNavItems = useStudentNav();

const page = usePage();

const adminNavItems = computed<NavItem[]>(() =>
    page.props.auth.user?.role === 'admin'
        ? [
              {
                  title: 'Cursos',
                  href: adminCourses(),
                  icon: GraduationCap,
              },
              {
                  title: 'Comentários',
                  href: adminComments(),
                  icon: MessageSquare,
              },
              {
                  title: 'Espaços da comunidade',
                  href: adminSpaces(),
                  icon: MessagesSquare,
              },
              {
                  title: 'Denúncias',
                  href: adminReports(),
                  icon: Flag,
              },
              {
                  title: 'Usuários',
                  href: adminUsers(),
                  icon: Users,
              },
          ]
        : [],
);
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="catalogUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Seu espaço" />
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
