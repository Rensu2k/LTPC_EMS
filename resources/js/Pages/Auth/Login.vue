<script setup>
import { ref, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const showPassword = ref(false);
const isVisible = ref(false);

const form = useForm({
    username: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

// Trigger fade-in animation
onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});
</script>

<template>
    <Head title="Log in" />
    <div class="min-h-screen flex">
        <!-- Left: Login Form -->
        <div
            class="flex flex-col justify-center items-center w-full md:w-1/2 bg-gradient-to-br from-gray-50 to-gray-100 p-8 relative overflow-hidden"
        >
            <!-- Subtle background pattern -->
            <div class="absolute inset-0 opacity-5">
                <div class="absolute inset-0 bg-dots-pattern"></div>
            </div>

            <div
                class="w-full max-w-md relative z-10 transition-all duration-700 ease-out transform"
                :class="
                    isVisible
                        ? 'translate-y-0 opacity-100'
                        : 'translate-y-8 opacity-0'
                "
            >
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1
                        class="text-3xl md:text-4xl font-bold text-gray-900 mb-2"
                    >
                        LTPC Enrollment System
                    </h1>
                    <p class="text-gray-600 font-medium">
                        Surigao City Livelihood Training and Productivity Center
                    </p>
                </div>

                <!-- Login Card -->
                <div
                    class="bg-white rounded-2xl shadow-xl p-8 transition-all duration-300 hover:shadow-2xl"
                >
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            Welcome back
                        </h2>
                        <p class="text-gray-600">
                            Sign in to your account to continue
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Username Field -->
                        <div class="space-y-2">
                            <label
                                for="username"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Username
                            </label>
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                >
                                    <svg
                                        class="h-5 w-5 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        ></path>
                                    </svg>
                                </div>
                                <input
                                    id="username"
                                    v-model="form.username"
                                    type="text"
                                    required
                                    class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200 placeholder-gray-400"
                                    :class="
                                        form.errors.username
                                            ? 'border-red-300 focus:border-red-500 focus:ring-red-100'
                                            : ''
                                    "
                                    placeholder="Enter your username"
                                />
                            </div>
                            <div
                                v-if="form.errors.username"
                                class="flex items-center space-x-2 text-red-600 text-sm"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                <span>{{ form.errors.username }}</span>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-2">
                            <label
                                for="password"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Password
                            </label>
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                >
                                    <svg
                                        class="h-5 w-5 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        ></path>
                                    </svg>
                                </div>
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    id="password"
                                    v-model="form.password"
                                    required
                                    class="w-full pl-10 pr-12 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200 placeholder-gray-400"
                                    :class="
                                        form.errors.password
                                            ? 'border-red-300 focus:border-red-500 focus:ring-red-100'
                                            : ''
                                    "
                                    placeholder="Enter your password"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors duration-200"
                                >
                                    <svg
                                        v-if="!showPassword"
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        ></path>
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        ></path>
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                            <div
                                v-if="form.errors.password"
                                class="flex items-center space-x-2 text-red-600 text-sm"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                <span>{{ form.errors.password }}</span>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center">
                            <input
                                id="remember"
                                type="checkbox"
                                v-model="form.remember"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors duration-200"
                            />
                            <label
                                for="remember"
                                class="ml-3 text-sm font-medium text-gray-700 select-none"
                            >
                                Remember me
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-4 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl"
                        >
                            <div
                                class="flex items-center justify-center space-x-2"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="animate-spin h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <span>{{
                                    form.processing
                                        ? "Signing in..."
                                        : "Sign In"
                                }}</span>
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Info Panel -->
        <div
            class="hidden md:flex flex-col justify-start items-center w-1/2 bg-gradient-to-br from-blue-800 via-blue-900 to-blue-800 text-white px-8 pt-16 relative overflow-hidden"
        >
            <!-- Subtle background pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0 bg-triangle-pattern"></div>
            </div>

            <div
                class="relative z-10 transition-all duration-1000 ease-out transform delay-300"
                :class="
                    isVisible
                        ? 'translate-x-0 opacity-100'
                        : 'translate-x-8 opacity-0'
                "
            >
                <!-- Logo -->
                <div class="flex justify-center mb-8 w-full">
                    <div class="relative">
                        <div
                            class="absolute inset-0 bg-white rounded-full blur-xl opacity-20"
                        ></div>
                        <ApplicationLogo
                            class="h-48 w-48 bg-white rounded-full p-2 relative z-10 shadow-2xl"
                        />
                    </div>
                </div>

                <!-- Title -->
                <h2 class="text-4xl font-bold mb-4 text-center leading-tight">
                    Surigao City LTPC<br />
                    <span class="text-blue-200"
                        >Enrollment Management System</span
                    >
                </h2>
                <p
                    class="mb-12 text-xl text-blue-100 max-w-3xl text-center mx-auto leading-relaxed"
                >
                    An integrated system for managing enrollments, programs, and
                    training progress at the Livelihood Training and
                    Productivity Center.
                </p>

                <!-- Feature Cards -->
                <div class="grid grid-cols-2 gap-8 w-full max-w-3xl">
                    <!-- Card 1 -->
                    <div
                        class="flex items-start space-x-4 p-4 rounded-lg bg-white bg-opacity-10 backdrop-blur-sm transition-all duration-300 hover:bg-opacity-20"
                    >
                        <span class="bg-blue-700 p-3 rounded-xl shadow-lg">
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
                                <rect
                                    width="20"
                                    height="14"
                                    x="2"
                                    y="5"
                                    rx="2"
                                />
                            </svg>
                        </span>
                        <div>
                            <div class="font-bold text-lg">
                                Program Management
                            </div>
                            <div class="text-blue-100 text-sm leading-relaxed">
                                Create and manage training programs and batches
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div
                        class="flex items-start space-x-4 p-4 rounded-lg bg-white bg-opacity-10 backdrop-blur-sm transition-all duration-300 hover:bg-opacity-20"
                    >
                        <span class="bg-blue-700 p-3 rounded-xl shadow-lg">
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
                            <div class="font-bold text-lg">
                                Trainee Tracking
                            </div>
                            <div class="text-blue-100 text-sm leading-relaxed">
                                Register and monitor training participants
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div
                        class="flex items-start space-x-4 p-4 rounded-lg bg-white bg-opacity-10 backdrop-blur-sm transition-all duration-300 hover:bg-opacity-20"
                    >
                        <span class="bg-blue-700 p-3 rounded-xl shadow-lg">
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
                            <div class="font-bold text-lg">
                                Trainer Assignment
                            </div>
                            <div class="text-blue-100 text-sm leading-relaxed">
                                Assign trainers to programs and monitor
                                performance
                            </div>
                        </div>
                    </div>

                    <!-- Card 4 -->
                    <div
                        class="flex items-start space-x-4 p-4 rounded-lg bg-white bg-opacity-10 backdrop-blur-sm transition-all duration-300 hover:bg-opacity-20"
                    >
                        <span class="bg-blue-700 p-3 rounded-xl shadow-lg">
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
                            <div class="font-bold text-lg">
                                Payment Tracking
                            </div>
                            <div class="text-blue-100 text-sm leading-relaxed">
                                Record payments and generate official receipts
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Background patterns */
.bg-dots-pattern {
    background-image: radial-gradient(circle, #000 1px, transparent 1px);
    background-size: 30px 30px;
}

.bg-triangle-pattern {
    background-image: linear-gradient(
        45deg,
        transparent 40%,
        rgba(255, 255, 255, 0.1) 40%,
        rgba(255, 255, 255, 0.1) 60%,
        transparent 60%
    );
    background-size: 40px 40px;
}

/* Additional animations and effects */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Smooth focus transitions */
input:focus {
    outline: none;
}

/* Custom checkbox styling */
input[type="checkbox"]:checked {
    background-color: #2563eb;
    border-color: #2563eb;
}

/* Enhanced button hover effects */
button:hover {
    transform: translateY(-1px);
}

button:active {
    transform: translateY(0);
}

/* Backdrop blur fallback */
@supports not (backdrop-filter: blur(12px)) {
    .backdrop-blur-sm {
        background-color: rgba(255, 255, 255, 0.15);
    }
}
</style>
