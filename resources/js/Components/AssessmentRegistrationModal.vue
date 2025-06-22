<script setup>
import { ref, watch, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    programs: {
        type: Array,
        default: () => [],
    },
    trainees: {
        type: Array,
        default: () => [],
    },
    trainers: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "submitted"]);

const form = useForm({
    title: "",
    description: "",
    type: "theoretical",
    program_id: "",
    applicant_type: "enrolled_trainee",
    trainee_id: "",
    external_applicant_name: "",
    external_applicant_email: "",
    external_applicant_phone: "",
    trainer_id: "",
    max_score: "100",
    passing_score: "75",
    assessment_date: "",
    assessment_fee: "0",
    payment_status: "pending",
    payment_method: "",
    payment_reference: "",
});

const submit = () => {
    form.post(route("officer.assessments.store"), {
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

// Set default assessment date to today
watch(
    () => props.show,
    (newValue) => {
        if (newValue && !form.assessment_date) {
            const today = new Date();
            form.assessment_date = today.toISOString().split("T")[0];
        }
    }
);

// Computed property to get selected trainee
const selectedTrainee = computed(() => {
    return props.trainees.find((trainee) => trainee.id == form.trainee_id);
});

// Computed property to check if selected trainee is a scholar
const isScholar = computed(() => {
    return (
        selectedTrainee.value &&
        selectedTrainee.value.scholarship_package &&
        selectedTrainee.value.scholarship_package.trim() !== ""
    );
});

// Watch for trainee selection changes and automatically set fee to 0 for scholars
watch(
    () => form.trainee_id,
    (newTraineeId) => {
        if (newTraineeId && form.applicant_type === "enrolled_trainee") {
            const trainee = props.trainees.find((t) => t.id == newTraineeId);
            if (
                trainee &&
                trainee.scholarship_package &&
                trainee.scholarship_package.trim() !== ""
            ) {
                form.assessment_fee = "0";
                form.payment_status = "paid";
                form.payment_method = "scholarship_exemption";
                form.payment_reference = `SCHOLAR-${trainee.scholarship_package.toUpperCase()}-${Date.now()}`;
            } else {
                // Reset to default for non-scholars
                form.payment_status = "pending";
                form.payment_method = "";
                form.payment_reference = "";
            }
        }
    }
);

// Watch for applicant type changes to reset fee when switching from external to trainee
watch(
    () => form.applicant_type,
    (newType) => {
        if (newType === "enrolled_trainee" && form.trainee_id) {
            const trainee = props.trainees.find((t) => t.id == form.trainee_id);
            if (
                trainee &&
                trainee.scholarship_package &&
                trainee.scholarship_package.trim() !== ""
            ) {
                form.assessment_fee = "0";
                form.payment_status = "paid";
                form.payment_method = "scholarship_exemption";
                form.payment_reference = `SCHOLAR-${trainee.scholarship_package.toUpperCase()}-${Date.now()}`;
            } else {
                form.payment_status = "pending";
                form.payment_method = "";
                form.payment_reference = "";
            }
        } else {
            // External applicant - reset to default
            form.payment_status = "pending";
            form.payment_method = "";
            form.payment_reference = "";
        }
    }
);
</script>

<template>
    <Modal
        :show="show"
        @close="close"
        custom-width="80vw"
        :close-on-click-outside="false"
    >
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg
                        class="h-6 w-6 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Create New Assessment
                    </h2>
                    <p class="text-gray-600 mb-6">
                        Create a new assessment for applicants
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Assessment Title -->
                    <div class="md:col-span-2">
                        <InputLabel for="title" value="Assessment Title *" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g., Final Practical Exam"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <!-- Assessment Description -->
                    <div class="md:col-span-2">
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Brief description of the assessment..."
                        ></textarea>
                        <InputError
                            class="mt-2"
                            :message="form.errors.description"
                        />
                    </div>

                    <!-- Assessment Type -->
                    <div>
                        <InputLabel for="type" value="Assessment Type *" />
                        <select
                            id="type"
                            v-model="form.type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="theoretical">Theoretical</option>
                            <option value="practical">Practical</option>
                            <option value="both">
                                Both (Theoretical & Practical)
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                    <!-- Maximum Score -->
                    <div>
                        <InputLabel for="max_score" value="Maximum Score *" />
                        <TextInput
                            id="max_score"
                            v-model="form.max_score"
                            type="number"
                            class="mt-1 block w-full"
                            min="1"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.max_score"
                        />
                    </div>

                    <!-- Passing Score -->
                    <div>
                        <InputLabel
                            for="passing_score"
                            value="Passing Score *"
                        />
                        <TextInput
                            id="passing_score"
                            v-model="form.passing_score"
                            type="number"
                            class="mt-1 block w-full"
                            min="1"
                            :max="form.max_score"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.passing_score"
                        />
                        <p class="text-xs text-gray-500 mt-1">
                            Minimum score required to pass the assessment
                        </p>
                    </div>

                    <!-- Applicant Type -->
                    <div class="md:col-span-2">
                        <InputLabel
                            for="applicant_type"
                            value="Applicant Type *"
                        />
                        <div class="mt-2 space-y-3">
                            <label class="flex items-center">
                                <input
                                    type="radio"
                                    v-model="form.applicant_type"
                                    value="enrolled_trainee"
                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-700"
                                    >Enrolled Applicant (Completed Status
                                    Only)</span
                                >
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="radio"
                                    v-model="form.applicant_type"
                                    value="external_applicant"
                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-700"
                                    >External Applicant</span
                                >
                            </label>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.applicant_type"
                        />
                    </div>

                    <!-- Program Selection -->
                    <div>
                        <InputLabel for="program_id" value="Program *" />
                        <select
                            id="program_id"
                            v-model="form.program_id"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="">Select a program</option>
                            <option
                                v-if="!programs || programs.length === 0"
                                disabled
                            >
                                No active programs available
                            </option>
                            <option
                                v-for="program in programs"
                                :key="program.program_id"
                                :value="program.program_id"
                            >
                                {{ program.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.program_id"
                        />
                    </div>

                    <!-- Trainee Selection (for enrolled trainees) -->
                    <div v-if="form.applicant_type === 'enrolled_trainee'">
                        <InputLabel
                            for="trainee_id"
                            value="Applicant (Completed Status) *"
                        />
                        <select
                            id="trainee_id"
                            v-model="form.trainee_id"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :class="{
                                'border-red-500':
                                    form.applicant_type ===
                                        'enrolled_trainee' &&
                                    form.errors.trainee_id,
                            }"
                        >
                            <option value="">Select an applicant</option>
                            <option
                                v-if="!trainees || trainees.length === 0"
                                value=""
                                disabled
                            >
                                No eligible applicants available
                            </option>
                            <option
                                v-for="trainee in trainees"
                                :key="trainee.id"
                                :value="trainee.id"
                            >
                                {{ trainee.first_name }}
                                {{ trainee.last_name }} ({{ trainee.status }})
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.trainee_id"
                        />
                        <p class="text-xs text-gray-500 mt-1">
                            Only applicants with "completed" status can take
                            assessments.
                        </p>
                    </div>

                    <!-- External Applicant Information -->
                    <div
                        v-if="form.applicant_type === 'external_applicant'"
                        class="md:col-span-2"
                    >
                        <h4 class="text-md font-medium text-gray-900 mb-4">
                            External Applicant Information
                        </h4>
                        <div class="space-y-4">
                            <!-- Full Name - Full Width -->
                            <div>
                                <InputLabel
                                    for="external_applicant_name"
                                    value="Full Name *"
                                />
                                <TextInput
                                    id="external_applicant_name"
                                    v-model="form.external_applicant_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Enter full name of the external applicant"
                                    :required="
                                        form.applicant_type ===
                                        'external_applicant'
                                    "
                                />
                                <InputError
                                    class="mt-2"
                                    :message="
                                        form.errors.external_applicant_name
                                    "
                                />
                            </div>

                            <!-- Email and Phone in 2 columns for better space usage -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel
                                        for="external_applicant_email"
                                        value="Email Address *"
                                    />
                                    <TextInput
                                        id="external_applicant_email"
                                        v-model="form.external_applicant_email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        placeholder="Enter email address"
                                        :required="
                                            form.applicant_type ===
                                            'external_applicant'
                                        "
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="
                                            form.errors.external_applicant_email
                                        "
                                    />
                                </div>
                                <div>
                                    <InputLabel
                                        for="external_applicant_phone"
                                        value="Phone Number *"
                                    />
                                    <TextInput
                                        id="external_applicant_phone"
                                        v-model="form.external_applicant_phone"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Enter phone number"
                                        :required="
                                            form.applicant_type ===
                                            'external_applicant'
                                        "
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="
                                            form.errors.external_applicant_phone
                                        "
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trainer Selection -->
                    <div>
                        <InputLabel
                            for="trainer_id"
                            value="Trainer/Assessor *"
                        />
                        <select
                            id="trainer_id"
                            v-model="form.trainer_id"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="">Select a trainer</option>
                            <option
                                v-if="!trainers || trainers.length === 0"
                                disabled
                            >
                                No active trainers available
                            </option>
                            <option
                                v-for="trainer in trainers"
                                :key="trainer.id"
                                :value="trainer.id"
                            >
                                {{ trainer.full_name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.trainer_id"
                        />
                    </div>

                    <!-- Assessment Date -->
                    <div>
                        <InputLabel
                            for="assessment_date"
                            value="Assessment Date *"
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
                </div>

                <!-- Payment Information Section -->
                <div class="bg-amber-50 rounded-lg p-6">
                    <h3
                        class="text-lg font-semibold text-amber-900 mb-4 flex items-center gap-2"
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
                                d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 0V4m0 7v7m0 0h4m-4 0H8"
                            />
                        </svg>
                        Assessment Payment Information
                    </h3>

                    <!-- Assessment Fee -->
                    <div>
                        <InputLabel
                            for="assessment_fee"
                            value="Assessment Fee (₱) *"
                        />

                        <!-- Scholar Notice -->
                        <div
                            v-if="
                                isScholar &&
                                form.applicant_type === 'enrolled_trainee'
                            "
                            class="p-4 bg-blue-50 border border-blue-200 rounded-md"
                        >
                            <div class="flex items-center">
                                <svg
                                    class="h-5 w-5 text-blue-400 mr-2"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <p class="text-sm text-blue-700">
                                    This applicant is a scholar with
                                    {{ selectedTrainee.scholarship_package }}
                                    package. Assessment fee is automatically set
                                    to ₱0.
                                </p>
                            </div>
                        </div>

                        <TextInput
                            id="assessment_fee"
                            v-model="form.assessment_fee"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full"
                            :class="{
                                'bg-gray-100':
                                    form.applicant_type === 'enrolled_trainee',
                            }"
                            placeholder="0.00"
                            :readonly="
                                form.applicant_type === 'enrolled_trainee'
                            "
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.assessment_fee"
                        />

                        <!-- General fee note for non-scholars -->
                        <p
                            v-if="form.applicant_type !== 'enrolled_trainee'"
                            class="text-xs text-gray-500 mt-1"
                        >
                            Set to 0 for free assessments. Scholar applicants
                            are automatically exempted.
                        </p>
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
                                ? "Creating..."
                                : "Create Assessment"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
