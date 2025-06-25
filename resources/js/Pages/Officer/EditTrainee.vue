<template>
    <Head title="Edit Trainee" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Edit Trainee
                    </h1>
                    <p class="text-gray-600 mt-2">Update trainee information</p>
                </div>
                <SecondaryButton @click="goBack">
                    Back to Trainees
                </SecondaryButton>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6 max-h-screen overflow-y-auto">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- ULI and Entry Date -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded">
                        <div>
                            <InputLabel for="uli_number" value="Unique Learner Identifier (ULI) Number" />
                            <TextInput
                                id="uli_number"
                                v-model="form.uli_number"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.uli_number" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="entry_date" value="Entry Date" />
                            <TextInput
                                id="entry_date"
                                v-model="form.entry_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.entry_date" class="mt-2" />
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Personal Information</h3>

                        <!-- Name -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <InputLabel for="last_name" value="Last Name *" />
                                <TextInput
                                    id="last_name"
                                    v-model="form.last_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.last_name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="first_name" value="First Name *" />
                                <TextInput
                                    id="first_name"
                                    v-model="form.first_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.first_name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="middle_name" value="Middle Name" />
                                <TextInput
                                    id="middle_name"
                                    v-model="form.middle_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.middle_name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="extension" value="Extension (Jr., Sr.)" />
                                <TextInput
                                    id="extension"
                                    v-model="form.extension"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.extension" class="mt-2" />
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <InputLabel for="street_number" value="Number, Street" />
                                <TextInput
                                    id="street_number"
                                    v-model="form.street_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel for="barangay" value="Barangay" />
                                <TextInput
                                    id="barangay"
                                    v-model="form.barangay"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel for="district" value="District" />
                                <TextInput
                                    id="district"
                                    v-model="form.district"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <InputLabel for="city_municipality" value="City, Municipality" />
                                <TextInput
                                    id="city_municipality"
                                    v-model="form.city_municipality"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel for="province" value="Province" />
                                <TextInput
                                    id="province"
                                    v-model="form.province"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel for="region" value="Region" />
                                <TextInput
                                    id="region"
                                    v-model="form.region"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                        </div>

                        <!-- Contact and Basic Info -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <InputLabel for="email_facebook" value="Email / Facebook" />
                                <TextInput
                                    id="email_facebook"
                                    v-model="form.email_facebook"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel for="contact_number" value="Contact Number" />
                                <TextInput
                                    id="contact_number"
                                    v-model="form.contact_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel for="nationality" value="Nationality" />
                                <TextInput
                                    id="nationality"
                                    v-model="form.nationality"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                        </div>

                        <!-- Sex and Civil Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel for="sex" value="Sex" />
                                <select
                                    id="sex"
                                    v-model="form.sex"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Select...</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="civil_status" value="Civil Status" />
                                <select
                                    id="civil_status"
                                    v-model="form.civil_status"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Select...</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="separated">Separated</option>
                                    <option value="widowed">Widowed</option>
                                    <option value="common_law">Common Law</option>
                                </select>
                            </div>
                        </div>

                        <!-- Employment -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel for="employment_status" value="Employment Status" />
                                <select
                                    id="employment_status"
                                    v-model="form.employment_status"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Select...</option>
                                    <option value="wage_employed">Wage Employed</option>
                                    <option value="underemployed">Underemployed</option>
                                    <option value="self_employed">Self Employed</option>
                                    <option value="unemployed">Unemployed</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="employment_type" value="Employment Type" />
                                <select
                                    id="employment_type"
                                    v-model="form.employment_type"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Select...</option>
                                    <option value="none">None</option>
                                    <option value="casual">Casual</option>
                                    <option value="probationary">Probationary</option>
                                    <option value="contractual">Contractual</option>
                                    <option value="regular">Regular</option>
                                    <option value="job_order">Job Order</option>
                                    <option value="permanent">Permanent</option>
                                    <option value="temporary">Temporary</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Birth Information -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Birth Information</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <InputLabel for="birth_month" value="Birth Month" />
                                <select
                                    id="birth_month"
                                    v-model="form.birth_month"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Month</option>
                                    <option v-for="(month, index) in months" :key="index" :value="index + 1">
                                        {{ month }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="birth_day" value="Birth Day" />
                                <select
                                    id="birth_day"
                                    v-model="form.birth_day"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Day</option>
                                    <option v-for="day in 31" :key="day" :value="day">
                                        {{ day }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="birth_year" value="Birth Year" />
                                <select
                                    id="birth_year"
                                    v-model="form.birth_year"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Year</option>
                                    <option v-for="year in years" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="age" value="Age" />
                                <TextInput
                                    id="age"
                                    v-model="form.age"
                                    type="number"
                                    class="mt-1 block w-full"
                                    readonly
                                />
                            </div>
                        </div>

                        <!-- Birthplace -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <InputLabel for="birthplace_city" value="Birthplace City" />
                                <TextInput
                                    id="birthplace_city"
                                    v-model="form.birthplace_city"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel for="birthplace_province" value="Birthplace Province" />
                                <TextInput
                                    id="birthplace_province"
                                    v-model="form.birthplace_province"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel for="birthplace_region" value="Birthplace Region" />
                                <TextInput
                                    id="birthplace_region"
                                    v-model="form.birthplace_region"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Education -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Education</h3>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.education" value="elementary" class="mr-2" />
                                Elementary
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.education" value="secondary" class="mr-2" />
                                Secondary
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.education" value="post_secondary" class="mr-2" />
                                Post Secondary
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.education" value="technical_vocational" class="mr-2" />
                                Technical/Vocational
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.education" value="college" class="mr-2" />
                                College
                            </label>
                        </div>
                    </div>

                    <!-- Parent/Guardian -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Parent/Guardian Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="parent_guardian_name" value="Parent/Guardian Name" />
                                <TextInput
                                    id="parent_guardian_name"
                                    v-model="form.parent_guardian_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel for="parent_guardian_address" value="Parent/Guardian Address" />
                                <TextInput
                                    id="parent_guardian_address"
                                    v-model="form.parent_guardian_address"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Classification -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Classification</h3>
                        <div class="space-y-2 mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.classification" value="student" class="mr-2" />
                                Student
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.classification" value="out_of_school_youth" class="mr-2" />
                                Out of School Youth
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.classification" value="currently_employed" class="mr-2" />
                                Currently Employed
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.classification" value="family_member" class="mr-2" />
                                Family Member
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.classification" value="others" class="mr-2" />
                                Others
                            </label>
                        </div>
                        <div v-if="form.classification && form.classification.includes('others')">
                            <InputLabel for="classification_others" value="Please specify others" />
                            <TextInput
                                id="classification_others"
                                v-model="form.classification_others"
                                type="text"
                                class="mt-1 block w-full"
                            />
                        </div>
                    </div>

                    <!-- Disability -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Disability Information</h3>
                        <div class="mb-4">
                            <InputLabel value="Disability Type" />
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.disability_type" value="none" class="mr-2" />
                                    None
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.disability_type" value="visual" class="mr-2" />
                                    Visual
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.disability_type" value="hearing" class="mr-2" />
                                    Hearing
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.disability_type" value="speech" class="mr-2" />
                                    Speech
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.disability_type" value="physical" class="mr-2" />
                                    Physical
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.disability_type" value="intellectual" class="mr-2" />
                                    Intellectual
                                </label>
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Disability Causes" />
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.disability_causes" value="congenital" class="mr-2" />
                                    Congenital/Inborn
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.disability_causes" value="illness" class="mr-2" />
                                    Illness
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.disability_causes" value="injury" class="mr-2" />
                                    Injury
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Program and Scholarship -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Program and Scholarship Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel for="program_qualification" value="Program *" />
                                <select
                                    id="program_qualification"
                                    v-model="form.program_qualification"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="">Select a program...</option>
                                    <option
                                        v-for="program in programs"
                                        :key="program.id"
                                        :value="program.name"
                                    >
                                        {{ program.name }}{{ program.duration ? ` (${program.duration})` : "" }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.program_qualification" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="scholarship_package" value="Scholarship Package (TWSP, PESFA, STEP, others)" />
                                <TextInput
                                    id="scholarship_package"
                                    v-model="form.scholarship_package"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Enter scholarship package type"
                                />
                                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-2">
                                    <p class="text-sm text-green-700 font-medium">
                                        ✓ Scholars are automatically exempted from enrollment and assessment fees
                                    </p>
                                    <p class="text-xs text-green-600 mt-1">
                                        Payment status will be automatically set to "Paid" for trainees with scholarship packages
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status and Payment -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Status Information</h3>
                        
                        <!-- Enrollment Policy Notice -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-blue-700 font-medium">
                                📋 Enrollment Policy: Trainees can only be set to "Active" status after payment is completed.
                            </p>
                            <p class="text-xs text-blue-600 mt-1">
                                • Scholars with scholarship packages are automatically enrolled with paid status<br>
                                • Regular trainees require external payment processing before enrollment<br>
                                • Payment status is managed automatically by the system<br>
                                • Only enrollment status can be manually changed
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="status" value="Enrollment Status" />
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full border-gray-300 rounded-md"
                                >
                                    <option value="pending">Pending (Not Enrolled)</option>
                                    <option value="active">Active (Enrolled)</option>
                                    <option value="completed">Completed</option>
                                    <option value="dropped">Dropped</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    Active status requires payment to be completed
                                </p>
                            </div>
                            <div>
                                <InputLabel for="payment_status" value="Payment Status (Automatic)" />
                                <div class="mt-1 block w-full p-3 bg-gray-50 border border-gray-300 rounded-md">
                                    <span class="text-sm font-medium" :class="paymentStatusColor">
                                        {{ paymentStatusText }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Payment status is automatically managed by scholarships and payment system
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Requirements -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Requirements</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.requirements" value="birth_certificate" class="mr-2" />
                                    Birth Certificate (PSA)
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.requirements" value="marriage_contract" class="mr-2" />
                                    Marriage Contract (For Married Women)
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.requirements" value="high_school_diploma" class="mr-2" />
                                    High School Diploma/TOR/Form 137 or 138
                                </label>
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.requirements" value="drivers_license" class="mr-2" />
                                    Driver's License (For Driving Lesson)
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.requirements" value="good_moral" class="mr-2" />
                                    Good Moral (School/Barangay)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-4 pt-6 border-t">
                        <SecondaryButton @click="goBack" type="button">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="form.processing">
                            <span v-if="form.processing">Updating...</span>
                            <span v-else">Update Trainee</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { watch, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    trainee: Object,
    programs: Array,
});

// Format date for input field
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
};

const form = useForm({
    uli_number: props.trainee?.uli_number || "",
    entry_date: formatDate(props.trainee?.entry_date) || "",
    last_name: props.trainee?.last_name || "",
    first_name: props.trainee?.first_name || "",
    middle_name: props.trainee?.middle_name || "",
    extension: props.trainee?.extension || "",
    street_number: props.trainee?.street_number || "",
    barangay: props.trainee?.barangay || "",
    district: props.trainee?.district || "",
    city_municipality: props.trainee?.city_municipality || "",
    province: props.trainee?.province || "",
    region: props.trainee?.region || "",
    email_facebook: props.trainee?.email_facebook || "",
    contact_number: props.trainee?.contact_number || "",
    nationality: props.trainee?.nationality || "Filipino",
    sex: props.trainee?.sex || "",
    civil_status: props.trainee?.civil_status || "",
    employment_status: props.trainee?.employment_status || "",
    employment_type: props.trainee?.employment_type || "",
    birth_month: props.trainee?.birth_month || "",
    birth_day: props.trainee?.birth_day || "",
    birth_year: props.trainee?.birth_year || "",
    age: props.trainee?.age || "",
    birthplace_city: props.trainee?.birthplace_city || "",
    birthplace_province: props.trainee?.birthplace_province || "",
    birthplace_region: props.trainee?.birthplace_region || "",
    education: props.trainee?.education || [],
    parent_guardian_name: props.trainee?.parent_guardian_name || "",
    parent_guardian_address: props.trainee?.parent_guardian_address || "",
    classification: props.trainee?.classification || [],
    classification_others: props.trainee?.classification_others || "",
    disability_type: props.trainee?.disability_type || [],
    disability_causes: props.trainee?.disability_causes || [],
    program_qualification: props.trainee?.program_qualification || "",
    scholarship_package: props.trainee?.scholarship_package || "",
    requirements: props.trainee?.requirements || [],
    status: props.trainee?.status || "active",
});

// Months array
const months = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];

