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
    <div class="login-wrapper">
        <!-- Left: Login Form -->
        <div class="left-panel">
            <!-- Animated floating orbs -->
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
            <div class="orb orb-4"></div>

            <!-- Mesh gradient overlay -->
            <div class="mesh-overlay"></div>

            <div
                class="form-container"
                :class="
                    isVisible
                        ? 'translate-y-0 opacity-100'
                        : 'translate-y-8 opacity-0'
                "
            >
                <!-- Small logo above form -->
                <div class="logo-wrapper">
                    <ApplicationLogo class="logo-small" />
                </div>

                <!-- Header -->
                <div class="form-header">
                    <h1 class="system-title">LTPC Enrollment System</h1>
                    <p class="system-subtitle">
                        Surigao City Livelihood Training and Productivity Center
                    </p>
                </div>

                <!-- Login Card (Glassmorphism) -->
                <div class="glass-card">
                    <div class="card-header">
                        <h2 class="welcome-text">Welcome back</h2>
                        <p class="welcome-sub">
                            Sign in to your account to continue
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="login-form">
                        <!-- Username Field -->
                        <div class="field-group">
                            <label for="username" class="field-label">
                                Username
                            </label>
                            <div class="input-wrapper">
                                <div class="input-icon">
                                    <svg
                                        class="icon"
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
                                    class="text-input"
                                    :class="
                                        form.errors.username
                                            ? 'input-error'
                                            : ''
                                    "
                                    placeholder="Enter your username"
                                />
                            </div>
                            <div
                                v-if="form.errors.username"
                                class="error-message"
                            >
                                <svg
                                    class="error-icon"
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
                        <div class="field-group">
                            <label for="password" class="field-label">
                                Password
                            </label>
                            <div class="input-wrapper">
                                <div class="input-icon">
                                    <svg
                                        class="icon"
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
                                    class="text-input"
                                    :class="
                                        form.errors.password
                                            ? 'input-error'
                                            : ''
                                    "
                                    placeholder="Enter your password"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="toggle-password"
                                >
                                    <svg
                                        v-if="!showPassword"
                                        class="icon"
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
                                        class="icon"
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
                                class="error-message"
                            >
                                <svg
                                    class="error-icon"
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
                        <div class="remember-row">
                            <label class="remember-label">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="remember-checkbox"
                                />
                                <span class="checkmark"></span>
                                <span class="remember-text">Remember me</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="submit-btn"
                        >
                            <div class="btn-content">
                                <svg
                                    v-if="form.processing"
                                    class="spinner"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="spinner-track"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="spinner-head"
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
                            <div class="btn-shimmer"></div>
                        </button>
                    </form>
                </div>

                <!-- Footer -->
                <p class="footer-text">
                    © 2025 Surigao City LTPC. All rights reserved.
                </p>
            </div>
        </div>

        <!-- Right: Info Panel -->
        <div class="right-panel">
            <!-- Animated background shapes -->
            <div class="bg-shape shape-1"></div>
            <div class="bg-shape shape-2"></div>
            <div class="bg-shape shape-3"></div>
            <div class="wave-bottom"></div>

            <div
                class="info-container"
                :class="
                    isVisible
                        ? 'translate-x-0 opacity-100'
                        : 'translate-x-8 opacity-0'
                "
            >
                <!-- Logo -->
                <div class="logo-hero-wrapper">
                    <div class="logo-glow"></div>
                    <ApplicationLogo class="logo-hero" />
                </div>

                <!-- Title -->
                <h2 class="info-title">
                    Surigao City LTPC<br />
                    <span class="info-title-accent"
                        >Enrollment Management System</span
                    >
                </h2>
                <p class="info-description">
                    An integrated system for managing enrollments, programs, and
                    training progress at the Livelihood Training and
                    Productivity Center.
                </p>

                <!-- Feature Cards -->
                <div class="feature-grid">
                    <!-- Card 1 -->
                    <div class="feature-card">
                        <span class="feature-icon-wrapper">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="feature-icon"
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
                            <div class="feature-title">Program Management</div>
                            <div class="feature-desc">
                                Create and manage training programs and batches
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="feature-card">
                        <span class="feature-icon-wrapper">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="feature-icon"
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
                            <div class="feature-title">Trainee Tracking</div>
                            <div class="feature-desc">
                                Register and monitor training participants
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="feature-card">
                        <span class="feature-icon-wrapper">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="feature-icon"
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
                            <div class="feature-title">Trainer Assignment</div>
                            <div class="feature-desc">
                                Assign trainers to programs and monitor
                                performance
                            </div>
                        </div>
                    </div>

                    <!-- Card 4 -->
                    <div class="feature-card">
                        <span class="feature-icon-wrapper">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="feature-icon"
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
                            <div class="feature-title">Payment Tracking</div>
                            <div class="feature-desc">
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
/* ===== FONTS ===== */
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

