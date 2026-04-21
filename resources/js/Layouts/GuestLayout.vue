<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-muted/50 p-4 sm:p-6">
        <div class="mb-6 flex flex-col items-center gap-2 text-center">
            <Link href="/" class="flex items-center gap-2 font-semibold">
                <ApplicationLogo class="h-10 w-10 fill-current text-primary" />
                <span class="text-xl">Ticket System</span>
            </Link>
            <p class="text-sm text-muted-foreground">
                Admin portal for managing customer tickets
            </p>
        </div>

        <slot />
    </div>
</template>
