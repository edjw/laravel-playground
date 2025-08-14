<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Zap } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const page = usePage();

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
    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form method="POST" @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Log in
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink :href="route('register')" :tabindex="5">Sign up</TextLink>
            </div>
        </form>

        <!-- Development Login Links -->
        <div v-if="page.props.environment === 'local'" class="mt-8 rounded-lg border border-dashed border-muted-foreground/25 p-4">
            <div class="mb-3 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <Zap class="h-4 w-4" />
                Quick Development Login
            </div>
            <div class="space-y-2">
                <form method="post" action="/laravel-login-link-login" class="w-full">
                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                    <input type="hidden" name="email" value="admin@example.com" />
                    <Button type="submit" variant="outline" size="sm" class="w-full justify-start text-xs">
                        Login as Admin (admin@example.com)
                    </Button>
                </form>
                <form method="post" action="/laravel-login-link-login" class="w-full">
                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                    <input type="hidden" name="email" value="user@example.com" />
                    <Button type="submit" variant="outline" size="sm" class="w-full justify-start text-xs"> Login as User (user@example.com) </Button>
                </form>
            </div>
        </div>
    </AuthBase>
</template>
