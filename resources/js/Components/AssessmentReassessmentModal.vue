<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, watch, ref } from "vue";
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
    assessor_name: "",
    assessment_fee: "",
});

// Track whether to use manual assessor input or select from trainers
const useManualAssessor = ref(false);

const submit = () => {
    // Transform data to ensure proper types for backend
    form.transform((data) => {
        const transformed = {
            ...data,
            assessment_fee: parseFloat(data.assessment_fee) || 0,
        };
        
        // If using manual assessor, clear trainer_id and ensure assessor_name is set
        if (useManualAssessor.value) {
            transformed.trainer_id = null;
            if (!transformed.assessor_name || transformed.assessor_name.trim() === '') {
                // This will be caught by validation
                transformed.assessor_name = '';
            }
        } else {
            // If using trainer selection, clear assessor_name
            transformed.assessor_name = null;
        }
        
        return transformed;
    });

    form.post(route("officer.assessments.reassessment", props.assessment.id), {
        onSuccess: () => {
            form.reset();
            useManualAssessor.value = false;
            emit("submitted");
            emit("close");
        },
    });
};

const close = () => {
    form.reset();
    useManualAssessor.value = false;
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

            // Set default assessment fee - use original assessment fee or standard re-assessment fee
            // Re-assessments are not free (scholars only get exemption for first attempt)
            const defaultFee =
                props.assessment?.assessment_fee > 0
                    ? props.assessment.assessment_fee
                    : "";
            form.assessment_fee = defaultFee.toString();
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
                <div class="space-y-6">
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

                    <!-- Assessor Selection -->
                    <div>
                        <InputLabel for="assessor_type" value="Assessor Type *" />
                        <div class="mt-2 space-y-3">
                            <label class="flex items-center">
                                <input
                                    type="radio"
                                    v-model="useManualAssessor"
                                    :value="false"
                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-700"
                                    >Select from Trainers</span
                                >
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="radio"
                                    v-model="useManualAssessor"
                                    :value="true"
                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-700"
                                    >Manual Input</span
                                >
                            </label>
                        </div>
                    </div>

                    <!-- Trainer Selection (when not using manual input) -->
                    <div v-if="!useManualAssessor">
                        <SearchableSelect
                            v-model="form.trainer_id"
                            :options="filteredTrainers"
                            label="Assessor *"
                            placeholder="Select trainer..."
                            display-key="full_name"
                            value-key="id"
                            :required="!useManualAssessor"
                            :error="form.errors.trainer_id"
                            :empty-message="
                                !assessment.program_id
                                    ? 'No program selected'
                                    : 'No trainers assigned to this program'
                            "
                        />
                    </div>

                    <!-- Manual Assessor Input (when using manual input) -->
                    <div v-else>
                        <InputLabel for="assessor_name" value="Assessor Name *" />
                        <TextInput
                            id="assessor_name"
                            v-model="form.assessor_name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Enter assessor name manually..."
                            :required="useManualAssessor"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.assessor_name"
                        />
                        <p class="text-xs text-gray-500 mt-1">
                            Enter the name of the assessor who will conduct this re-assessment.
                        </p>
                    </div>
                </div>

                <!-- Assessment Fee -->
                <div class="bg-blue-50 rounded-lg p-4">
                    <h3
                        class="text-md font-semibold text-blue-900 mb-3 flex items-center gap-2"
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
                        Assessment Fee Information
                    </h3>

                    <div
                        v-if="isScholar"
                        class="mb-4 p-3 bg-orange-100 border border-orange-200 rounded-lg"
                    >
                        <p class="text-orange-800 text-sm">
                            <strong>Note:</strong> Scholar exemption only
                            applies to the first assessment attempt.
                            Re-assessments require payment which will be handled
                            by the cashier.
                        </p>
                    </div>

                    <div class="mt-4">
                        <InputLabel
                            for="assessment_fee"
                            value="Assessment Fee Amount *"
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
                        <p class="text-xs text-gray-500 mt-1">
                            This amount will be used for cashier payment
                            processing
                        </p>
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
