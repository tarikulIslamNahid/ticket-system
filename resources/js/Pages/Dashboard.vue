<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import {
    Ticket,
    Inbox,
    CheckCircle2,
    Clock,
} from 'lucide-vue-next';

const stats = [
    {
        label: 'Total Tickets',
        value: '—',
        icon: Ticket,
        description: 'All tickets received',
    },
    {
        label: 'Open',
        value: '—',
        icon: Inbox,
        description: 'Awaiting response',
    },
    {
        label: 'Closed',
        value: '—',
        icon: CheckCircle2,
        description: 'Resolved tickets',
    },
    {
        label: 'Today',
        value: '—',
        icon: Clock,
        description: 'New today',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Overview of your ticket activity
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6 p-4 sm:p-6 lg:p-8">
            <!-- Stats grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.label">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            {{ stat.label }}
                        </CardTitle>
                        <component :is="stat.icon" class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stat.value }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stat.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent activity placeholder -->
            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Recent Tickets</CardTitle>
                        <CardDescription>
                            Latest customer messages converted to tickets
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center justify-center gap-2 py-12 text-center">
                            <Inbox class="h-10 w-10 text-muted-foreground/40" />
                            <p class="text-sm font-medium">No tickets yet</p>
                            <p class="text-xs text-muted-foreground">
                                Tickets will appear here once customers start messaging.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                        <CardDescription>
                            Common admin tasks
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="rounded-md border bg-muted/30 p-3">
                            <p class="font-medium">Ticket management</p>
                            <p class="text-xs text-muted-foreground">
                                Coming soon — reply, change status, and more.
                            </p>
                        </div>
                        <div class="rounded-md border bg-muted/30 p-3">
                            <p class="font-medium">AI auto-categorize</p>
                            <p class="text-xs text-muted-foreground">
                                Support / Billing / Other auto-tagging.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
