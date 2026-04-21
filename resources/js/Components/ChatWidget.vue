<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { MessageCircle, X, Send, Radio } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

const STORAGE_KEY = 'ticket_chat_token';

const page = usePage();
const hiddenOnPage = computed(() => {
    const url = page.url || '';
    return typeof url === 'string' && url.startsWith('/ticket/');
});

const isOpen = ref(false);
const phase = ref('form');
const loading = ref(false);
const errors = ref({});

const formData = ref({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

const ticket = ref(null);
const messages = ref([]);
const replyMessage = ref('');
const adminTyping = ref(false);

const scrollRef = useTemplateRef('scrollRef');
let typingTimer = null;
let typingNotifyTimer = null;
let channel = null;

const token = computed(() => ticket.value?.public_token);
const isClosed = computed(() => ticket.value?.status === 'closed');

onMounted(async () => {
    const storedToken = localStorage.getItem(STORAGE_KEY);
    if (! storedToken) return;

    try {
        const { data } = await window.axios.get(`/chat/${storedToken}`);
        ticket.value = data.ticket;
        messages.value = ticket.value.replies ?? [];
        phase.value = 'chat';
        subscribe();
        scrollToBottom();
    } catch {
        localStorage.removeItem(STORAGE_KEY);
    }
});

onBeforeUnmount(cleanup);

function subscribe() {
    if (! window.Echo || ! token.value) return;

    channel = window.Echo.channel(`ticket.${token.value}`);

    channel.listen('.reply.created', (event) => {
        if (! event?.reply) return;
        if (! messages.value.some((m) => m.id === event.reply.id)) {
            messages.value.push(event.reply);
            scrollToBottom();
        }
    });

    channel.listen('.typing', (event) => {
        if (event?.who === 'admin') {
            adminTyping.value = true;
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                adminTyping.value = false;
            }, 2500);
            scrollToBottom();
        }
    });

    channel.listen('.status.changed', (event) => {
        if (event?.status && ticket.value) {
            ticket.value = { ...ticket.value, status: event.status };
        }
    });
}

function cleanup() {
    clearTimeout(typingTimer);
    clearTimeout(typingNotifyTimer);
    if (channel && window.Echo && token.value) {
        window.Echo.leave(`ticket.${token.value}`);
        channel = null;
    }
}

async function startChat() {
    loading.value = true;
    errors.value = {};
    try {
        const { data } = await window.axios.post('/chat/start', formData.value);
        ticket.value = data.ticket;
        messages.value = [];
        localStorage.setItem(STORAGE_KEY, ticket.value.public_token);
        phase.value = 'chat';
        subscribe();
        scrollToBottom();
    } catch (e) {
        errors.value = e.response?.data?.errors ?? {};
    } finally {
        loading.value = false;
    }
}

async function sendReply() {
    if (! replyMessage.value.trim() || isClosed.value) return;

    loading.value = true;
    try {
        const { data } = await window.axios.post(`/chat/${token.value}/reply`, {
            message: replyMessage.value.trim(),
        });
        if (data.reply && ! messages.value.some((m) => m.id === data.reply.id)) {
            messages.value.push(data.reply);
        }
        replyMessage.value = '';
        scrollToBottom();
    } catch {
        // broadcast will deliver it via channel anyway; keep silent for MVP
    } finally {
        loading.value = false;
    }
}

function notifyTyping() {
    clearTimeout(typingNotifyTimer);
    typingNotifyTimer = setTimeout(() => {
        window.axios.post(`/chat/${token.value}/typing`).catch(() => {});
    }, 400);
}

watch(replyMessage, (value) => {
    if (value && token.value && ! isClosed.value) notifyTyping();
});

async function scrollToBottom() {
    await nextTick();
    if (scrollRef.value) {
        scrollRef.value.scrollTop = scrollRef.value.scrollHeight;
    }
}

function startNewChat() {
    cleanup();
    localStorage.removeItem(STORAGE_KEY);
    ticket.value = null;
    messages.value = [];
    formData.value = { name: '', email: '', phone: '', subject: '', message: '' };
    errors.value = {};
    phase.value = 'form';
}

