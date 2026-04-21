<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Badge } from '@/Components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { CheckCircle2, Mail, Phone, ArrowLeft, Radio } from 'lucide-vue-next';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const data = computed(() => props.ticket.data ?? props.ticket);

const replies = ref([...(data.value.replies ?? [])]);
const adminTyping = ref(false);
let typingTimer = null;
let channel = null;

const statusLabel = computed(() => (data.value.status === 'closed' ? 'Closed' : 'Open'));
const statusVariant = computed(() => (data.value.status === 'closed' ? 'secondary' : 'default'));

const categoryLabel = computed(() => {
    const map = { support: 'Support', billing: 'Billing', other: 'Other' };
    return map[data.value.category] ?? 'Other';
});

const formatDate = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const initials = (name) => (name || '?')
    .split(' ')
    .map((w) => w[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase();

onMounted(() => {
    if (! window.Echo) return;

    channel = window.Echo.channel(`ticket.${data.value.public_token}`);

    channel.listen('.reply.created', (event) => {
        if (! event?.reply) return;
        if (! replies.value.some((r) => r.id === event.reply.id)) {
            replies.value.push(event.reply);
        }
    });

    channel.listen('.typing', (event) => {
        if (event?.who === 'admin') {
            adminTyping.value = true;
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                adminTyping.value = false;
            }, 2500);
        }
    });
});

onBeforeUnmount(() => {
    clearTimeout(typingTimer);
    if (channel && window.Echo) {
        window.Echo.leave(`ticket.${data.value.public_token}`);
    }
});
</script>

<template>
    <Head :title="`${data.ticket_number} — Ticket`" />

    <PublicLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex items-center justify-between">
                <Link
                    :href="route('contact.create')"
                    class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Submit another ticket
                </Link>
                <span
                    class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                    title="Live updates enabled"
                >
                    <Radio class="h-3.5 w-3.5 text-green-500" />
                    Live
                </span>
            </div>

            <div
                v-if="flashSuccess"
                class="flex items-start gap-3 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800"
            >
                <CheckCircle2 class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600" />
                <div>
                    <p class="font-medium">{{ flashSuccess }}</p>
                    <p class="mt-1 text-green-700/80">
                        Keep this page open — admin replies will appear here in real time.
                    </p>
                </div>
            </div>

            <Card>
                <CardHeader class="space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ data.ticket_number }}
                            </p>
                            <CardTitle class="mt-1 text-xl">
                                {{ data.subject || 'Support request' }}
                            </CardTitle>
                            <CardDescription class="mt-1">
                                Submitted {{ formatDate(data.created_at) }}
                            </CardDescription>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <Badge :variant="statusVariant" class="capitalize">
                                {{ statusLabel }}
                            </Badge>
                            <Badge variant="outline" class="capitalize">
                                {{ categoryLabel }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div class="grid gap-3 rounded-md border bg-muted/30 p-4 text-sm sm:grid-cols-[auto_1fr]">
                        <span class="font-medium text-muted-foreground">From</span>
                        <span>{{ data.name }}</span>

                        <span v-if="data.email" class="flex items-center gap-1 text-muted-foreground">
                            <Mail class="h-3.5 w-3.5" /> Email
                        </span>
                        <span v-if="data.email">{{ data.email }}</span>

                        <span v-if="data.phone" class="flex items-center gap-1 text-muted-foreground">
                            <Phone class="h-3.5 w-3.5" /> Phone
                        </span>
                        <span v-if="data.phone">{{ data.phone }}</span>
                    </div>

                    <div class="space-y-4">
                        <!-- Original message -->
                        <div class="flex gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold uppercase text-primary">
                                {{ initials(data.name) }}
                            </div>
                            <div class="flex-1 rounded-lg border bg-background p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium">{{ data.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(data.created_at) }}
                                    </p>
                                </div>
                                <p class="mt-2 whitespace-pre-wrap text-sm">
                                    {{ data.message }}
                                </p>
                            </div>
                        </div>

                        <!-- Replies (live-updated) -->
                        <template v-if="replies.length">
                            <div
                                v-for="reply in replies"
                                :key="reply.id"
                                class="flex gap-3"
                                :class="{ 'flex-row-reverse': reply.sender_type === 'admin' }"
                            >
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                                    :class="reply.sender_type === 'admin'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-muted-foreground'"
                                >
                                    {{ initials(reply.author_name) }}
                                </div>
                                <div
                                    class="flex-1 rounded-lg p-4"
                                    :class="reply.sender_type === 'admin'
                                        ? 'bg-primary/5 border border-primary/20'
                                        : 'bg-background border'"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-medium">
                                            {{ reply.author_name }}
                                            <span
                                                v-if="reply.sender_type === 'admin'"
                                                class="ml-1 rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-primary"
                                            >
                                                Support
                                            </span>
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDate(reply.created_at) }}
                                        </p>
                                    </div>
                                    <p class="mt-2 whitespace-pre-wrap text-sm">
                                        {{ reply.message }}
                                    </p>
                                </div>
                            </div>
                        </template>

                        <div
                            v-if="!replies.length && !adminTyping"
                            class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                        >
                            Waiting for a reply from our team. Updates appear here automatically.
                        </div>

                        <!-- Admin typing indicator -->
                        <div v-if="adminTyping" class="flex flex-row-reverse gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                                S
                            </div>
                            <div class="flex-1 rounded-lg border border-primary/20 bg-primary/5 p-4">
                                <div class="flex items-center gap-1 text-sm text-muted-foreground">
                                    <span>Support is typing</span>
                                    <span class="flex gap-0.5">
                                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-primary" style="animation-delay: 0ms"></span>
                                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-primary" style="animation-delay: 150ms"></span>
                                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-primary" style="animation-delay: 300ms"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
