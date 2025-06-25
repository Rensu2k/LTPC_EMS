<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    options: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: "Type to search...",
    },
    emptyMessage: {
        type: String,
        default: "No options available",
    },
    displayKey: {
        type: String,
        default: "name",
    },
    valueKey: {
        type: String,
        default: "id",
    },
    secondaryKey: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const searchQuery = ref("");
const searchInput = ref(null);
const dropdown = ref(null);
const highlightedIndex = ref(-1);

// Get selected option
const selectedOption = computed(() => {
    return props.options.find(
        (option) => option[props.valueKey] == props.modelValue
    );
});

// Display text for selected option in input
const displayValue = computed(() => {
    if (!selectedOption.value) return searchQuery.value;

    // When not searching, show selected option name
    if (!isOpen.value || searchQuery.value === "") {
        return selectedOption.value[props.displayKey];
    }

    // When searching, show search query
    return searchQuery.value;
});

// Filtered options based on search
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;

    const query = searchQuery.value.toLowerCase();
    return props.options.filter((option) => {
        const primary = option[props.displayKey]?.toLowerCase() || "";
        const secondary = props.secondaryKey
            ? option[props.secondaryKey]?.toLowerCase() || ""
            : "";
        return primary.includes(query) || secondary.includes(query);
    });
});

// Handle input changes
const onInput = (event) => {
    searchQuery.value = event.target.value;
    if (!isOpen.value) {
        openDropdown();
    }
    highlightedIndex.value = -1;
};

// Handle option selection
const selectOption = (option) => {
    emit("update:modelValue", option[props.valueKey]);
    searchQuery.value = ""; // Clear search after selection
    closeDropdown();
};

// Clear selection
const clearSelection = () => {
    emit("update:modelValue", "");
    searchQuery.value = "";
    closeDropdown();
};

// Open dropdown
const openDropdown = () => {
    if (props.disabled) return;
    isOpen.value = true;
    highlightedIndex.value = -1;
};

// Close dropdown
const closeDropdown = () => {
    isOpen.value = false;
    highlightedIndex.value = -1;
    // If user closed without selecting and there was a previous selection, clear search
    if (selectedOption.value && searchQuery.value !== "") {
        searchQuery.value = "";
    }
};

// Toggle dropdown
const toggleDropdown = () => {
    if (isOpen.value) {
        closeDropdown();
    } else {
        openDropdown();
    }
};

// Handle keyboard navigation
const handleKeyDown = (event) => {
    if (
        !isOpen.value &&
        (event.key === "ArrowDown" || event.key === "ArrowUp")
    ) {
        event.preventDefault();
        openDropdown();
        return;
    }

    if (!isOpen.value) return;

    switch (event.key) {
        case "ArrowDown":
            event.preventDefault();
            highlightedIndex.value = Math.min(
                highlightedIndex.value + 1,
                filteredOptions.value.length - 1
            );
            break;
        case "ArrowUp":
            event.preventDefault();
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1);
            break;
        case "Enter":
            event.preventDefault();
            if (
                highlightedIndex.value >= 0 &&
                filteredOptions.value[highlightedIndex.value]
            ) {
                selectOption(filteredOptions.value[highlightedIndex.value]);
            }
            break;
        case "Escape":
            event.preventDefault();
            closeDropdown();
            break;
        case "Tab":
            closeDropdown();
            break;
    }
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (dropdown.value && !dropdown.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});

// Watch for model value changes to update display
watch(
    () => props.modelValue,
    () => {
        // Clear search when value changes externally
        if (!isOpen.value) {
            searchQuery.value = "";
        }
    }
);

// Reset search when options change
watch(
    () => props.options,
    () => {
        highlightedIndex.value = -1;
    }
);
</script>

<template>
    <div>
        <InputLabel :value="label" />
        <div ref="dropdown" class="relative">
            <!-- Search input that acts as the main field -->
            <div class="relative">
                <input
                    ref="searchInput"
                    :value="displayValue"
                    @input="onInput"
                    @focus="openDropdown"
                    @keydown="handleKeyDown"
                    type="text"
                    :placeholder="
                        selectedOption
                            ? selectedOption[displayKey]
                            : placeholder
                    "
                    :disabled="disabled"
                    class="w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 shadow-sm transition-colors focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:cursor-not-allowed"
                    :class="{
                        'border-red-500': error,
                        'hover:border-gray-400': !disabled && !error,
                    }"
                />

                <!-- Clear button (when there's a selection and not required) -->
                <button
                    v-if="selectedOption && !required && !disabled"
                    type="button"
                    @click="clearSelection"
                    class="absolute inset-y-0 right-8 flex items-center pr-1 hover:bg-gray-50 rounded"
                    title="Clear selection"
                >
                    <svg
                        class="h-4 w-4 text-gray-400 hover:text-gray-600"
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

                <!-- Dropdown arrow -->
                <button
                    type="button"
                    @click="toggleDropdown"
                    :disabled="disabled"
                    class="absolute inset-y-0 right-0 flex items-center pr-2 disabled:cursor-not-allowed hover:bg-gray-50 rounded-r-md"
                    title="Open dropdown"
                >
                    <svg
                        class="h-5 w-5 text-gray-400 transition-transform"
                        :class="{ 'rotate-180': isOpen }"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </div>

            <!-- Dropdown panel -->
            <Transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <div
                    v-show="isOpen"
                    class="absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                >
                    <!-- Options list -->
                    <div class="max-h-60 overflow-auto">
                        <!-- Clear option (if value is selected and not required) -->
                        <div
                            v-if="selectedOption && !required"
                            @click="clearSelection"
                            class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-500 hover:bg-gray-50 border-b border-gray-100"
                        >
                            <span class="block text-sm italic"
                                >Clear selection</span
                            >
                        </div>

                        <!-- Options -->
                        <div
                            v-for="(option, index) in filteredOptions"
                            :key="option[valueKey]"
                            @click="selectOption(option)"
                            @mouseenter="highlightedIndex = index"
                            class="cursor-pointer select-none relative py-2 pl-3 pr-9"
                            :class="{
                                'bg-indigo-100 text-indigo-900':
                                    option[valueKey] == modelValue,
                                'bg-gray-100':
                                    highlightedIndex === index &&
                                    option[valueKey] != modelValue,
                                'hover:bg-indigo-50 hover:text-indigo-900':
                                    option[valueKey] != modelValue &&
                                    highlightedIndex !== index,
                                'text-gray-900': option[valueKey] != modelValue,
                            }"
                        >
                            <div class="flex items-center">
                                <span
                                    class="block font-medium truncate"
                                    :class="{
                                        'font-semibold':
                                            option[valueKey] == modelValue,
                                    }"
                                >
                                    {{ option[displayKey] }}
                                </span>
                                <span
                                    v-if="secondaryKey && option[secondaryKey]"
                                    class="ml-2 text-sm text-gray-500 truncate"
                                >
                                    ({{ option[secondaryKey] }})
                                </span>
                            </div>

                            <!-- Check icon for selected option -->
                            <span
                                v-if="option[valueKey] == modelValue"
                                class="absolute inset-y-0 right-0 flex items-center pr-4"
                            >
                                <svg
                                    class="h-5 w-5 text-indigo-600"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </span>
                        </div>

                        <!-- No options message -->
                        <div
                            v-if="filteredOptions.length === 0"
                            class="relative cursor-default select-none py-4 text-center text-gray-500"
                        >
                            <span class="text-sm">
                                {{
                                    searchQuery
                                        ? "No results found"
                                        : emptyMessage
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Error message -->
        <InputError class="mt-2" :message="error" />
    </div>
</template>
