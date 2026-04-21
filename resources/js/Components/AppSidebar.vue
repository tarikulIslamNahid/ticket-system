<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    LayoutDashboard,
    Ticket,
    Settings,
    LogOut,
    ChevronsUpDown,
} from 'lucide-vue-next';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import {
    Avatar,
    AvatarFallback,
} from '@/Components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const page = usePage();

const user = computed(() => page.props.auth?.user ?? { name: 'User', email: '' });

const initials = computed(() => {
    const name = user.value.name ?? '';
    return name
        .split(' ')
        .map((w) => w[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase() || 'U';
});

const navItems = [
    {
        label: 'Dashboard',
        icon: LayoutDashboard,
        route: 'dashboard',
        href: '/dashboard',
    },
    {
        label: 'Tickets',
        icon: Ticket,
        route: 'admin.tickets.index',
        href: '/admin/tickets',
    },
    {
        label: 'Settings',
        icon: Settings,
        route: 'settings',
        href: '#',
        disabled: true,
    },
];

const isActive = (item) => {
    try {
        return route().current(item.route);
    } catch {
        return false;
    }
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <aside
        class="flex h-svh w-64 shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground"
    >
        <!-- Header -->
        <div class="flex h-16 items-center gap-2 border-b border-sidebar-border px-6">
            <Link :href="route('dashboard')" class="flex items-center gap-2 font-semibold">
                <ApplicationLogo class="h-7 w-7 fill-current" />
                <span class="text-base">Ticket System</span>
            </Link>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 space-y-1 overflow-y-auto p-3">
            <p class="px-3 py-2 text-xs font-medium uppercase tracking-wider text-sidebar-foreground/60">
                Main
            </p>

            <template v-for="item in navItems" :key="item.label">
                <Link
                    v-if="!item.disabled"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
                        isActive(item)
                            ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                            : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground',
                    ]"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                    <span>{{ item.label }}</span>
                </Link>

                <div
                    v-else
                    class="flex cursor-not-allowed items-center gap-3 rounded-md px-3 py-2 text-sm text-sidebar-foreground/40"
                    :title="`${item.label} (coming soon)`"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                    <span>{{ item.label }}</span>
                    <span class="ml-auto rounded-full bg-sidebar-accent/40 px-2 py-0.5 text-[10px] uppercase tracking-wide">
                        Soon
                    </span>
                </div>
            </template>
        </nav>

        <!-- Footer: User menu -->
        <div class="border-t border-sidebar-border p-3">
            <DropdownMenu>
                <DropdownMenuTrigger
                    class="flex w-full items-center gap-3 rounded-md p-2 text-left text-sm outline-none transition-colors hover:bg-sidebar-accent/60 focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                >
                    <Avatar class="h-8 w-8">
                        <AvatarFallback class="bg-sidebar-primary text-sidebar-primary-foreground">
                            {{ initials }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex-1 overflow-hidden">
                        <p class="truncate font-medium">{{ user.name }}</p>
                        <p class="truncate text-xs text-sidebar-foreground/60">
                            {{ user.email }}
                        </p>
                    </div>
                    <ChevronsUpDown class="h-4 w-4 text-sidebar-foreground/60" />
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" side="top" class="w-56">
                    <DropdownMenuLabel>
                        <div class="flex flex-col">
                            <span class="font-medium">{{ user.name }}</span>
                            <span class="text-xs font-normal text-muted-foreground">
                                {{ user.email }}
                            </span>
                        </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        class="cursor-pointer text-destructive focus:text-destructive"
                        @click="logout"
                    >
                        <LogOut class="mr-2 h-4 w-4" />
                        <span>Log out</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </aside>
</template>
