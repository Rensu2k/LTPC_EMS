<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

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
    status: props.assessment.status || "",
    score: props.assessment.score ? String(props.assessment.score) : "",
    max_score: props.assessment.max_score
        ? String(props.assessment.max_score)
        : "",
    passing_score: props.assessment.passing_score
        ? String(props.assessment.passing_score)
        : "75",
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

const percentage = computed(() => {
    const score = parseFloat(form.score);
    const maxScore = parseFloat(form.max_score);
    if (score && maxScore && score > 0 && maxScore > 0) {
        return Math.round((score / maxScore) * 100);
    }
    return null;
});

const grade = computed(() => {
    const percent = percentage.value;
    if (percent === null) return "N/A";

    if (percent >= 90) return "A";
    if (percent >= 80) return "B";
    if (percent >= 70) return "C";
    if (percent >= 60) return "D";
    return "F";
});

const passFailStatus = computed(() => {
    const score = parseFloat(form.score);
    const passingScore = parseFloat(form.passing_score);

    if (score && passingScore && score >= 0 && passingScore > 0) {
        return score >= passingScore ? "pass" : "fail";
    }
    return null;
});

// Watch for changes to numeric fields and ensure they remain strings
watch(
    () => form.score,
    (newValue) => {
        if (typeof newValue === "number") {
            form.score = String(newValue);
        }
    }
);

watch(
    () => form.max_score,
    (newValue) => {
        if (typeof newValue === "number") {
            form.max_score = String(newValue);
        }
    }
);

watch(
    () => form.assessment_fee,
    (newValue) => {
        if (typeof newValue === "number") {
            form.assessment_fee = String(newValue);
        }
    }
);

watch(
    () => form.passing_score,
    (newValue) => {
        if (typeof newValue === "number") {
            form.passing_score = String(newValue);
        }
    }
);

// Auto-update status when score or passing score changes
watch([() => form.score, () => form.passing_score], () => {
    if (form.score && form.passing_score) {
        const score = parseFloat(form.score);
        const passingScore = parseFloat(form.passing_score);

        if (score >= 0 && passingScore > 0) {
            form.status = score >= passingScore ? "pass" : "fail";
        }
    }
});

const submit = () => {
    // Convert numeric fields for backend
    form.transform((data) => ({
        ...data,
        score: data.score ? parseFloat(data.score) : null,
        max_score: data.max_score ? parseFloat(data.max_score) : null,
        passing_score: data.passing_score
            ? parseFloat(data.passing_score)
            : null,
        assessment_fee: data.assessment_fee
            ? parseFloat(data.assessment_fee)
            : 0,
    }));

    form.put(route("officer.assessments.update", props.assessment.id), {
        onSuccess: () => {
            router.visit(route("officer.assessments"));
        },
        onError: (errors) => {
            console.log("Assessment update errors:", errors);
        },
    });
};

const cancel = () => {
    router.visit(route("officer.assessments"));
};
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
                                    <option value="theoretical">
                                        Theoretical
                                    </option>
                                    <option value="practical">Practical</option>
                                    <option value="both">
                                        Both (Theoretical & Practical)
                                    </option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.type"
                                />
                            </div>

                            <!-- Assessment Status -->
                            <div>
                                <InputLabel for="status" value="Status *" />
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="graded">Graded</option>
                                    <option value="pass">Pass</option>
                                    <option value="fail">Fail</option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.status"
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

                            <!-- Maximum Score -->
                            <div>
                                <InputLabel
                                    for="max_score"
                                    value="Maximum Score *"
                                />
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
                                    Minimum score required to pass
                                </p>
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
                            <!-- Program Selection -->
                            <div>
                                <InputLabel
                                    for="program_id"
                                    value="Program *"
                                />
                                <select
                                    id="program_id"
                                    v-model="form.program_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select a program</option>
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

                            <!-- Applicant Selection -->
                            <div>
                                <InputLabel
                                    for="trainee_id"
                                    value="Applicant *"
                                />
                                <select
                                    id="trainee_id"
                                    v-model="form.trainee_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">
                                        Select an applicant
                                    </option>
                                    <option
                                        v-for="trainee in trainees"
                                        :key="trainee.id"
                                        :value="trainee.id"
                                    >
                                        {{ trainee.first_name }}
                                        {{ trainee.last_name }}
                                    </option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.trainee_id"
                                />
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
                        </div>
                    </div>

                    <!-- Grading Section -->
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
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                            Assessment Grading
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Score Input -->
                            <div>
                                <InputLabel for="score" value="Score" />
                                <TextInput
                                    id="score"
                                    v-model="form.score"
                                    type="number"
                                    class="mt-1 block w-full"
                                    :max="form.max_score"
                                    min="0"
                                    placeholder="Enter score (optional)"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.score"
                                />
                                <p class="text-xs text-blue-700 mt-1">
                                    Score out of {{ form.max_score }} points
                                </p>
                            </div>

                            <!-- Grade Display -->
                            <div v-if="percentage !== null">
                                <InputLabel value="Assessment Results" />
                                <div
                                    class="mt-1 p-3 bg-white rounded-lg border"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm text-gray-600"
                                            >Percentage:</span
                                        >
                                        <span class="font-semibold"
                                            >{{ percentage }}%</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between mt-2"
                                    >
                                        <span class="text-sm text-gray-600"
                                            >Grade:</span
                                        >
                                        <span
                                            class="text-lg font-bold"
                                            :class="{
                                                'text-green-600':
                                                    grade === 'A' ||
                                                    grade === 'B',
                                                'text-yellow-600':
                                                    grade === 'C',
                                                'text-red-600':
                                                    grade === 'D' ||
                                                    grade === 'F',
                                            }"
                                        >
                                            {{ grade }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="passFailStatus !== null"
                                        class="flex items-center justify-between mt-2 pt-2 border-t"
                                    >
                                        <span class="text-sm text-gray-600"
                                            >Result:</span
                                        >
                                        <span
                                            class="text-lg font-bold uppercase tracking-wide"
                                            :class="{
                                                'text-green-600 bg-green-100 px-2 py-1 rounded':
                                                    passFailStatus === 'pass',
                                                'text-red-600 bg-red-100 px-2 py-1 rounded':
                                                    passFailStatus === 'fail',
                                            }"
                                        >
                                            {{ passFailStatus }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">
                                        Passing score:
                                        {{ form.passing_score }} points
                                    </p>
                                </div>
                            </div>
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
