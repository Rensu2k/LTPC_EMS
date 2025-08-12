<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";

const props = defineProps({
    show: Boolean,
    assessment: Object,
    trainers: Array,
    programs: Array,
});

const emit = defineEmits(["close", "submitted"]);

const form = useForm({
    assessment_date: "",
    trainer_id: "",
    assessment_fee: "0",
    payment_status: "pending",
    payment_method: "",
    payment_reference: "",
    payment_notes: "Re-assessment - payment required",
});

const submit = () => {
    form.post(route("officer.assessments.reassessment", props.assessment.id), {
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
        } else {
            // Set default assessment date to today when opening
            const today = new Date();
            form.assessment_date = today.toISOString().split("T")[0];
        }
    }
);

// Computed property for filtered trainers based on assessment program
const filteredTrainers = computed(() => {
    if (!props.assessment?.program_id || !props.trainers) {
        return props.trainers || [];
    }

    // Find the program
    const program = props.programs.find(
        (p) => p.program_id === props.assessment.program_id
    );
    if (!program || !program.assigned_trainers) {
        return [];
    }

    // Filter trainers assigned to this program
    return props.trainers.filter((trainer) =>
        program.assigned_trainers.includes(trainer.id)
    );
});

// Check if applicant is a scholar (for display purposes)
const isScholar = computed(() => {
    return (
        props.assessment?.applicant_type === "enrolled_trainee" &&
        props.assessment?.trainee?.scholarship_package
    );
});
</script>

<template>
    <Modal :show="show" @close="close" max-width="lg">
        <div class="p-6" v-if="assessment">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">
                        Schedule Re-assessment
                    </h2>
                    <p class="text-gray-600 mt-1">
                        Create a re-assessment for: {{ assessment.title }}
                    </p>
                    <div class="mt-3 flex gap-2">
                        <span
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                        >
                            Attempt #{{ assessment.attempt_number + 1 }}
                        </span>
                        <span
                            v-if="isScholar"
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800"
                        >
                            Scholar - Fee Required for Re-assessment
                        </span>
                    </div>
                </div>
                <button
                    @click="close"
                    class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100"
                >
                    <svg
                        class="h-7 w-7"
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

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Assessment Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-md font-semibold text-gray-900 mb-3">
                        Original Assessment Details
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-500"
                                >Applicant:</span
                            >
                            <span class="ml-2 text-gray-900">{{
                                assessment.applicant_name
                            }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500"
                                >Program:</span
                            >
                            <span class="ml-2 text-gray-900">{{
                                assessment.program_name
                            }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500"
                                >Previous Result:</span
                            >
                            <span
                                :class="assessment.result_color"
                                class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                                {{ assessment.result_status }}
                            </span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500"
                                >Previous Date:</span
                            >
                            <span class="ml-2 text-gray-900">{{
                                assessment.assessment_date
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Re-assessment Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Assessment Date -->
                    <div>
                        <InputLabel
                            for="assessment_date"
                            value="New Assessment Date *"
                        />
                        <TextInput
                            id="assessment_date"
                            v-model="form.assessment_date"
                            type="date"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.assessment_date"
                        />
                    </div>

                    <!-- Trainer Selection -->
                    <div>
                        <SearchableSelect
                            v-model="form.trainer_id"
                            :options="filteredTrainers"
                            label="Trainer/Assessor *"
                            placeholder="Select trainer..."
                            display-key="full_name"
                            value-key="id"
                            :required="true"
                            :error="form.errors.trainer_id"
                            :empty-message="
                                !assessment.program_id
                                    ? 'No program selected'
                                    : 'No trainers assigned to this program'
                            "
                        />
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="bg-red-50 rounded-lg p-4">
                    <h3
                        class="text-md font-semibold text-red-900 mb-3 flex items-center gap-2"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                            />
                        </svg>
                        Payment Required for Re-assessment
                    </h3>

                    <div
                        v-if="isScholar"
                        class="mb-4 p-3 bg-orange-100 border border-orange-200 rounded-lg"
                    >
                        <p class="text-orange-800 text-sm">
                            <strong>Note:</strong> Scholar exemption only
                            applies to the first assessment attempt.
                            Re-assessments require payment regardless of
                            scholarship status.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Assessment Fee -->
                        <div>
                            <InputLabel
                                for="assessment_fee"
                                value="Assessment Fee *"
                            />
                            <TextInput
                                id="assessment_fee"
                                v-model="form.assessment_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.assessment_fee"
                            />
                        </div>

                        <!-- Payment Status -->
                        <div>
                            <InputLabel
                                for="payment_status"
                                value="Payment Status *"
                            />
                            <select
                                id="payment_status"
                                v-model="form.payment_status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.payment_status"
                            />
                        </div>
                    </div>

                    <!-- Payment Details (shown when paid) -->
                    <div
                        v-if="form.payment_status === 'paid'"
                        class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4"
                    >
                        <!-- Payment Method -->
                        <div>
                            <InputLabel
                                for="payment_method"
                                value="Payment Method *"
                            />
                            <TextInput
                                id="payment_method"
                                v-model="form.payment_method"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g., Cash, GCash, Bank Transfer"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.payment_method"
                            />
                        </div>

                        <!-- Payment Reference -->
                        <div>
                            <InputLabel
                                for="payment_reference"
                                value="Payment Reference"
                            />
                            <TextInput
                                id="payment_reference"
                                v-model="form.payment_reference"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Transaction ID, Receipt #"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.payment_reference"
                            />
                        </div>
                    </div>

                    <!-- Payment Notes -->
                    <div class="mt-4">
                        <InputLabel for="payment_notes" value="Payment Notes" />
                        <textarea
                            id="payment_notes"
                            v-model="form.payment_notes"
                            rows="2"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Additional payment information..."
                        ></textarea>
                        <InputError
                            class="mt-2"
                            :message="form.errors.payment_notes"
                        />
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t">
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
                                ? "Scheduling..."
                                : "Schedule Re-assessment"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
