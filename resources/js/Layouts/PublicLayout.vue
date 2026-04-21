<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ChatWidget from '@/Components/ChatWidget.vue';
</script>

<template>
    <div class="min-h-svh bg-muted/30">
        <header class="border-b bg-background">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-2 font-semibold">
                    <ApplicationLogo class="h-7 w-7 fill-current text-primary" />
                    <span>Ticket System</span>
                </Link>
                <Link
                    :href="route('login')"
                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    Admin sign in
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>

        <footer class="mt-12 border-t py-6">
            <div class="mx-auto max-w-5xl px-4 text-center text-xs text-muted-foreground sm:px-6">
                Need help? Submit a ticket and our team will get back to you shortly.
            </div>
        </footer>

        <ChatWidget />
    </div>
</template>
