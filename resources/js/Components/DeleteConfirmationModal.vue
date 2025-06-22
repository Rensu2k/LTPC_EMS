<template>
    <Modal :show="show" @close="closeModal" max-width="md">
        <div class="p-6">
            <!-- Warning Icon -->
            <div
                class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4"
            >
                <svg
                    class="h-6 w-6 text-red-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.081 15.5c-.77.833.192 2.5 1.732 2.5z"
                    />
                </svg>
            </div>

            <!-- Title -->
            <h3 class="text-lg font-medium text-gray-900 text-center mb-2">
                {{
                    title ||
                    `Delete ${
                        itemType?.charAt(0).toUpperCase() + itemType?.slice(1)
                    }`
                }}
            </h3>

            <!-- Message -->
            <div class="text-center mb-6">
                <p class="text-sm text-gray-500 mb-3">
                    {{
                        message ||
                        `Are you sure you want to delete this ${itemType}? This action cannot be undone.`
                    }}
                </p>

                <!-- Item Details -->
                <div class="bg-gray-50 rounded-lg p-4 text-left">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-full bg-red-500 flex items-center justify-center text-white font-semibold text-sm"
                        >
                            {{
                                getItemName()
                                    .split(" ")
                                    .map((n) => n[0])
                                    .join("")
                            }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ getItemName() }}
                            </p>
                            <p class="text-sm text-gray-500">
                                ID: {{ getItemId() }}
                            </p>
                            <p
                                class="text-sm text-gray-500"
                                v-if="getItemDetails()"
                            >
                                {{ getItemDetails() }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Warning message for specific cases -->
                <div
                    v-if="!canDelete()"
                    class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg"
                >
                    <p class="text-sm text-red-700">
                        ⚠️ {{ getWarningMessage() }}
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3">
                <SecondaryButton @click="closeModal" type="button">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    @click="confirmDelete"
                    :disabled="processing || !canDelete()"
                    class="bg-red-600 hover:bg-red-700 disabled:bg-gray-400"
                >
                    {{ processing ? "Deleting..." : "Delete" }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    show: Boolean,
    item: Object, // Can be trainee, trainer, or program
    itemType: String, // 'trainee', 'trainer', or 'program'
    title: String,
    message: String,
});

const emit = defineEmits(["close", "confirm"]);

const processing = ref(false);

const closeModal = () => {
    emit("close");
};

const confirmDelete = () => {
    processing.value = true;
    emit("confirm");
};

const getItemName = () => {
    if (props.itemType === "trainee") {
        return `${props.item?.first_name || ""} ${
            props.item?.last_name || ""
        }`.trim();
    } else if (props.itemType === "trainer") {
        return props.item?.full_name || "";
    } else if (props.itemType === "program") {
        return props.item?.name || "";
    }
    return "this item";
};

const getItemId = () => {
    if (props.itemType === "trainee") {
        return (
            props.item?.uli_number ||
            `T-${String(props.item?.id).padStart(4, "0")}`
        );
    } else if (props.itemType === "trainer") {
        return `TR${String(props.item?.id).padStart(3, "0")}`;
    } else if (props.itemType === "program") {
        return `P${String(props.item?.id).padStart(3, "0")}`;
    }
    return `#${props.item?.id}`;
};

const getItemDetails = () => {
    if (props.itemType === "trainer") {
        return props.item?.program;
    } else if (props.itemType === "program") {
        return `${props.item?.duration} • ${
            props.item?.enrollments || 0
        } enrollments`;
    }
    return null;
};

const canDelete = () => {
    if (
        props.itemType === "trainee" &&
        ["completed", "dropped", "suspended"].includes(props.item?.status)
    ) {
        return false;
    }
    if (props.itemType === "program" && props.item?.enrollments > 0) {
        return false;
    }
    return true;
};

const getWarningMessage = () => {
    if (
        props.itemType === "trainee" &&
        ["completed", "dropped", "suspended"].includes(props.item?.status)
    ) {
        return `This trainee has a status of "${props.item.status}" and cannot be deleted.`;
    }
    if (props.itemType === "program" && props.item?.enrollments > 0) {
        return `This program has ${props.item.enrollments} active enrollment${
            props.item.enrollments === 1 ? "" : "s"
        } and cannot be deleted.`;
    }
    return null;
};
</script>
