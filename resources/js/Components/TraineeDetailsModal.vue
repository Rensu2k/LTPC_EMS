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
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">
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
                                >Course</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ trainee.course_qualification }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Batch</label
                            >
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
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
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"
                            >
                                {{ trainee.status || "Active" }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">
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
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">Address</h3>
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
                <PrimaryButton @click="editTrainee">
                    Edit Trainee
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
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
</script>
