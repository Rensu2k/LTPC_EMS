<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    users: Array,
    flash: Object,
});

// Handle flash messages
import { usePage } from "@inertiajs/vue3";
const page = usePage();

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingUser = ref(null);
const deletingUser = ref(null);
const searchQuery = ref("");
const selectedRole = ref("");

const form = useForm({
    username: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "cashier",
});

const filteredUsers = computed(() => {
    let filtered = props.users || [];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (user) =>
                user.username
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                user.email
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                user.role
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    if (selectedRole.value) {
        filtered = filtered.filter((user) => user.role === selectedRole.value);
    }

    return filtered;
});

const openCreateModal = () => {
    form.reset();
    editingUser.value = null;
    showModal.value = true;
};

const openEditModal = (user) => {
    editingUser.value = user;
    form.username = user.username;
    form.email = user.email;
    form.role = user.role;
    form.password = "";
    form.password_confirmation = "";
    showModal.value = true;
};

const openDeleteModal = (user) => {
    deletingUser.value = user;
    showDeleteModal.value = true;
};

const submitForm = () => {
    if (editingUser.value) {
        form.put(`/admin/users/${editingUser.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
            onError: () => {
                // Form errors will be displayed automatically
            },
        });
    } else {
        form.post("/admin/users", {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
            onError: () => {
                // Form errors will be displayed automatically
            },
        });
    }
};

const deleteUser = () => {
    router.delete(`/admin/users/${deletingUser.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingUser.value = null;
        },
    });
};

const getRoleColor = (role) => {
    const colors = {
        admin: "bg-purple-100 text-purple-800",
        officer: "bg-blue-100 text-blue-800",
        cashier: "bg-green-100 text-green-800",
    };
    return colors[role] || "bg-gray-100 text-gray-800";
};
</script>

<template>
    <Head title="User Management" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <!-- Flash Messages -->
            <div
                v-if="page.props.flash && page.props.flash.success"
                class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
            >
                <span class="block sm:inline">{{
                    page.props.flash.success
                }}</span>
            </div>
            <div
                v-if="page.props.flash && page.props.flash.error"
                class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
            >
                <span class="block sm:inline">{{
                    page.props.flash.error
                }}</span>
            </div>

            <div
                class="bg-white rounded-xl shadow-sm overflow-hidden animate-fade-in border border-gray-100"
            >
                <!-- Header Section -->
                <div
                    class="p-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50 relative"
                >
                    <div
                        class="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-green-500 to-emerald-500"
                    ></div>
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <h3
                                class="text-lg font-semibold text-green-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-16 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                System Users
                            </h3>
                            <!-- <p class="text-sm text-gray-500">Manage user accounts roles</p> -->
                        </div>
                        <button
                            class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium px-6 py-2 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-md hover:shadow-lg"
                            @click="openCreateModal"
                        >
                            Add New User
                        </button>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="search" value="Search Users" />
                            <TextInput
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by name, email, or role..."
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="role-filter"
                                value="Filter by Role"
                            />
                            <select
                                id="role-filter"
                                v-model="selectedRole"
                                class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                            >
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="officer">Officer</option>
                                <option value="cashier">Cashier</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div
                    class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100"
                >
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                        <div
                            class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm"
                        >
                            <div class="text-sm font-medium text-green-600">
                                Total Users
                            </div>
                            <div class="text-2xl font-bold text-green-600">
                                {{ users?.length || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    User Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Role
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="user in filteredUsers"
                                :key="user.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-semibold"
                                            >
                                                <span
                                                    class="text-sm font-medium"
                                                >
                                                    {{
                                                        user.username
                                                            ?.charAt(0)
                                                            ?.toUpperCase()
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ user.username }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ user.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-red-100 text-red-800':
                                                user.role === 'admin',
                                            'bg-blue-100 text-blue-800':
                                                user.role === 'officer',
                                            'bg-green-100 text-green-800':
                                                user.role === 'cashier',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="openEditModal(user)"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            v-if="
                                                user.id !==
                                                $page.props.auth.user.id
                                            "
                                            @click="openDeleteModal(user)"
                                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div
                        v-if="filteredUsers.length === 0"
                        class="text-center py-12"
                    >
                        <div class="text-gray-400 mb-4">
                            <svg
                                class="mx-auto h-12 w-12"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            No users found
                        </h3>
                        <p class="text-gray-500">
                            {{
                                searchQuery || selectedRole
                                    ? "Try adjusting your filters."
                                    : "Get started by adding a new user."
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal
            :show="showModal"
            @close="showModal = false"
            max-width="2xl"
            :close-on-click-outside="false"
        >
            <div class="p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ editingUser ? "Edit User" : "Add New User" }}
                    </h3>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="username" value="User Name *" />
                            <TextInput
                                id="username"
                                v-model="form.username"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError
                                :message="form.errors.username"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel for="email" value="Email Address *" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError
                                :message="form.errors.email"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                for="password"
                                :value="
                                    editingUser
                                        ? 'New Password (leave blank to keep current)'
                                        : 'Password *'
                                "
                            />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full"
                                :required="!editingUser"
                            />
                            <InputError
                                :message="form.errors.password"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="password_confirmation"
                                value="Confirm Password"
                            />
                            <TextInput
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                :message="form.errors.password_confirmation"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="role" value="Role *" />
                        <select
                            id="role"
                            v-model="form.role"
                            class="mt-1 block w-full"
                            required
                        >
                            <option value="cashier">Cashier</option>
                            <option value="officer">Officer</option>
                            <option value="admin">Admin</option>
                        </select>
                        <InputError :message="form.errors.role" class="mt-2" />
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-4 py-2 rounded-lg transition-colors"
                            @click="showModal = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium px-4 py-2 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-md hover:shadow-lg"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? "Saving..."
                                    : editingUser
                                    ? "Update"
                                    : "Create"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900">
                        Delete User
                    </h3>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to delete
                    {{ deletingUser?.username }}? This action cannot be undone
                    and will remove all associated data.
                </p>
                <div class="flex justify-end space-x-3">
                    <button
                        type="button"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-4 py-2 rounded-lg transition-colors"
                        @click="showDeleteModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg transition-colors"
                        @click="deleteUser"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
