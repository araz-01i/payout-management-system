<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Users, UserCheck, DollarSign } from '@lucide/vue';
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
import { index as usersIndex } from '@/routes/users';
import { index as employeesIndex } from '@/routes/employees';
import { index as payoutsIndex } from '@/routes/payouts';
import type { NavItem } from '@/types';

const page = usePage();
const can = computed(() => (page.props as any).can ?? {});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    items.push({
        title: 'Users',
        href: usersIndex.url(),
        icon: Users,
    });

    // Add Employees menu item if user has permission
    if (can.value.manage_employees) {
        items.push({
            title: 'Employees',
            href: employeesIndex.url(),
            icon: UserCheck,
        });
    }

    // Add Payouts menu item if user has permission
    if (can.value.view_payouts) {
        items.push({
            title: 'Payouts',
            href: payoutsIndex.url(),
            icon: DollarSign,
        });
    }

    return items;
});


</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
