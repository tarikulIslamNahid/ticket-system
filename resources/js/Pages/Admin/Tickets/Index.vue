<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { Inbox, Search, MessageSquare } from 'lucide-vue-next';

const props = defineProps({
    tickets: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const reload = () => {
    router.get(
        route('admin.tickets.index'),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
const debouncedSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(reload, 300);
};

watch(search, () => debouncedSearch());
watch(status, () => reload());

const statusVariant = (value) => (value === 'closed' ? 'secondary' : 'default');

const categoryLabel = (value) => {
    const map = { support: 'Support', billing: 'Billing', other: 'Other' };
    return map[value] ?? 'Other';
};

const sourceLabel = (value) => {
    const map = { email: 'Email', chat: 'Chat', form: 'Form' };
    return map[value] ?? value;
};

const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Tickets" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-semibold tracking-tight">Tickets</h2>
                <p class="text-sm text-muted-foreground">
                    Manage all customer tickets from a single place.
                </p>
            </div>
        </template>

        <div class="space-y-4 p-4 sm:p-6 lg:p-8">
            <!-- Filters -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Search by ticket #, name, email, subject…"
                        class="pl-9"
                    />
                </div>
                <select
                    v-model="status"
                    class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <option
                        v-for="opt in statusOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
            </div>

            <!-- Table -->
            <div class="rounded-md border bg-background">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-28">Ticket #</TableHead>
                            <TableHead>Customer</TableHead>
                            <TableHead>Subject</TableHead>
                            <TableHead>Source</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Created</TableHead>
                            <TableHead class="w-20 text-right">Replies</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="ticket in tickets.data"
                            :key="ticket.id"
                            class="cursor-pointer transition-colors hover:bg-muted/50"
                            @click="router.get(route('admin.tickets.show', ticket.id))"
                        >
                            <TableCell class="font-mono text-xs font-medium">
                                {{ ticket.ticket_number }}
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ ticket.name }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ ticket.email || ticket.phone || '—' }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="max-w-xs truncate text-sm">
                                {{ ticket.subject || ticket.message }}
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline" class="capitalize">
                                    {{ sourceLabel(ticket.source) }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary" class="capitalize">
                                    {{ categoryLabel(ticket.category) }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="statusVariant(ticket.status)" class="capitalize">
                                    {{ ticket.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right text-xs text-muted-foreground">
                                {{ formatDate(ticket.created_at) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <span class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                    <MessageSquare class="h-3.5 w-3.5" />
                                    {{ ticket.replies_count ?? 0 }}
                                </span>
                            </TableCell>
                        </TableRow>

                        <!-- Empty state -->
                        <TableRow v-if="!tickets.data.length">
                            <TableCell colspan="8" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Inbox class="h-10 w-10 text-muted-foreground/40" />
                                    <p class="text-sm font-medium">No tickets found</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ search || status
                                            ? 'Try adjusting your filters.'
                                            : 'Tickets will appear here once customers submit messages.' }}
                                    </p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div
                v-if="tickets.meta && tickets.meta.last_page > 1"
                class="flex items-center justify-between text-sm"
            >
                <p class="text-muted-foreground">
                    Showing {{ tickets.meta.from ?? 0 }}–{{ tickets.meta.to ?? 0 }} of {{ tickets.meta.total }}
                </p>
                <div class="flex gap-1">
                    <Link
                        v-for="link in tickets.meta.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        preserve-scroll
                        preserve-state
                        :class="[
                            'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition-colors',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : link.url
                                    ? 'border hover:bg-accent hover:text-accent-foreground'
                                    : 'cursor-not-allowed border text-muted-foreground opacity-50',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
