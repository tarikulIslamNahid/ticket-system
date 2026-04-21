<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

const submit = () => {
    form.post(route('contact.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Submit a ticket" />

    <PublicLayout>
        <div class="grid gap-8 lg:grid-cols-[1fr_2fr]">
            <!-- Intro panel -->
            <div class="space-y-4">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight">
                        How can we help?
                    </h1>
                    <p class="mt-2 text-muted-foreground">
                        Send us a message and we'll convert it to a ticket right away.
                    </p>
                </div>

                <div class="space-y-3 rounded-lg border bg-background p-4 text-sm">
                    <div>
                        <p class="font-medium">What happens next</p>
                        <ol class="mt-2 list-decimal space-y-1 pl-4 text-muted-foreground">
                            <li>Your message becomes a ticket (TKT-XXXXX).</li>
                            <li>We notify you by email with a tracking link.</li>
                            <li>Our team replies and updates the status.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Form card -->
            <Card>
                <CardHeader>
                    <CardTitle>Contact form</CardTitle>
                    <CardDescription>
                        All fields marked with * are required.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <Label for="name">Name *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                autocomplete="name"
                                placeholder="Jane Doe"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="email"
                                    placeholder="you@example.com"
                                    :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="text-sm text-destructive">
                                    {{ form.errors.email }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    autocomplete="tel"
                                    placeholder="+1 555 123 4567"
                                    :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.phone }"
                                />
                                <p v-if="form.errors.phone" class="text-sm text-destructive">
                                    {{ form.errors.phone }}
                                </p>
                            </div>
                        </div>
                        <p class="-mt-2 text-xs text-muted-foreground">
                            Provide either email or phone so we can reach you.
                        </p>

                        <div class="space-y-2">
                            <Label for="subject">Subject</Label>
                            <Input
                                id="subject"
                                v-model="form.subject"
                                placeholder="Quick summary of your issue"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.subject }"
                            />
                            <p v-if="form.errors.subject" class="text-sm text-destructive">
                                {{ form.errors.subject }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="message">Message *</Label>
                            <Textarea
                                id="message"
                                v-model="form.message"
                                rows="6"
                                required
                                placeholder="Describe your issue or question in detail (at least 10 characters)"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.message }"
                            />
                            <p v-if="form.errors.message" class="text-sm text-destructive">
                                {{ form.errors.message }}
                            </p>
                        </div>

                        <Button
                            type="submit"
                            class="w-full sm:w-auto"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Sending…</span>
                            <span v-else>Submit ticket</span>
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