/* ===== BASE LAYOUT ===== */
.login-wrapper {
    display: flex;
    min-height: 100vh;
    font-family: "Inter", sans-serif;
}

/* ===== LEFT PANEL ===== */
.left-panel {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 50%;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 40%, #e2e8f0 100%);
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

/* Mesh gradient overlay */
.mesh-overlay {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            ellipse at 20% 50%,
            rgba(99, 102, 241, 0.06) 0%,
            transparent 50%
        ),
        radial-gradient(
            ellipse at 80% 20%,
            rgba(139, 92, 246, 0.04) 0%,
            transparent 50%
        ),
        radial-gradient(
            ellipse at 60% 80%,
            rgba(59, 130, 246, 0.04) 0%,
            transparent 50%
        );
    pointer-events: none;
}

/* Floating orbs */
.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.25;
    animation: float 20s ease-in-out infinite;
    pointer-events: none;
}
.orb-1 {
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.5), transparent);
    top: -80px;
    left: -80px;
    animation-delay: 0s;
}
.orb-2 {
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.4), transparent);
    bottom: 10%;
    right: 5%;
    animation-delay: -5s;
    animation-duration: 25s;
}
.orb-3 {
    width: 150px;
    height: 150px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.35), transparent);
    top: 60%;
    left: 10%;
    animation-delay: -10s;
    animation-duration: 18s;
}
.orb-4 {
    width: 100px;
    height: 100px;
    background: radial-gradient(circle, rgba(168, 85, 247, 0.3), transparent);
    top: 15%;
    right: 20%;
    animation-delay: -7s;
    animation-duration: 22s;
}

@keyframes float {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    25% {
        transform: translate(30px, -40px) scale(1.05);
    }
    50% {
        transform: translate(-20px, 20px) scale(0.95);
    }
    75% {
        transform: translate(15px, 30px) scale(1.02);
    }
}

