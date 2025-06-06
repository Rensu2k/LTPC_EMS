<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <Head title="Enrollment Officer Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Enrollment Officer Dashboard
            </h2>
        </template>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <div class="bg-white rounded-xl shadow p-8">
                <h1 class="text-2xl font-bold mb-4">
                    Welcome, Enrollment Officer!
                </h1>
                <p class="text-gray-600">
                    This is your dashboard. Add your officer-specific widgets
                    here.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
