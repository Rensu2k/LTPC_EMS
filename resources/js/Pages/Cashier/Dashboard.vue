<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <Head title="Cashier Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Cashier Dashboard
            </h2>
        </template>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <div class="bg-white rounded-xl shadow p-8">
                <h1 class="text-2xl font-bold mb-4">Welcome, Cashier!</h1>
                <p class="text-gray-600">
                    This is your cashier dashboard. Add your payment and
                    transaction tools here.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
