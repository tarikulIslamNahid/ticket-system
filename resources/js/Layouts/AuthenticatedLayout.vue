<script setup>
import { ref } from 'vue';
import { Menu } from 'lucide-vue-next';
import AppSidebar from '@/Components/AppSidebar.vue';
import {
    Sheet,
    SheetContent,
    SheetTrigger,
} from '@/Components/ui/sheet';
import { Button } from '@/Components/ui/button';

const mobileOpen = ref(false);
</script>

<template>
    <div class="flex min-h-svh bg-muted/30">
        <!-- Desktop sidebar -->
        <div class="hidden md:flex">
            <AppSidebar />
        </div>

        <!-- Main content -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Mobile top bar -->
            <header class="flex h-14 items-center gap-3 border-b bg-background px-4 md:hidden">
                <Sheet v-model:open="mobileOpen">
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon">
                            <Menu class="h-5 w-5" />
                            <span class="sr-only">Toggle menu</span>
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-64 bg-sidebar p-0 text-sidebar-foreground">
                        <AppSidebar />
                    </SheetContent>
                </Sheet>
                <span class="font-semibold">Ticket System</span>
            </header>

            <!-- Optional header slot (per page) -->
            <header v-if="$slots.header" class="border-b bg-background">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
