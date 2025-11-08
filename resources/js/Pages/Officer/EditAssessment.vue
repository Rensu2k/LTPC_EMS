<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";

const props = defineProps({
    assessment: Object,
    programs: Array,
    trainees: Array,
    trainers: Array,
});

const form = useForm({
    title: props.assessment.title || "",
    description: props.assessment.description || "",
    type: props.assessment.type || "",
    status: props.assessment.status || "pending",
    result: props.assessment.result || "",
    program_id: props.assessment.program_id || "",
    trainee_id: props.assessment.trainee_id || "",
    trainer_id: props.assessment.trainer_id || "",
    assessment_date: props.assessment.assessment_date
        ? props.assessment.assessment_date.split("T")[0]
        : "", // Format date for input
    // Required fields for backend validation
    applicant_type: props.assessment.applicant_type || "enrolled_trainee",
    external_applicant_name: props.assessment.external_applicant_name || "",
    external_applicant_email: props.assessment.external_applicant_email || "",
    external_applicant_phone: props.assessment.external_applicant_phone || "",
    assessment_fee: props.assessment.assessment_fee
        ? String(props.assessment.assessment_fee)
        : "0",
    payment_status: props.assessment.payment_status || "pending",
    payment_method: props.assessment.payment_method || "",
    payment_reference: props.assessment.payment_reference || "",
    payment_notes: props.assessment.payment_notes || "",
});

// Watch for changes to numeric fields and ensure they remain strings
watch(
    () => form.assessment_fee,
    (newValue) => {
        if (typeof newValue === "number") {
            form.assessment_fee = String(newValue);
        }
    }
);

// Auto-update status when result is selected
watch(
    () => form.result,
    (newResult) => {
        if (newResult && newResult !== "") {
            form.status = "completed";
        } else {
            form.status = "pending";
        }
    }
);

const submit = () => {
    // Convert numeric fields for backend
    form.transform((data) => ({
        ...data,
        assessment_fee: data.assessment_fee
            ? parseFloat(data.assessment_fee)
            : 0,
    }));

    form.put(route("officer.assessments.update", props.assessment.id), {
        onSuccess: () => {
            router.visit(route("officer.assessments"));
        },
        onError: (errors) => {
            // Handle validation errors
        },
    });
};

const cancel = () => {
    router.visit(route("officer.assessments"));
};

// Program and applicant are read-only in edit mode, so no need to watch for changes

// Computed property for filtered trainees based on selected program
const filteredTrainees = computed(() => {
    if (!form.program_id || !props.trainees) {
        return [];
    }

    // Find the selected program
    const selectedProgram = props.programs.find(
        (p) => p.program_id === form.program_id
    );
    if (!selectedProgram) {
        return [];
    }

    // Filter trainees who have completed the selected program or have that program qualification
    return props.trainees
        .filter((trainee) => {
            // Check if trainee has this program qualification (legacy system)
            if (trainee.program_qualification === selectedProgram.name) {
                return true;
            }

            // Modern enrollment system: Check if trainee has completed enrollments for this program
            if (trainee.enrollments && Array.isArray(trainee.enrollments)) {
                const hasCompletedThisProgram = trainee.enrollments.some(
                    (enrollment) =>
                        enrollment.status === "completed" &&
                        enrollment.program &&
                        enrollment.program.name === selectedProgram.name
                );
                if (hasCompletedThisProgram) {
                    return true;
                }
            }

            return false;
        })
        .map((trainee) => ({
            ...trainee,
            full_name: `${trainee.first_name} ${trainee.last_name}`,
        }));
});

// Computed property for filtered trainers based on selected program
const filteredTrainers = computed(() => {
    if (!form.program_id || !props.trainers) {
        return props.trainers; // Return all trainers if no program selected
    }

    // Find the selected program
    const selectedProgram = props.programs.find(
        (p) => p.program_id === form.program_id
    );
    if (!selectedProgram || !selectedProgram.assigned_trainers) {
        return []; // Return empty if program not found or no assigned trainers
    }

    // Filter trainers who are assigned to the selected program
    return props.trainers.filter((trainer) =>
        selectedProgram.assigned_trainers.includes(trainer.id)
    );
});
</script>

