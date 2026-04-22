<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/Components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import {
    Ticket as TicketIcon,
    Inbox,
    CheckCircle2,
    Clock,
    ArrowRight,
    MessageSquare,
} from 'lucide-vue-next';

const props = defineProps({
    stats: { type: Object, required: true },
    recentTickets: { type: Object, required: true },
});

const recent = computed(() => props.recentTickets.data ?? props.recentTickets);

const statCards = computed(() => [
    {
        label: 'Total tickets',
        value: props.stats.total,
        icon: TicketIcon,
        description: 'All tickets received',
    },
    {
        label: 'Open',
        value: props.stats.open,
        icon: Inbox,
        description: 'Awaiting response',
    },
    {
        label: 'Closed',
        value: props.stats.closed,
        icon: CheckCircle2,
        description: 'Resolved tickets',
    },
    {
        label: 'Last 24h',
        value: props.stats.last_24h,
        icon: Clock,
        description: 'New in the past 24 hours',
    },
]);

const bySource = computed(() => {
    const labels = { form: 'Form', chat: 'Chat', email: 'Email' };
    return Object.entries(props.stats.by_source ?? {})
        .map(([key, count]) => ({
            key,
            label: labels[key] ?? key,
            count,
            share: props.stats.total ? Math.round((count / props.stats.total) * 100) : 0,
        }))
        .sort((a, b) => b.count - a.count);
});

const byCategory = computed(() => {
    const labels = { support: 'Support', billing: 'Billing', other: 'Other' };
    return Object.entries(props.stats.by_category ?? {})
        .map(([key, count]) => ({
            key,
            label: labels[key] ?? key,
            count,
            share: props.stats.total ? Math.round((count / props.stats.total) * 100) : 0,
        }))
        .sort((a, b) => b.count - a.count);
});

const statusVariant = (status) => (status === 'closed' ? 'secondary' : 'default');

const sourceLabel = (s) => {
    const map = { form: 'Form', chat: 'Chat', email: 'Email' };
    return map[s] ?? s;
};

const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-semibold tracking-tight">Dashboard</h2>
                <p class="text-sm text-muted-foreground">
                    Overview of ticket activity across all channels.
                </p>
            </div>
        </template>

        <div class="space-y-6 p-4 sm:p-6 lg:p-8">
            <!-- Stat cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in statCards" :key="stat.label">
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

            <!-- Main grid -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Recent tickets -->
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <div>
                            <CardTitle>Recent tickets</CardTitle>
                            <CardDescription>
                                Latest {{ recent.length }} tickets across all channels
                            </CardDescription>
                        </div>
                        <Link
                            :href="route('admin.tickets.index')"
                            class="inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            View all
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recent.length === 0" class="flex flex-col items-center gap-2 py-12 text-center">
                            <Inbox class="h-10 w-10 text-muted-foreground/40" />
                            <p class="text-sm font-medium">No tickets yet</p>
                            <p class="text-xs text-muted-foreground">
                                Tickets will appear here once customers start messaging.
                            </p>
                        </div>

                        <ul v-else class="divide-y">
                            <li
                                v-for="ticket in recent"
                                :key="ticket.id"
                                class="group"
                            >
                                <Link
                                    :href="route('admin.tickets.show', ticket.id)"
                                    class="flex flex-col gap-2 py-3 transition-colors group-first:pt-0 group-last:pb-0 hover:bg-muted/40 sm:flex-row sm:items-center sm:gap-4"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs text-muted-foreground">
                                                {{ ticket.ticket_number }}
                                            </span>
                                            <Badge :variant="statusVariant(ticket.status)" class="capitalize">
                                                {{ ticket.status }}
                                            </Badge>
                                        </div>
                                        <p class="mt-1 truncate text-sm font-medium">
                                            {{ ticket.subject || ticket.message }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ ticket.name }} · {{ sourceLabel(ticket.source) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                        <span class="inline-flex items-center gap-1">
                                            <MessageSquare class="h-3.5 w-3.5" />
                                            {{ ticket.replies_count ?? 0 }}
                                        </span>
                                        <span class="whitespace-nowrap">
                                            {{ formatDate(ticket.created_at) }}
                                        </span>
                                    </div>
                                </Link>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <!-- Breakdown cards -->
                <div class="space-y-4">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-base">By source</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-if="bySource.length === 0"
                                class="py-4 text-center text-xs text-muted-foreground"
                            >
                                No data
                            </div>
                            <div
                                v-for="item in bySource"
                                :key="item.key"
                                class="space-y-1"
                            >
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">{{ item.label }}</span>
                                    <span class="text-muted-foreground">
                                        {{ item.count }}
                                        <span class="ml-1 text-xs">({{ item.share }}%)</span>
                                    </span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-primary transition-all"
                                        :style="{ width: item.share + '%' }"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-base">By category</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-if="byCategory.length === 0"
                                class="py-4 text-center text-xs text-muted-foreground"
                            >
                                No data
                            </div>
                            <div
                                v-for="item in byCategory"
                                :key="item.key"
                                class="space-y-1"
                            >
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">{{ item.label }}</span>
                                    <span class="text-muted-foreground">
                                        {{ item.count }}
                                        <span class="ml-1 text-xs">({{ item.share }}%)</span>
                                    </span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-primary transition-all"
                                        :style="{ width: item.share + '%' }"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
