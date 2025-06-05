<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";

const showPassword = ref(false);

const form = useForm({
    username: "",
    password: "",
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Log in" />
    <div class="min-h-screen flex">
        <!-- Left: Login Form -->
        <div
            class="flex flex-col justify-center items-center w-full md:w-1/2 bg-gray-50 p-8"
        >
            <div class="w-full max-w-md">
                <h1
                    class="text-2xl md:text-3xl font-bold text-center text-blue-900 mb-1"
                >
                    LTPC Enrollment System
                </h1>
                <p class="text-center text-gray-500 mb-6">
                    Surigao City Livelihood Training and Productivity Center
                </p>
                <div class="mb-4">
                    <div
                        class="rounded-md py-1 text-center text-3xl font-bold text-gray-700"
                    >
                        Login
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-8">
                    <h2 class="text-xl font-semibold mb-1">Welcome back</h2>
                    <p class="text-gray-500 text-sm mb-6">
                        Sign in to your account to continue
                    </p>
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Username -->
                        <div>
                            <label
                                for="username"
                                class="block text-sm font-medium mb-1"
                                >Username</label
                            >
                            <input
                                id="username"
                                v-model="form.username"
                                type="text"
                                required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-700 focus:ring-blue-700"
                                placeholder="Enter your username"
                            />
                            <p
                                v-if="form.errors.username"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.username }}
                            </p>
                        </div>
                        <!-- Password -->
                        <div>
                            <label
                                for="password"
                                class="block text-sm font-medium mb-1"
                                >Password</label
                            >
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                v-model="form.password"
                                required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-700 focus:ring-blue-700"
                                placeholder="Enter your password"
                            />
                            <p
                                v-if="form.errors.password"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>
                        <!-- Show Password Toggle -->
                        <div class="flex items-center mb-2">
                            <input
                                id="showPassword"
                                type="checkbox"
                                v-model="showPassword"
                                class="mr-2"
                            />
                            <label
                                for="showPassword"
                                class="text-sm text-gray-600 select-none"
                                >Show password</label
                            >
                        </div>
                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-blue-900 hover:bg-blue-800 text-white font-medium py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? "Signing in..." : "Sign In" }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Right: Info Panel -->
        <div
            class="hidden md:flex flex-col justify-center items-start w-1/2 bg-blue-800 text-white px-16"
        >
            <h2 class="text-3xl font-bold mb-2">
                Surigao City LTPC Enrollment Management System
            </h2>
            <p class="mb-8 text-lg text-blue-100 max-w-xl">
                An integrated system for managing enrollments, courses, and
                training progress at the Livelihood Training and Productivity
                Center.
            </p>
            <div class="grid grid-cols-2 gap-6 w-full max-w-2xl">
                <!-- Card 1 -->
                <div class="flex items-start space-x-3">
                    <span class="bg-blue-700 p-2 rounded-md">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6l4 2"
                            />
                            <rect width="20" height="14" x="2" y="5" rx="2" />
                        </svg>
                    </span>
                    <div>
                        <div class="font-semibold">Course Management</div>
                        <div class="text-blue-100 text-sm">
                            Create and manage training courses and batches
                        </div>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="flex items-start space-x-3">
                    <span class="bg-blue-700 p-2 rounded-md">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 3.13a4 4 0 010 7.75M8 3.13a4 4 0 000 7.75"
                            />
                        </svg>
                    </span>
                    <div>
                        <div class="font-semibold">Trainee Tracking</div>
                        <div class="text-blue-100 text-sm">
                            Register and monitor training participants
                        </div>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="flex items-start space-x-3">
                    <span class="bg-blue-700 p-2 rounded-md">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 10c-4.41 0-8-1.79-8-4V6c0-2.21 3.59-4 8-4s8 1.79 8 4v8c0 2.21-3.59 4-8 4z"
                            />
                        </svg>
                    </span>
                    <div>
                        <div class="font-semibold">Trainer Assignment</div>
                        <div class="text-blue-100 text-sm">
                            Assign trainers to courses and monitor performance
                        </div>
                    </div>
                </div>
                <!-- Card 4 -->
                <div class="flex items-start space-x-3">
                    <span class="bg-blue-700 p-2 rounded-md">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 9V7a5 5 0 00-10 0v2a2 2 0 00-2 2v7a2 2 0 002 2h12a2 2 0 002-2v-7a2 2 0 00-2-2z"
                            />
                        </svg>
                    </span>
                    <div>
                        <div class="font-semibold">Payment Tracking</div>
                        <div class="text-blue-100 text-sm">
                            Record payments and generate official receipts
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom styles for extra polish */
</style>