<template>
    <Head title="Edit Assessment" />

    <AuthenticatedLayout>
        <div class="p-8 max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <button
                    @click="cancel"
                    class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                </button>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Edit Assessment
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Update assessment information and grade
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-8">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Assessment Information Section -->
                    <div>
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"
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
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Assessment Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Assessment Title -->
                            <div class="md:col-span-2">
                                <InputLabel
                                    for="title"
                                    value="Assessment Title *"
                                />
                                <TextInput
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.title"
                                />
                            </div>

                            <!-- Assessment Description -->
                            <div class="md:col-span-2">
                                <InputLabel
                                    for="description"
                                    value="Description"
                                />
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
                                <InputLabel
                                    for="type"
                                    value="Assessment Type *"
                                />
                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="practical">Practical</option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.type"
                                />
                            </div>

                            <!-- Status is automatically managed: Pending → Completed when score is assigned -->

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
                    </div>

                    <!-- Participants Section -->
                    <div>
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"
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
                                    d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            Participants
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Program Display (Read-only) -->
                            <div>
                                <InputLabel value="Program" />
                                <div
                                    class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md"
                                >
                                    <span class="text-sm font-medium">
                                        {{
                                            programs.find(
                                                (p) =>
                                                    p.program_id ===
                                                    form.program_id
                                            )?.name || "Unknown Program"
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- Applicant Type Display -->
                            <div class="md:col-span-2">
                                <InputLabel
                                    for="applicant_type"
                                    value="Applicant Type"
                                />
                                <div
                                    class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md"
                                >
                                    <span class="text-sm font-medium">
                                        {{
                                            form.applicant_type ===
                                            "enrolled_trainee"
                                                ? "Enrolled Applicant"
                                                : "External Applicant"
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- Enrolled Trainee Display (Read-only) -->
                            <div
                                v-if="
                                    form.applicant_type === 'enrolled_trainee'
                                "
                            >
                                <InputLabel value="Applicant" />
                                <div
                                    class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md"
                                >
                                    <span class="text-sm font-medium">
                                        {{
                                            trainees.find(
                                                (t) => t.id == form.trainee_id
                                            )?.first_name +
                                                " " +
                                                trainees.find(
                                                    (t) =>
                                                        t.id == form.trainee_id
                                                )?.last_name ||
                                            "Unknown Trainee"
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- External Applicant Information (Read-only) -->
                            <div
                                v-if="
                                    form.applicant_type === 'external_applicant'
                                "
                                class="md:col-span-2"
                            >
                                <h4
                                    class="text-md font-medium text-gray-900 mb-4"
                                >
                                    External Applicant Information
                                </h4>
                                <div
                                    class="grid grid-cols-1 md:grid-cols-3 gap-4"
                                >
                                    <!-- Full Name -->
                                    <div>
                                        <InputLabel value="Full Name" />
                                        <div
                                            class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md"
                                        >
                                            <span class="text-sm font-medium">
                                                {{
                                                    form.external_applicant_name ||
                                                    "N/A"
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <InputLabel value="Email Address" />
                                        <div
                                            class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md"
                                        >
                                            <span class="text-sm font-medium">
                                                {{
                                                    form.external_applicant_email ||
                                                    "N/A"
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <InputLabel value="Phone Number" />
                                        <div
                                            class="mt-1 p-3 bg-gray-50 border border-gray-300 rounded-md"
                                        >
                                            <span class="text-sm font-medium">
                                                {{
                                                    form.external_applicant_phone ||
                                                    "N/A"
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    External applicant information cannot be
                                    changed during editing
                                </p>
                            </div>

                            <!-- Trainer Selection -->
                             <!-- Assessor should be dynamic -->
                            <div>
                                <SearchableSelect
                                    v-model="form.trainer_id"
                                    :options="filteredTrainers"
                                    label="Trainer/Assessor *" 
                                    placeholder="Type trainer name..."
                                    display-key="full_name"
                                    value-key="id"
                                    :required="true"
                                    :error="form.errors.trainer_id"
                                    :disabled="!form.program_id"
                                    :empty-message="
                                        !form.program_id
                                            ? 'Please select a program first'
                                            : 'No trainers assigned to this program'
                                    "
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Assessment Evaluation Section -->
                    <div class="bg-blue-50 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-blue-900 mb-4 flex items-center gap-2"
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
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Assessment Evaluation
                        </h3>

                        <!-- Payment Required Warning -->
                        <div
                            v-if="form.payment_status !== 'paid'"
                            class="mb-4 p-4 bg-amber-100 border border-amber-200 rounded-lg"
                        >
                            <div class="flex items-center gap-2">
                                <svg
                                    class="w-5 h-5 text-amber-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
                                    />
                                </svg>
                                <span class="text-amber-800 font-medium"
                                    >Payment Required</span
                                >
                            </div>
                            <p class="text-amber-700 text-sm mt-1">
                                Assessment evaluation is only available after
                                payment has been completed. Please ensure the
                                assessment fee is paid before proceeding with
                                evaluation.
                            </p>
                        </div>

                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-6"
                            :class="{
                                'opacity-50 pointer-events-none':
                                    form.payment_status !== 'paid',
                            }"
                        >
                            <!-- Assessment Result -->
                            <div>
                                <InputLabel
                                    for="result"
                                    value="Assessment Result"
                                />
                                <select
                                    id="result"
                                    v-model="form.result"
                                    :disabled="form.payment_status !== 'paid'"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                >
                                    <option value="">
                                        {{
                                            form.payment_status !== "paid"
                                                ? "Payment required for evaluation"
                                                : "Select assessment result"
                                        }}
                                    </option>
                                    <option
                                        value="competent"
                                        :disabled="
                                            form.payment_status !== 'paid'
                                        "
                                    >
                                        Competent
                                    </option>
                                    <option
                                        value="not_yet_competent"
                                        :disabled="
                                            form.payment_status !== 'paid'
                                        "
                                    >
                                        Not Yet Competent
                                    </option>
                                    <option
                                        value="absent"
                                        :disabled="
                                            form.payment_status !== 'paid'
                                        "
                                    >
                                        Absent
                                    </option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.result"
                                />
                                <p
                                    class="text-xs mt-1"
                                    :class="
                                        form.payment_status !== 'paid'
                                            ? 'text-amber-600'
                                            : 'text-blue-700'
                                    "
                                >
                                    {{
                                        form.payment_status !== "paid"
                                            ? "Complete payment to enable evaluation"
                                            : "Select the final assessment result"
                                    }}
                                </p>
                            </div>

                            <!-- Result Display -->
                            <div v-if="form.result">
                                <InputLabel value="Assessment Summary" />
                                <div
                                    class="mt-1 p-4 bg-white rounded-lg border"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-4 h-4 rounded-full"
                                            :class="{
                                                'bg-green-500':
                                                    form.result === 'competent',
                                                'bg-red-500':
                                                    form.result ===
                                                    'not_yet_competent',
                                                'bg-gray-500':
                                                    form.result === 'absent',
                                            }"
                                        ></div>
                                        <span
                                            class="text-lg font-semibold"
                                            :class="{
                                                'text-green-700':
                                                    form.result === 'competent',
                                                'text-red-700':
                                                    form.result ===
                                                    'not_yet_competent',
                                                'text-gray-700':
                                                    form.result === 'absent',
                                            }"
                                        >
                                            {{
                                                form.result === "competent"
                                                    ? "Competent"
                                                    : form.result ===
                                                      "not_yet_competent"
                                                    ? "Not Yet Competent"
                                                    : "Absent"
                                            }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-2">
                                        {{
                                            form.result === "competent"
                                                ? "The applicant has demonstrated competency in the assessment."
                                                : form.result ===
                                                  "not_yet_competent"
                                                ? "The applicant needs additional training or reassessment."
                                                : "The applicant was absent for the assessment."
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Status Display -->
                        <div class="mt-4 p-3 bg-white rounded-lg border">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700"
                                    >Assessment Status:</span
                                >
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="{
                                            'bg-yellow-100 text-yellow-800':
                                                form.status === 'pending',
                                            'bg-green-100 text-green-800':
                                                form.status === 'completed',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            form.status
                                                .charAt(0)
                                                .toUpperCase() +
                                            form.status.slice(1)
                                        }}
                                    </span>
                                    <span
                                        v-if="form.payment_status !== 'paid'"
                                        :class="{
                                            'bg-red-100 text-red-800':
                                                form.payment_status ===
                                                'pending',
                                            'bg-orange-100 text-orange-800':
                                                form.payment_status ===
                                                'refunded',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        Payment
                                        {{
                                            form.payment_status
                                                .charAt(0)
                                                .toUpperCase() +
                                            form.payment_status.slice(1)
                                        }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{
                                    form.payment_status !== "paid"
                                        ? "Payment must be completed before assessment can be evaluated"
                                        : 'Status automatically changes to "Completed" when a result is selected'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div
                        class="flex items-center justify-end gap-3 pt-6 border-t"
                    >
                        <SecondaryButton @click="cancel" type="button">
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
                                    ? "Updating..."
                                    : "Update Assessment"
                            }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
