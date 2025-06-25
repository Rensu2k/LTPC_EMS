<template>
    <Modal :show="show" @close="close" custom-width="70vw">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg
                        class="h-6 w-6 text-green-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Enroll {{ trainee.first_name }}
                        {{ trainee.last_name }} in New Program
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Add trainee to another program while maintaining
                        enrollment history
                    </p>
                </div>
            </div>

            <!-- Current Enrollments Summary -->
            <div
                v-if="enrollmentHistory && enrollmentHistory.length > 0"
                class="mb-6 p-4 bg-blue-50 rounded-lg"
            >
                <h3 class="text-sm font-medium text-blue-900 mb-2">
                    Current Enrollment History
                </h3>
                <div class="space-y-2">
                    <div
                        v-for="enrollment in enrollmentHistory"
                        :key="enrollment.id"
                        class="flex justify-between items-center text-sm"
                    >
                        <div>
                            <span class="font-medium">{{
                                enrollment.program.name
                            }}</span>
                            <span class="text-gray-500 ml-2"
                                >(Batch {{ enrollment.batch }})</span
                            >
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2 py-1 rounded-full text-xs font-medium"
                                :class="{
                                    'bg-green-100 text-green-800':
                                        enrollment.status === 'completed',
                                    'bg-blue-100 text-blue-800':
                                        enrollment.status === 'active',
                                    'bg-red-100 text-red-800':
                                        enrollment.status === 'dropped',
                                    'bg-yellow-100 text-yellow-800':
                                        enrollment.status === 'pending',
                                }"
                            >
                                {{
                                    enrollment.status.charAt(0).toUpperCase() +
                                    enrollment.status.slice(1)
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrollment Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Program Selection -->
                    <div class="md:col-span-2">
                        <SearchableSelect
                            v-model="form.program_id"
                            :options="enhancedPrograms"
                            label="Select Program *"
                            placeholder="Type to search programs..."
                            display-key="display_name"
                            value-key="program_id"
                            :required="true"
                            :error="form.errors.program_id"
                            empty-message="No available programs (trainee may already be enrolled in all active programs)"
                        />
                        <p class="text-xs text-gray-500 mt-1">
                            Only programs where the trainee is not currently
                            enrolled are shown
                        </p>
                    </div>
                </div>

                <!-- Batch System Information -->
                <div
                    class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg"
                >
                    <h3
                        class="text-sm font-medium text-blue-900 mb-2 flex items-center gap-2"
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
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        Batch System Information
                    </h3>
                    <p class="text-sm text-blue-800">
                        Programs use a batch system with a maximum of
                        <strong>25 trainees per batch</strong>. Programs have
                        unlimited enrollment capacity - when a batch reaches 25
                        trainees, a new batch is automatically created. If no
                        batch is specified, the trainee will be enrolled in the
                        current active batch or the next available batch if
                        current is full.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Batch Number -->
                    <div>
                        <InputLabel for="batch" value="Batch Number" />
                        <TextInput
                            id="batch"
                            v-model="form.batch"
                            type="number"
                            min="1"
                            class="mt-1 block w-full"
                            placeholder="Auto-assigned if blank"
                        />
                        <InputError class="mt-2" :message="form.errors.batch" />
                        <p class="text-xs text-gray-500 mt-1">
                            Leave blank to auto-assign next available batch
                            (recommended)
                        </p>
                    </div>

                    <!-- Enrollment Fee Override -->
                    <div>
                        <InputLabel
                            for="enrollment_fee"
                            value="Enrollment Fee Override (₱)"
                        />
                        <TextInput
                            id="enrollment_fee"
                            v-model="form.enrollment_fee"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full"
                            :class="{
                                'bg-gray-100 cursor-not-allowed': isScholar,
                            }"
                            placeholder="Use program default"
                            :readonly="isScholar"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.enrollment_fee"
                        />
                        <p v-if="isScholar" class="text-xs text-green-600 mt-1">
                            ⭐ Fee automatically set to ₱0 due to
                            {{ trainee.scholarship_package }} scholarship
                        </p>
                        <p v-else class="text-xs text-gray-500 mt-1">
                            Leave blank to use the program's default enrollment
                            fee
                        </p>
                    </div>

                    <!-- Notes -->
                    <div class="md:col-span-2">
                        <InputLabel for="notes" value="Enrollment Notes" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Any special notes about this enrollment (optional)"
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.notes" />
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t">
                    <SecondaryButton @click="close" type="button">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                        {{
                            form.processing
                                ? "Enrolling..."
                                : "Enroll in Program"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { defineProps, defineEmits, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Modal from "./Modal.vue";
import InputLabel from "./InputLabel.vue";
import TextInput from "./TextInput.vue";
import InputError from "./InputError.vue";
import PrimaryButton from "./PrimaryButton.vue";
import SecondaryButton from "./SecondaryButton.vue";
import SearchableSelect from "./SearchableSelect.vue";

const props = defineProps({
    show: Boolean,
    trainee: Object,
    availablePrograms: Array,
    enrollmentHistory: Array,
});

const emit = defineEmits(["close", "submitted"]);

const form = useForm({
    program_id: "",
    batch: "",
    enrollment_fee: "",
    notes: "",
});

// Check if trainee is a scholar
const isScholar = computed(() => {
    return (
        props.trainee?.scholarship_package &&
        props.trainee.scholarship_package.trim() !== ""
    );
});

// Enhanced programs with display text for SearchableSelect
const enhancedPrograms = computed(() => {
    return (props.availablePrograms || []).map((program) => ({
        ...program,
        display_name: `${program.name} - ₱${program.enrollment_fee} (${program.duration})`,
    }));
});

// Auto-set fee to 0 for scholars
watch(
    () => isScholar.value,
    (newValue) => {
        if (newValue) {
            form.enrollment_fee = "0";
        }
    }
);

const submit = () => {
    form.post(route("officer.trainees.enroll.store", props.trainee.id), {
        onSuccess: () => {
            form.reset();
            emit("submitted");
            emit("close");
        },
    });
};

const close = () => {
    form.reset();
    form.clearErrors();
    emit("close");
};

// Reset form when modal is closed
watch(
    () => props.show,
    (newValue) => {
        if (!newValue) {
            form.reset();
            form.clearErrors();
        }
    }
);
</script>