const initials = (name) => (name || '?')
    .split(' ')
    .map((w) => w[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase();

const formatTime = (iso) => {
    if (! iso) return '';
    const d = new Date(iso);
    return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <template v-if="! hiddenOnPage">
        <button
            v-if="! isOpen"
            type="button"
            class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
            aria-label="Open support chat"
            @click="isOpen = true"
        >
            <MessageCircle class="h-6 w-6" />
        </button>

        <div
            v-if="isOpen"
            class="fixed bottom-6 right-6 z-40 flex h-[540px] w-[22rem] max-w-[calc(100vw-3rem)] flex-col rounded-lg border bg-background shadow-2xl sm:w-96"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b bg-primary px-4 py-3 text-primary-foreground">
                <div class="flex items-center gap-2">
                    <MessageCircle class="h-5 w-5" />
                    <div>
                        <p class="text-sm font-semibold">Support chat</p>
                        <p class="text-xs text-primary-foreground/80 flex items-center gap-1">
                            <Radio class="h-3 w-3" />
                            Live
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded p-1 transition-colors hover:bg-primary-foreground/10"
                    aria-label="Close chat"
                    @click="isOpen = false"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Form phase -->
            <div v-if="phase === 'form'" class="flex-1 space-y-3 overflow-y-auto p-4">
                <p class="text-sm text-muted-foreground">
                    Start a conversation. Our team will reply here and by email.
                </p>
                <div class="space-y-1.5">
                    <Label for="chat-name">Name</Label>
                    <Input id="chat-name" v-model="formData.name" required />
                    <p v-if="errors.name" class="text-xs text-destructive">
                        {{ errors.name[0] }}
                    </p>
                </div>
                <div class="space-y-1.5">
                    <Label for="chat-email">Email</Label>
                    <Input id="chat-email" v-model="formData.email" type="email" />
                    <p v-if="errors.email" class="text-xs text-destructive">
                        {{ errors.email[0] }}
                    </p>
                </div>
                <div class="space-y-1.5">
                    <Label for="chat-phone">Phone</Label>
                    <Input id="chat-phone" v-model="formData.phone" />
                    <p v-if="errors.phone" class="text-xs text-destructive">
                        {{ errors.phone[0] }}
                    </p>
                </div>
                <div class="space-y-1.5">
                    <Label for="chat-msg">First message</Label>
                    <Textarea
                        id="chat-msg"
                        v-model="formData.message"
                        rows="3"
                        required
                    />
                    <p v-if="errors.message" class="text-xs text-destructive">
                        {{ errors.message[0] }}
                    </p>
                </div>
                <Button class="w-full" :disabled="loading" @click="startChat">
                    <span v-if="loading">Starting chat…</span>
                    <span v-else>Start chat</span>
                </Button>
            </div>

            <!-- Chat phase -->
            <template v-if="phase === 'chat' && ticket">
                <div ref="scrollRef" class="flex-1 space-y-3 overflow-y-auto p-4">
                    <div class="flex items-center justify-between rounded-md bg-muted/50 p-2 text-xs">
                        <span class="font-mono text-muted-foreground">
                            {{ ticket.ticket_number }}
                        </span>
                        <button
                            type="button"
                            class="text-xs text-muted-foreground underline transition-colors hover:text-foreground"
                            @click="startNewChat"
                        >
                            New chat
                        </button>
                    </div>

                    <!-- Original message -->
                    <div class="flex gap-2">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                            {{ initials(ticket.name) }}
                        </div>
                        <div class="max-w-[80%] rounded-lg bg-muted p-3 text-sm">
                            {{ ticket.message }}
                        </div>
                    </div>

                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="flex gap-2"
                        :class="{ 'flex-row-reverse': msg.sender_type === 'admin' }"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                            :class="msg.sender_type === 'admin'
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted'"
                        >
                            {{ initials(msg.author_name) }}
                        </div>
                        <div
                            class="max-w-[80%] rounded-lg p-3 text-sm"
                            :class="msg.sender_type === 'admin'
                                ? 'border border-primary/20 bg-primary/5'
                                : 'bg-muted'"
                        >
                            <p class="whitespace-pre-wrap">{{ msg.message }}</p>
                            <p class="mt-1 text-[10px] text-muted-foreground">
                                {{ formatTime(msg.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="adminTyping" class="flex flex-row-reverse gap-2">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                            S
                        </div>
                        <div class="rounded-lg border border-primary/20 bg-primary/5 px-3 py-2 text-xs text-muted-foreground">
                            <span>Support is typing</span>
                            <span class="ml-1 inline-flex gap-0.5">
                                <span class="h-1 w-1 animate-bounce rounded-full bg-primary" style="animation-delay: 0ms"></span>
                                <span class="h-1 w-1 animate-bounce rounded-full bg-primary" style="animation-delay: 150ms"></span>
                                <span class="h-1 w-1 animate-bounce rounded-full bg-primary" style="animation-delay: 300ms"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Reply / closed state -->
                <div class="border-t p-3">
                    <div
                        v-if="isClosed"
                        class="rounded-md bg-muted/50 p-2 text-center text-xs text-muted-foreground"
                    >
                        This ticket is closed.
                        <button
                            type="button"
                            class="ml-1 underline"
                            @click="startNewChat"
                        >
                            Start a new chat
                        </button>
                    </div>
                    <form v-else class="flex gap-2" @submit.prevent="sendReply">
                        <Input
                            v-model="replyMessage"
                            placeholder="Type a message…"
                            class="flex-1"
                            :disabled="loading"
                        />
                        <Button
                            type="submit"
                            size="icon"
                            :disabled="loading || ! replyMessage.trim()"
                        >
                            <Send class="h-4 w-4" />
                        </Button>
                    </form>
                </div>
            </template>
        </div>
    </template>
</template>
