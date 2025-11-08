<template>
    <Modal :show="show" @close="closeModal" custom-width="80vw">
        <div class="p-6 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    Trainee Details
                </h2>
                <button
                    @click="closeModal"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <div v-if="trainee" class="space-y-6">
                <!-- Basic Information -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3
                        class="text-lg font-semibold mb-4 flex items-center gap-2"
                    >
                        <svg
                            class="w-5 h-5 text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                        Basic Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >ULI Number</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ trainee.uli_number || "Not assigned" }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Full Name</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ trainee.first_name }} {{ trainee.last_name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Program</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ trainee.program_qualification }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Batch</label
                            >
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200"
                            >
                                Batch {{ trainee.batch || 1 }}
                            </span>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Status</label
                            >
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-50 text-green-700 border border-green-200"
                            >
                                {{ trainee.status || "Active" }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3
                        class="text-lg font-semibold mb-4 flex items-center gap-2"
                    >
                        <svg
                            class="w-5 h-5 text-green-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                        </svg>
                        Contact Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Email/Facebook</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ trainee.email_facebook || "Not provided" }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Contact Number</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ trainee.contact_number || "Not provided" }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3
                        class="text-lg font-semibold mb-4 flex items-center gap-2"
                    >
                        <svg
                            class="w-5 h-5 text-orange-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        Address
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Barangay</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ trainee.barangay || "Not provided" }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >City/Municipality</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{
                                    trainee.city_municipality || "Not provided"
                                }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Province</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ trainee.province || "Not provided" }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t mt-6">
                <SecondaryButton @click="closeModal"> Close </SecondaryButton>
                <button
                    @click="viewEnrollmentHistory"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    Enrollment History
                </button>
                <PrimaryButton @click="editTrainee">
                    Edit Trainee
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    trainee: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close", "edit"]);

const closeModal = () => {
    emit("close");
};

const editTrainee = () => {
    emit("edit", props.trainee);
};

const viewEnrollmentHistory = () => {
    if (props.trainee) {
        router.visit(
            route("officer.trainees.enrollment-history", props.trainee.id)
        );
    }
};
</script>
