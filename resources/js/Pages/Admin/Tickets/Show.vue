<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import {
    ArrowLeft,
    Mail,
    Phone,
    Sparkles,
    Send,
    CheckCircle2,
    Radio,
    Lock,
    Unlock,
} from 'lucide-vue-next';

const props = defineProps({
    ticket: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const data = computed(() => props.ticket.data ?? props.ticket);

const replies = ref([...(data.value.replies ?? [])]);
const currentStatus = ref(data.value.status);
const closedAt = ref(data.value.closed_at);
const customerTyping = ref(false);
let typingTimer = null;
let channel = null;

const isClosed = computed(() => currentStatus.value === 'closed');
const statusVariant = computed(() => (isClosed.value ? 'secondary' : 'default'));

const categoryLabel = computed(() => {
    const map = { support: 'Support', billing: 'Billing', other: 'Other' };
    return map[data.value.category] ?? 'Other';
});

const sourceLabel = computed(() => {
    const map = { email: 'Email', chat: 'Chat', form: 'Form' };
    return map[data.value.source] ?? data.value.source;
});

const form = useForm({ message: '' });

const submit = () => {
    form.post(route('admin.tickets.reply', data.value.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('message'),
    });
};

const statusForm = useForm({ status: '' });

const toggleStatus = () => {
    statusForm.status = isClosed.value ? 'open' : 'closed';
    statusForm.patch(route('admin.tickets.status', data.value.id), {
        preserveScroll: true,
    });
};

const useAiSuggestion = () => {
    if (data.value.ai_suggested_reply) {
        form.message = data.value.ai_suggested_reply;
    }
};

// Debounced typing notification to backend
let typingNotifyTimer = null;
const notifyTyping = () => {
    clearTimeout(typingNotifyTimer);
    typingNotifyTimer = setTimeout(() => {
        window.axios
            .post(route('admin.tickets.typing', data.value.id))
            .catch(() => {});
    }, 400);
};

watch(() => form.message, (value) => {
    if (value && value.length > 0) notifyTyping();
});

onMounted(() => {
    if (! window.Echo) return;

    channel = window.Echo.channel(`ticket.${data.value.public_token}`);

    channel.listen('.reply.created', (event) => {
        if (! event?.reply) return;
        // Dedupe by id
        if (! replies.value.some((r) => r.id === event.reply.id)) {
            replies.value.push(event.reply);
        }
    });

    channel.listen('.typing', (event) => {
        if (event?.who === 'customer') {
            customerTyping.value = true;
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                customerTyping.value = false;
            }, 2500);
        }
    });

    channel.listen('.status.changed', (event) => {
        if (event?.status) {
            currentStatus.value = event.status;
            closedAt.value = event.closed_at;
        }
    });
});

onBeforeUnmount(() => {
    clearTimeout(typingTimer);
    clearTimeout(typingNotifyTimer);
    if (channel && window.Echo) {
        window.Echo.leave(`ticket.${data.value.public_token}`);
    }
});

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

