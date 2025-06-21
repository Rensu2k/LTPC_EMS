<template>
    <Modal
        :show="show"
        @close="closeModal"
        custom-width="80vw"
        :close-on-click-outside="false"
    >
        <div class="p-6 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    Trainee Registration Form
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

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- ULI and Entry Date -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded"
                >
                    <div>
                        <InputLabel
                            for="uli_number"
                            value="Unique Learner Identifier (ULI) Number"
                        />
                        <TextInput
                            id="uli_number"
                            v-model="form.uli_number"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            :message="form.errors.uli_number"
                            class="mt-2"
                        />
                    </div>
                    <div>
                        <InputLabel for="entry_date" value="Entry Date" />
                        <TextInput
                            id="entry_date"
                            v-model="form.entry_date"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            :message="form.errors.entry_date"
                            class="mt-2"
                        />
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">
                        Personal Information
                    </h3>

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
                            <InputError
                                :message="form.errors.last_name"
                                class="mt-2"
                            />
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
                            <InputError
                                :message="form.errors.first_name"
                                class="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel for="middle_name" value="Middle Name" />
                            <TextInput
                                id="middle_name"
                                v-model="form.middle_name"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                :message="form.errors.middle_name"
                                class="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="extension"
                                value="Extension (Jr., Sr.)"
                            />
                            <TextInput
                                id="extension"
                                v-model="form.extension"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                :message="form.errors.extension"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <InputLabel
                                for="street_number"
                                value="Number, Street"
                            />
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
                            <InputLabel
                                for="city_municipality"
                                value="City, Municipality"
                            />
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

                    <!-- Contact Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <InputLabel
                                for="email_facebook"
                                value="Email address/Facebook"
                            />
                            <TextInput
                                id="email_facebook"
                                v-model="form.email_facebook"
                                type="text"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="contact_number"
                                value="Contact Number"
                            />
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
                                value="Filipino"
                            />
                        </div>
                    </div>

                    <!-- Sex, Civil Status, Employment -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Sex -->
                        <div>
                            <InputLabel value="Sex" class="mb-2" />
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.sex"
                                        value="male"
                                        class="mr-2"
                                    />
                                    Male
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.sex"
                                        value="female"
                                        class="mr-2"
                                    />
                                    Female
                                </label>
                            </div>
                        </div>

                        <!-- Civil Status -->
                        <div>
                            <InputLabel value="3.2 Civil Status" class="mb-2" />
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.civil_status"
                                        value="single"
                                        class="mr-2"
                                    />
                                    Single
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.civil_status"
                                        value="married"
                                        class="mr-2"
                                    />
                                    Married
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.civil_status"
                                        value="separated"
                                        class="mr-2"
                                    />
                                    Separated/Divorced/Annulled
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.civil_status"
                                        value="widowed"
                                        class="mr-2"
                                    />
                                    Widow/er
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="form.civil_status"
                                        value="common_law"
                                        class="mr-2"
                                    />
                                    Common Law/Live-in
                                </label>
                            </div>
                        </div>

                        <!-- Employment -->
                        <div>
                            <InputLabel
                                value="3.3 Employment (before the training)"
                                class="mb-2"
                            />
                            <div class="mb-3">
                                <p class="text-sm font-medium mb-2">
                                    Employment Status:
                                </p>
                                <div class="space-y-1">
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_status"
                                            value="wage_employed"
                                            class="mr-2"
                                        />
                                        Wage-Employed
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_status"
                                            value="underemployed"
                                            class="mr-2"
                                        />
                                        Underemployed
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_status"
                                            value="self_employed"
                                            class="mr-2"
                                        />
                                        Self-Employed
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_status"
                                            value="unemployed"
                                            class="mr-2"
                                        />
                                        Unemployed
                                    </label>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium mb-2">
                                    Employment Type:
                                </p>
                                <div class="space-y-1">
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_type"
                                            value="none"
                                            class="mr-2"
                                        />
                                        None
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_type"
                                            value="casual"
                                            class="mr-2"
                                        />
                                        Casual
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_type"
                                            value="probationary"
                                            class="mr-2"
                                        />
                                        Probationary
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_type"
                                            value="contractual"
                                            class="mr-2"
                                        />
                                        Contractual
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_type"
                                            value="regular"
                                            class="mr-2"
                                        />
                                        Regular
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_type"
                                            value="job_order"
                                            class="mr-2"
                                        />
                                        Job Order
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_type"
                                            value="permanent"
                                            class="mr-2"
                                        />
                                        Permanent
                                    </label>
                                    <label class="flex items-center text-sm">
                                        <input
                                            type="radio"
                                            v-model="form.employment_type"
                                            value="temporary"
                                            class="mr-2"
                                        />
                                        Temporary
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Birth Information -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <InputLabel for="birth_month" value="Birth Month" />
                            <select
                                id="birth_month"
                                v-model="form.birth_month"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select Month</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="birth_day" value="Birth Day" />
                            <TextInput
                                id="birth_day"
                                v-model="form.birth_day"
                                type="number"
                                min="1"
                                max="31"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel for="birth_year" value="Birth Year" />
                            <TextInput
                                id="birth_year"
                                v-model="form.birth_year"
                                type="number"
                                min="1900"
                                max="2010"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="age"
                                value="Age (Auto-calculated)"
                            />
                            <TextInput
                                id="age"
                                v-model="form.age"
                                type="number"
                                class="mt-1 block w-full bg-gray-100"
                                readonly
                                placeholder="Enter birth date to calculate age"
                            />
                        </div>
                    </div>

                    <!-- Birthplace -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <InputLabel
                                for="birthplace_city"
                                value="Birthplace - City/Municipality"
                            />
                            <TextInput
                                id="birthplace_city"
                                v-model="form.birthplace_city"
                                type="text"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="birthplace_province"
                                value="Province"
                            />
                            <TextInput
                                id="birthplace_province"
                                v-model="form.birthplace_province"
                                type="text"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="birthplace_region"
                                value="Region"
                            />
                            <TextInput
                                id="birthplace_region"
                                v-model="form.birthplace_region"
                                type="text"
                                class="mt-1 block w-full"
                            />
                        </div>
                    </div>

                    <!-- Educational Attainment -->
                    <div class="border-t pt-6 mt-6">
                        <h3 class="text-lg font-semibold mb-4">
                            3.6 Educational Attainment Before the Training
                            (Trainee)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="no_grade_completed"
                                        class="mr-2"
                                    />
                                    No Grade Completed
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="elementary_undergraduate"
                                        class="mr-2"
                                    />
                                    Elementary Undergraduate
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="elementary_graduate"
                                        class="mr-2"
                                    />
                                    Elementary Graduate
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="high_school_undergraduate"
                                        class="mr-2"
                                    />
                                    High School Undergraduate
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="high_school_graduate"
                                        class="mr-2"
                                    />
                                    High School Graduate
                                </label>
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="junior_high"
                                        class="mr-2"
                                    />
                                    Junior High (K-12)
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="senior_high"
                                        class="mr-2"
                                    />
                                    Senior High (K-12)
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="post_secondary_technical_undergrad"
                                        class="mr-2"
                                    />
                                    Post-Secondary Non-Tertiary Technical
                                    Vocational Course Undergraduate
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="post_secondary_technical_graduate"
                                        class="mr-2"
                                    />
                                    Post-Secondary Non-Tertiary Technical
                                    Vocational Course Graduate
                                </label>
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="college_undergraduate"
                                        class="mr-2"
                                    />
                                    College Undergraduate
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="college_graduate"
                                        class="mr-2"
                                    />
                                    College Graduate
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="masteral"
                                        class="mr-2"
                                    />
                                    Masteral
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.education"
                                        value="doctorate"
                                        class="mr-2"
                                    />
                                    Doctorate
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Parent/Guardian -->
                    <div class="border-t pt-6 mt-6">
                        <h3 class="text-lg font-semibold mb-4">
                            3.7 Parent/Guardian
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel
                                    for="parent_guardian_name"
                                    value="Name"
                                />
                                <TextInput
                                    id="parent_guardian_name"
                                    v-model="form.parent_guardian_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    for="parent_guardian_address"
                                    value="Complete Permanent Mailing Address"
                                />
                                <TextInput
                                    id="parent_guardian_address"
                                    v-model="form.parent_guardian_address"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Learner/Trainee Classification -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">
                        4. Learner/Trainee/Student (Clients) Classification:
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="4ps_beneficiary"
                                    class="mr-2"
                                />
                                4Ps Beneficiary
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="displaced_workers"
                                    class="mr-2"
                                />
                                Displaced Workers
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="family_member_afp_pnp"
                                    class="mr-2"
                                />
                                Family Member of AFP and PNP wounded in action
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="industry_workers"
                                    class="mr-2"
                                />
                                Industry Workers
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="out_of_school_youth"
                                    class="mr-2"
                                />
                                Out-of-School-Youth
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="rebel_returnees"
                                    class="mr-2"
                                />
                                Rebel Returnees/Decommissioned Combatants
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="tesda_alumni"
                                    class="mr-2"
                                />
                                TESDA Alumni
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="victim_disasters"
                                    class="mr-2"
                                />
                                Victim of Natural Disasters and Calamities
                            </label>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="agrarian_reform"
                                    class="mr-2"
                                />
                                Agrarian Reform Beneficiary
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="drug_dependents"
                                    class="mr-2"
                                />
                                Drug Dependents Surrenderees/Surrenderers
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="farmers_fishermen"
                                    class="mr-2"
                                />
                                Farmers and Fishermen
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="inmates_detainees"
                                    class="mr-2"
                                />
                                Inmates and Detainees
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="overseas_filipino_dependent"
                                    class="mr-2"
                                />
                                Overseas Filipino Workers (OFW) Dependent
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="returning_ofw"
                                    class="mr-2"
                                />
                                Returning/Repatriated Overseas Filipino Workers
                                (OFW)
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="tvet_trainers"
                                    class="mr-2"
                                />
                                TVET Trainers
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="wounded_afp_pnp"
                                    class="mr-2"
                                />
                                Wounded-in-Action AFP & PNP Personnel
                            </label>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="balik_probinsya"
                                    class="mr-2"
                                />
                                Balik Probinsya
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="family_afp_pnp_killed"
                                    class="mr-2"
                                />
                                Family Member of AFP and PNP Killed-in-action
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="indigenous_people"
                                    class="mr-2"
                                />
                                Indigenous People and Cultural Communities
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="milf_beneficiary"
                                    class="mr-2"
                                />
                                MILF Beneficiary
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="rcef_rsbsa"
                                    class="mr-2"
                                />
                                RCEF-RSBSA
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="student"
                                    class="mr-2"
                                />
                                Student
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="uniformed_personnel"
                                    class="mr-2"
                                />
                                Uniformed Personnel
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.classification"
                                    value="others"
                                    class="mr-2"
                                />
                                Others:
                                <TextInput
                                    v-model="form.classification_others"
                                    type="text"
                                    class="ml-2 w-32"
                                    placeholder="(Please Specify)"
                                />
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Type of Disability -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">
                        5. Type of Disability (for Persons with Disability
                        Only):
                        <span class="text-sm font-normal italic"
                            >To filled up by the TESDA personnel</span
                        >
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.disability_type"
                                    value="mental_intellectual"
                                    class="mr-2"
                                />
                                Mental/Intellectual
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.disability_type"
                                    value="hearing_disability"
                                    class="mr-2"
                                />
                                Hearing Disability
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.disability_type"
                                    value="psychological_disability"
                                    class="mr-2"
                                />
                                Psychological Disability
                            </label>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.disability_type"
                                    value="visual_disability"
                                    class="mr-2"
                                />
                                Visual Disability
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.disability_type"
                                    value="speech_impairment"
                                    class="mr-2"
                                />
                                Speech Impairment
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.disability_type"
                                    value="disability_chronic_illness"
                                    class="mr-2"
                                />
                                Disability Due to Chronic Illness
                            </label>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.disability_type"
                                    value="orthopedic_disability"
                                    class="mr-2"
                                />
                                Orthopedic (Musculoskeletal) Disability
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.disability_type"
                                    value="multiple_disabilities"
                                    class="mr-2"
                                />
                                Multiple Disabilities, specify
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.disability_type"
                                    value="learning_disability"
                                    class="mr-2"
                                />
                                Learning Disability
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Causes of Disability -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">
                        6. Causes of Disability (for Persons with Disability
                        Only):
                        <span class="text-sm font-normal italic"
                            >To filled up by the TESDA personnel</span
                        >
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.disability_causes"
                                value="congenital_inborn"
                                class="mr-2"
                            />
                            Congenital/Inborn
                        </label>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.disability_causes"
                                value="illness"
                                class="mr-2"
                            />
                            Illness
                        </label>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.disability_causes"
                                value="injury"
                                class="mr-2"
                            />
                            Injury
                        </label>
                    </div>
                </div>

                <!-- Course/Qualification -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">
                        Course Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                for="course_qualification"
                                value="Name of Course/Qualification *"
                            />
                            <select
                                id="course_qualification"
                                v-model="form.course_qualification"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="">Select a course...</option>
                                <option
                                    v-for="course in courses"
                                    :key="course.course_id"
                                    :value="course.name"
                                >
                                    {{ course.name
                                    }}{{
                                        course.duration
                                            ? ` (${course.duration})`
                                            : ""
                                    }}
                                </option>
                            </select>
                            <InputError
                                :message="form.errors.course_qualification"
                                class="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="scholarship_package"
                                value="Scholarship Package (TWSP, PESFA, STEP, others)"
                            />
                            <TextInput
                                id="scholarship_package"
                                v-model="form.scholarship_package"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Enter scholarship package type"
                            />
                            <div
                                class="bg-green-50 border border-green-200 rounded-lg p-3 mt-2"
                            >
                                <p class="text-sm text-green-700 font-medium">
                                    ✓ Scholars are automatically exempted from
                                    enrollment and assessment fees
                                </p>
                                <p class="text-xs text-green-600 mt-1">
                                    Payment status will be automatically set to
                                    "Paid" for trainees with scholarship
                                    packages
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Requirements -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">9. Requirements</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.requirements"
                                    value="birth_certificate"
                                    class="mr-2"
                                />
                                Birth Certificate (PSA)
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.requirements"
                                    value="marriage_contract"
                                    class="mr-2"
                                />
                                Marriage Contract (For Married Women)
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.requirements"
                                    value="high_school_diploma"
                                    class="mr-2"
                                />
                                High School Diploma/TOR/Form 137 or 138
                            </label>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.requirements"
                                    value="drivers_license"
                                    class="mr-2"
                                />
                                Driver's License (For Driving Lesson)
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.requirements"
                                    value="good_moral"
                                    class="mr-2"
                                />
                                Good Moral (School/Barangay)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4 pt-6 border-t">
                    <SecondaryButton @click="closeModal" type="button">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">
                        <span v-if="form.processing">Registering...</span>
                        <span v-else>Register Trainee</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

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
    courses: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "submitted"]);

const form = useForm({
    uli_number: "",
    entry_date: "",
    last_name: "",
    first_name: "",
    middle_name: "",
    extension: "",
    street_number: "",
    barangay: "",
    district: "",
    city_municipality: "",
    province: "",
    region: "",
    email_facebook: "",
    contact_number: "",
    nationality: "Filipino",
    sex: "",
    civil_status: "",
    employment_status: "",
    employment_type: "",
    birth_month: "",
    birth_day: "",
    birth_year: "",
    age: "",
    birthplace_city: "",
    birthplace_province: "",
    birthplace_region: "",
    education: [],
    parent_guardian_name: "",
    parent_guardian_address: "",
    classification: [],
    classification_others: "",
    disability_type: [],
    disability_causes: [],
    course_qualification: "",
    scholarship_package: "",
    requirements: [],
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
    form.post(route("officer.trainees.store"), {
        onSuccess: () => {
            closeModal();
            emit("submitted");
            form.reset();
        },
    });
};

const closeModal = () => {
    emit("close");
    form.reset();
    form.clearErrors();
};
</script>