/* ===== FORM CONTAINER ===== */
.form-container {
    width: 100%;
    max-width: 420px;
    position: relative;
    z-index: 10;
    transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Logo above form */
.logo-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}
.logo-small {
    height: 64px;
    width: 64px;
    border-radius: 16px;
    background: #ffffff;
    padding: 8px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

/* Header text */
.form-header {
    text-align: center;
    margin-bottom: 1.5rem;
}
.system-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 0.375rem;
    letter-spacing: -0.025em;
}
.system-subtitle {
    font-size: 0.875rem;
    color: #94a3b8;
    font-weight: 400;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

/* ===== CARD ===== */
.glass-card {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    padding: 2rem;
    box-shadow:
        0 4px 24px rgba(0, 0, 0, 0.06),
        0 1px 2px rgba(0, 0, 0, 0.04);
    transition: all 0.3s ease;
}
.glass-card:hover {
    border-color: rgba(0, 0, 0, 0.1);
    box-shadow:
        0 8px 40px rgba(0, 0, 0, 0.08),
        0 1px 2px rgba(0, 0, 0, 0.04);
}

/* Card header */
.card-header {
    margin-bottom: 1.75rem;
}
.welcome-text {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.375rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.welcome-sub {
    color: #94a3b8;
    font-size: 0.875rem;
}

/* ===== FORM FIELDS ===== */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.field-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.field-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    letter-spacing: 0.025em;
}
.input-wrapper {
    position: relative;
}
.input-icon {
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    padding-left: 0.875rem;
    display: flex;
    align-items: center;
    pointer-events: none;
}
.icon {
    width: 1.25rem;
    height: 1.25rem;
    color: #94a3b8;
}
.text-input {
    width: 100%;
    padding: 0.75rem 0.875rem 0.75rem 2.75rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    color: #1e293b;
    font-size: 0.9375rem;
    font-family: "Inter", sans-serif;
    transition: all 0.25s ease;
    outline: none;
}
.text-input::placeholder {
    color: #94a3b8;
}
.text-input:focus {
    border-color: #818cf8;
    background: #ffffff;
    box-shadow:
        0 0 0 4px rgba(99, 102, 241, 0.1),
        0 0 20px rgba(99, 102, 241, 0.05);
}
.text-input.input-error {
    border-color: #fca5a5;
}
.text-input.input-error:focus {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

/* Toggle password */
.toggle-password {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    padding-right: 0.875rem;
    display: flex;
    align-items: center;
    background: none;
    border: none;
    cursor: pointer;
    color: #94a3b8;
    transition: color 0.2s;
}
.toggle-password:hover {
    color: #475569;
    transform: none;
}

/* Error message */
.error-message {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: #ef4444;
    font-size: 0.8125rem;
}
.error-icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

/* ===== REMEMBER CHECKBOX ===== */
.remember-row {
    display: flex;
    align-items: center;
}
.remember-label {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    cursor: pointer;
    user-select: none;
}
.remember-checkbox {
    appearance: none;
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border: 1.5px solid #cbd5e1;
    border-radius: 5px;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}
.remember-checkbox:checked {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-color: transparent;
}
.remember-checkbox:checked::after {
    content: "";
    position: absolute;
    left: 5px;
    top: 2px;
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
.remember-text {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

/* ===== SUBMIT BUTTON ===== */
.submit-btn {
    position: relative;
    width: 100%;
    padding: 0.8125rem 1.5rem;
    background: linear-gradient(135deg, #6366f1, #7c3aed, #8b5cf6);
    border: none;
    border-radius: 12px;
    color: #ffffff;
    font-family: "Inter", sans-serif;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35);
    margin-top: 0.25rem;
}
.submit-btn:hover:not(:disabled) {
    box-shadow: 0 6px 24px rgba(99, 102, 241, 0.5);
    transform: translateY(-2px);
}
.submit-btn:active:not(:disabled) {
    transform: translateY(0);
}
.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.btn-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    position: relative;
    z-index: 1;
}

/* Shimmer effect */
.btn-shimmer {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transition: none;
}
.submit-btn:hover .btn-shimmer {
    animation: shimmer 1.5s ease-in-out infinite;
}
@keyframes shimmer {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

/* Spinner */
.spinner {
    width: 1.25rem;
    height: 1.25rem;
    animation: spin 1s linear infinite;
}
.spinner-track {
    opacity: 0.25;
}
.spinner-head {
    opacity: 0.75;
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Footer */
.footer-text {
    text-align: center;
    margin-top: 1.5rem;
    color: #94a3b8;
    font-size: 0.75rem;
    letter-spacing: 0.025em;
}

/* ===== RIGHT PANEL ===== */
.right-panel {
    display: none;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    width: 50%;
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 40%, #2563eb 100%);
    color: #ffffff;
    padding: 3rem 2rem;
    position: relative;
    overflow: hidden;
}

/* Background animated shapes */
.bg-shape {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.04);
    animation: morphFloat 20s ease-in-out infinite;
    pointer-events: none;
}
.shape-1 {
    width: 400px;
    height: 400px;
    top: -100px;
    right: -100px;
    animation-delay: 0s;
}
.shape-2 {
    width: 250px;
    height: 250px;
    bottom: 20%;
    left: -60px;
    animation-delay: -7s;
    animation-duration: 25s;
}
.shape-3 {
    width: 180px;
    height: 180px;
    top: 50%;
    right: 10%;
    animation-delay: -12s;
    animation-duration: 22s;
}

@keyframes morphFloat {
    0%,
    100% {
        transform: translate(0, 0) scale(1) rotate(0deg);
        border-radius: 50%;
    }
    25% {
        transform: translate(20px, -30px) scale(1.1) rotate(5deg);
        border-radius: 45% 55% 50% 50%;
    }
    50% {
        transform: translate(-15px, 15px) scale(0.95) rotate(-3deg);
        border-radius: 55% 45% 48% 52%;
    }
    75% {
        transform: translate(10px, 25px) scale(1.05) rotate(2deg);
        border-radius: 48% 52% 55% 45%;
    }
}

/* Wave at bottom of right panel */
.wave-bottom {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 120px;
    background: linear-gradient(to top, rgba(255, 255, 255, 0.03), transparent);
    clip-path: polygon(
        0% 60%,
        15% 50%,
        30% 60%,
        45% 45%,
        60% 55%,
        75% 40%,
        90% 55%,
        100% 45%,
        100% 100%,
        0% 100%
    );
    pointer-events: none;
}

/* Info container */
.info-container {
    position: relative;
    z-index: 10;
    transition: all 1s cubic-bezier(0.4, 0, 0.2, 1) 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Hero logo */
.logo-hero-wrapper {
    position: relative;
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
}
.logo-glow {
    position: absolute;
    inset: -16px;
    background: white;
    border-radius: 50%;
    filter: blur(30px);
    opacity: 0.15;
    animation: pulse-glow 4s ease-in-out infinite;
}
@keyframes pulse-glow {
    0%,
    100% {
        opacity: 0.15;
        transform: scale(1);
    }
    50% {
        opacity: 0.25;
        transform: scale(1.1);
    }
}
.logo-hero {
    height: 10rem;
    width: 10rem;
    background: white;
    border-radius: 50%;
    padding: 0.5rem;
    position: relative;
    z-index: 1;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.25);
}

/* Info text */
.info-title {
    font-size: 2.25rem;
    font-weight: 800;
    margin-bottom: 1rem;
    text-align: center;
    line-height: 1.2;
    letter-spacing: -0.025em;
}
.info-title-accent {
    color: #93c5fd;
}
.info-description {
    margin-bottom: 2.5rem;
    font-size: 1.0625rem;
    color: #bfdbfe;
    max-width: 480px;
    text-align: center;
    line-height: 1.6;
}

/* ===== FEATURE CARDS ===== */
.feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    width: 100%;
    max-width: 520px;
}
.feature-card {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    padding: 1rem;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
}
.feature-card:hover {
    background: rgba(255, 255, 255, 0.14);
    border-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}
.feature-icon-wrapper {
    padding: 0.625rem;
    background: linear-gradient(
        135deg,
        rgba(99, 102, 241, 0.4),
        rgba(59, 130, 246, 0.4)
    );
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
}
.feature-icon {
    width: 1.25rem;
    height: 1.25rem;
}
.feature-title {
    font-weight: 700;
    font-size: 0.9375rem;
    margin-bottom: 0.125rem;
}
.feature-desc {
    color: #bfdbfe;
    font-size: 0.8125rem;
    line-height: 1.4;
}

/* ===== RESPONSIVE ===== */
@media (min-width: 768px) {
    .right-panel {
        display: flex;
    }
    .left-panel {
        width: 50%;
    }
}

@media (max-width: 767px) {
    .left-panel {
        width: 100%;
        min-height: 100vh;
    }
}

/* ===== UTILITY CLASSES ===== */
.translate-y-0 {
    transform: translateY(0);
}
.translate-y-8 {
    transform: translateY(2rem);
}
.translate-x-0 {
    transform: translateX(0);
}
.translate-x-8 {
    transform: translateX(2rem);
}
.opacity-0 {
    opacity: 0;
}
.opacity-100 {
    opacity: 1;
}

/* Backdrop blur fallback */
@supports not (backdrop-filter: blur(12px)) {
    .glass-card {
        background: rgba(30, 30, 60, 0.9);
    }
    .feature-card {
        background: rgba(255, 255, 255, 0.12);
    }
}
</style>