const initials = (name) => {
    return (name || '?')
        .split(' ')
        .map((w) => w[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
};
</script>

<template>
    <Head :title="`${data.ticket_number} — ${data.subject || 'Ticket'}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.tickets.index')"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md border transition-colors hover:bg-accent"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span class="sr-only">Back to tickets</span>
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-sm text-muted-foreground">
                            {{ data.ticket_number }}
                        </span>
                        <Badge :variant="statusVariant" class="capitalize">
                            {{ currentStatus }}
                        </Badge>
                        <span
                            class="ml-auto inline-flex items-center gap-1 text-xs text-muted-foreground"
                            title="Live updates enabled"
                        >
                            <Radio class="h-3.5 w-3.5 text-green-500" />
                            Live
                        </span>
                    </div>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight">
                        {{ data.subject || 'Support request' }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="grid gap-6 p-4 sm:p-6 lg:grid-cols-[1fr_320px] lg:p-8">
            <div class="space-y-6">
                <div
                    v-if="flashSuccess"
                    class="flex items-center gap-2 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800"
                >
                    <CheckCircle2 class="h-4 w-4 text-green-600" />
                    {{ flashSuccess }}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Conversation</CardTitle>
                        <CardDescription>
                            Original message and all replies. New replies appear in real time.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                                {{ initials(data.name) }}
                            </div>
                            <div class="flex-1 rounded-lg border bg-background p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium">
                                        {{ data.name }}
                                        <span class="ml-1 text-xs font-normal text-muted-foreground">
                                            (customer)
                                        </span>
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(data.created_at) }}
                                    </p>
                                </div>
                                <p class="mt-2 whitespace-pre-wrap text-sm">
                                    {{ data.message }}
                                </p>
                            </div>
                        </div>

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
                                        : 'bg-muted'"
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
                                                class="ml-1 text-xs font-normal text-muted-foreground"
                                            >
                                                ({{ reply.sender_type === 'admin' ? 'support' : 'customer' }})
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
                            v-else
                            class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                        >
                            No replies yet. Be the first to respond.
                        </div>

                        <!-- Customer typing indicator -->
                        <div
                            v-if="customerTyping"
                            class="flex gap-3"
                        >
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                                {{ initials(data.name) }}
                            </div>
                            <div class="flex-1 rounded-lg border bg-background p-4">
                                <div class="flex items-center gap-1 text-sm text-muted-foreground">
                                    <span>{{ data.name }} is typing</span>
                                    <span class="flex gap-0.5">
                                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-muted-foreground" style="animation-delay: 0ms"></span>
                                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-muted-foreground" style="animation-delay: 150ms"></span>
                                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-muted-foreground" style="animation-delay: 300ms"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-start justify-between space-y-0">
                        <div>
                            <CardTitle class="text-base">Reply</CardTitle>
                            <CardDescription>
                                The customer will see this update instantly.
                            </CardDescription>
                        </div>
                        <Button
                            v-if="data.ai_suggested_reply && !isClosed"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="useAiSuggestion"
                        >
                            <Sparkles class="mr-1 h-3.5 w-3.5" />
                            Use AI suggestion
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="isClosed"
                            class="rounded-md border border-dashed bg-muted/30 p-6 text-center text-sm text-muted-foreground"
                        >
                            This ticket is closed. Reopen it from the Details panel
                            to send a reply.
                        </div>
                        <form v-else class="space-y-3" @submit.prevent="submit">
                            <Textarea
                                v-model="form.message"
                                rows="5"
                                placeholder="Type your reply…"
                                required
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.message }"
                            />
                            <p v-if="form.errors.message" class="text-sm text-destructive">
                                {{ form.errors.message }}
                            </p>
                            <div class="flex justify-end">
                                <Button type="submit" :disabled="form.processing || !form.message.trim()">
                                    <Send class="mr-1 h-4 w-4" />
                                    <span v-if="form.processing">Sending…</span>
                                    <span v-else>Send reply</span>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <aside class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Source
                            </p>
                            <Badge variant="outline" class="mt-1 capitalize">
                                {{ sourceLabel }}
                            </Badge>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Category
                            </p>
                            <Badge variant="secondary" class="mt-1 capitalize">
                                {{ categoryLabel }}
                            </Badge>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Created
                            </p>
                            <p class="mt-1">{{ formatDate(data.created_at) }}</p>
                        </div>
                        <div v-if="closedAt">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Closed
                            </p>
                            <p class="mt-1">{{ formatDate(closedAt) }}</p>
                        </div>
                        <div class="pt-2">
                            <Button
                                type="button"
                                :variant="isClosed ? 'outline' : 'destructive'"
                                class="w-full"
                                :disabled="statusForm.processing"
                                @click="toggleStatus"
                            >
                                <Unlock v-if="isClosed" class="mr-1 h-4 w-4" />
                                <Lock v-else class="mr-1 h-4 w-4" />
                                {{ isClosed ? 'Reopen ticket' : 'Close ticket' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Customer</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Name
                            </p>
                            <p class="mt-1 font-medium">{{ data.name }}</p>
                        </div>
                        <div v-if="data.email">
                            <p class="flex items-center gap-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                <Mail class="h-3 w-3" /> Email
                            </p>
                            <p class="mt-1 break-all">{{ data.email }}</p>
                        </div>
                        <div v-if="data.phone">
                            <p class="flex items-center gap-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                <Phone class="h-3 w-3" /> Phone
                            </p>
                            <p class="mt-1">{{ data.phone }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="data.ai_suggested_reply">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Sparkles class="h-4 w-4 text-primary" />
                            AI suggested reply
                        </CardTitle>
                        <CardDescription>
                            Draft generated by Gemini. Edit before sending.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="whitespace-pre-wrap rounded-md bg-muted/50 p-3 text-sm">
                            {{ data.ai_suggested_reply }}
                        </p>
                    </CardContent>
                </Card>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
