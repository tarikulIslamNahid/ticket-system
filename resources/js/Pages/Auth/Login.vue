<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <Card class="w-full max-w-md shadow-lg">
            <CardHeader class="space-y-1">
                <CardTitle class="text-2xl">Welcome back</CardTitle>
                <CardDescription>
                    Enter your credentials to access the dashboard
                </CardDescription>
            </CardHeader>

            <CardContent>
                <div
                    v-if="status"
                    class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="admin@ticket.test"
                            required
                            autofocus
                            autocomplete="username"
                            :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.email }"
                        />
                        <p
                            v-if="form.errors.email"
                            class="text-sm font-medium text-destructive"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                            :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.password }"
                        />
                        <p
                            v-if="form.errors.password"
                            class="text-sm font-medium text-destructive"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Checkbox id="remember" v-model="form.remember" />
                        <Label
                            for="remember"
                            class="cursor-pointer text-sm font-normal"
                        >
                            Remember me
                        </Label>
                    </div>

                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Signing in…</span>
                        <span v-else>Sign in</span>
                    </Button>
                </form>
            </CardContent>
        </Card>
    </GuestLayout>
</template>