// Generate years (current year down to 1950)
const years = computed(() => {
    const currentYear = new Date().getFullYear();
    const yearArray = [];
    for (let year = currentYear; year >= 1950; year--) {
        yearArray.push(year);
    }
    return yearArray;
});

// Payment status display (read-only)
const paymentStatusText = computed(() => {
    const status = props.trainee?.payment_status || 'unpaid';
    return status.charAt(0).toUpperCase() + status.slice(1);
});

const paymentStatusColor = computed(() => {
    const status = props.trainee?.payment_status || 'unpaid';
    return {
        'text-green-600': status === 'paid',
        'text-red-600': status === 'unpaid'
    };
});

// Function to calculate age based on birth date
const calculateAge = () => {
    if (form.birth_year && form.birth_month && form.birth_day) {
        const birthDate = new Date(
            parseInt(form.birth_year),
            parseInt(form.birth_month) - 1, // Month is 0-indexed in JavaScript Date
            parseInt(form.birth_day)
        );

        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();

        // Adjust age if birthday hasn't occurred this year yet
        if (
            monthDiff < 0 ||
            (monthDiff === 0 && today.getDate() < birthDate.getDate())
        ) {
            age--;
        }

        // Only update if age is valid (positive number)
        if (age >= 0 && age <= 150) {
            form.age = age.toString();
        }
    }
};

// Watch for changes in birth date fields and auto-calculate age
watch(
    [() => form.birth_year, () => form.birth_month, () => form.birth_day],
    () => {
        calculateAge();
    }
);

const submitForm = () => {
    form.put(route("officer.trainees.update", props.trainee.id), {
        onSuccess: () => {
            router.visit("/officer/trainees");
        },
    });
};

const goBack = () => {
    router.visit("/officer/trainees");
};
</script>
