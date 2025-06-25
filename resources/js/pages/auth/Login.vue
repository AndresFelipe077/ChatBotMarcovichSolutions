<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Head, router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';

// Definir las props
const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

// Estado del formulario
interface FormData {
    email: string;
    password: string;
    remember: boolean;
    errors: Record<string, string>;
    processing: boolean;
}

const form = ref<FormData>({
    email: '',
    password: '',
    remember: false,
    errors: {},
    processing: false
});

// Cargar credenciales guardadas al montar el componente
onMounted(() => {
    const savedEmail = localStorage.getItem('saved_email');
    if (savedEmail) {
        form.value.email = savedEmail;
        form.value.remember = true;
    }
});

const submit = async () => {
    if (form.value.processing) return;

    form.value.processing = true;
    form.value.errors = {};

    try {
        await router.post('/login', {
            email: form.value.email,
            password: form.value.password,
            remember: form.value.remember
        }, {
            onSuccess: () => {
                // router.visit('/dashboard', { replace: true });
            },
            onError: (errors) => {
                if (errors.email) {
                    form.value.errors.email = Array.isArray(errors.email) ? errors.email[0] : errors.email;
                }

                if (errors.password) {
                    form.value.errors.password = Array.isArray(errors.password) ? errors.password[0] : errors.password;
                }
            },
            preserveState: true,
            preserveScroll: true
        });
    } catch (error) {
        console.error('Login error:', error);
    } finally {
        form.value.processing = false;
    }
};

</script>

<template>
    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
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
                ¿No tienes una cuenta?
                <TextLink :href="route('register')" :tabindex="5">Regístrate</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
