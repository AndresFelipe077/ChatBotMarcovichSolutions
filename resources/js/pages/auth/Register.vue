<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
});

const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);

// Get CSRF token from meta tag
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

const submit = async () => {
    isLoading.value = true;
    errors.value = {};

    try {
        // Register the user
        const response = await fetch('/api/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                name: form.value.name,
                email: form.value.email,
                password: form.value.password,
                password_confirmation: form.value.password_confirmation
            })
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422) {
                // Handle validation errors
                errors.value = data.errors || {};
                if (data.message) {
                    errors.value.general = [data.message];
                }
            } else {
                throw new Error(data.message || 'Error en el registro');
            }
            return;
        }

        // Redirect to login page with success message
        router.visit(route('login'), {
            onSuccess: () => {
                // You can add a success message here if needed
                // For example: showToast('Registro exitoso. Por favor inicia sesión.');
            }
        });

    } catch (error) {
        console.error('Registration error:', error);
        errors.value.general = ['Ocurrió un error durante el registro. Por favor inténtalo de nuevo.'];
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div v-if="errors.general" class="text-sm text-red-500 bg-red-50 p-3 rounded-md">
                {{ errors.general }}
            </div>

            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        v-model="form.name"
                        placeholder="Full name"
                        :class="{'border-red-500': errors.name}"
                    />
                    <InputError :message="errors.name?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                        :class="{'border-red-500': errors.email}"
                    />
                    <InputError :message="errors.email?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                        :class="{'border-red-500': errors.password}"
                    />
                    <InputError :message="errors.password?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="isLoading">
                    <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin mr-2" />
                    {{ isLoading ? 'Creating account...' : 'Create account' }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
