<script setup>
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    show: Boolean,
    program: Object,
    trainers: Array,
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "assigned"]);

const form = useForm({
    name: "",
    description: "",
    duration: "",
    prerequisites: "",
    assigned_trainers: [],
    status: "active",
    enrollment_fee: 0,
    start_date: null,
    end_date: null,
});

// Filter trainers based on program expertise
const availableTrainers = computed(() => {
    if (!props.program || !props.trainers) return [];

    return props.trainers.filter((trainer) => {
        // Check if trainer has expertise that matches or relates to the program
        if (trainer.expertise && Array.isArray(trainer.expertise)) {
            return trainer.expertise.some(
                (expertise) =>
                    expertise
                        .toLowerCase()
                        .includes(props.program.name.toLowerCase()) ||
                    props.program.name
                        .toLowerCase()
                        .includes(expertise.toLowerCase())
            );
        }
        return false;
    });
});

// Watch for program changes to reset form
watch(
    () => props.program,
    (newProgram) => {
        if (newProgram) {
            form.name = newProgram.name || "";
            form.description = newProgram.description || "";
            form.duration = newProgram.duration || "";
            form.prerequisites = newProgram.prerequisites || "";
            form.assigned_trainers = [...(newProgram.assigned_trainers || [])];
            form.status = newProgram.status || "active";
            form.enrollment_fee = newProgram.enrollment_fee || 0;
            form.start_date = newProgram.start_date || null;
            form.end_date = newProgram.end_date || null;
        }
    },
    { immediate: true }
);

const close = () => {
    emit("close");
    form.reset();
};

const submit = () => {
    const routeName = props.isAdmin
        ? "admin.programs.update"
        : "officer.programs.update";
    form.put(route(routeName, props.program?.program_id), {
        onSuccess: () => {
            emit("assigned");
            close();
        },
        preserveScroll: true,
    });
};

const toggleTrainer = (trainerId) => {
    const index = form.assigned_trainers.indexOf(trainerId);
    if (index > -1) {
        form.assigned_trainers.splice(index, 1);
    } else {
        form.assigned_trainers.push(trainerId);
    }
};

const isTrainerSelected = (trainerId) => {
    return form.assigned_trainers.includes(trainerId);
};

const getTrainerExpertiseString = (trainer) => {
    if (!trainer.expertise || !Array.isArray(trainer.expertise))
        return "No expertise listed";
    return trainer.expertise.join(", ");
};
</script>

<template>
    <Modal :show="show" @close="close" :max-width="'2xl'">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Assign Trainers
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Assign trainers to "{{ program?.name }}"
                    </p>
                </div>
                <button
                    @click="close"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Available Trainers" />
                        <p class="text-sm text-gray-500 mb-3">
                            Select trainers with relevant expertise for this
                            program
                        </p>

                        <div
                            v-if="availableTrainers.length === 0"
                            class="text-center py-8"
                        >
                            <svg
                                class="mx-auto h-12 w-12 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No trainers available
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                No trainers found with expertise matching this
                                program.
                            </p>
                        </div>

                        <div v-else class="space-y-3 max-h-64 overflow-y-auto">
                            <div
                                v-for="trainer in availableTrainers"
                                :key="trainer.id"
                                class="flex items-start space-x-3 p-3 border rounded-lg hover:bg-gray-50 transition-colors"
                                :class="{
                                    'border-blue-200 bg-blue-50':
                                        isTrainerSelected(trainer.id),
                                    'border-gray-200': !isTrainerSelected(
                                        trainer.id
                                    ),
                                }"
                            >
                                <input
                                    :id="`trainer-${trainer.id}`"
                                    type="checkbox"
                                    :checked="isTrainerSelected(trainer.id)"
                                    @change="toggleTrainer(trainer.id)"
                                    class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label
                                    :for="`trainer-${trainer.id}`"
                                    class="flex-1 cursor-pointer"
                                >
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ trainer.name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Expertise:
                                        {{ getTrainerExpertiseString(trainer) }}
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="form.assigned_trainers.length > 0"
                        class="mt-4 p-3 bg-blue-50 rounded-lg"
                    >
                        <p class="text-sm font-medium text-blue-900">
                            Selected:
                            {{ form.assigned_trainers.length }} trainer(s)
                        </p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    <SecondaryButton @click="close" :disabled="form.processing">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        type="submit"
                        :disabled="form.processing"
                        :class="{ 'opacity-50': form.processing }"
                    >
                        <span v-if="form.processing">Assigning...</span>
                        <span v-else>Assign Trainers</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
