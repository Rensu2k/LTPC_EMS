<template>
    <div
        v-if="meta.total > 0"
        class="flex items-center justify-between px-6 py-4 bg-white border-t border-gray-200"
    >
        <!-- Mobile pagination -->
        <div
            v-if="meta.last_page > 1"
            class="flex justify-between flex-1 sm:hidden"
        >
            <button
                @click="links.prev ? changePage(links.prev) : null"
                :disabled="!links.prev"
                :class="{
                    'relative inline-flex items-center px-4 py-2 text-sm font-medium border rounded-md': true,
                    'text-gray-700 bg-white border-gray-300 hover:bg-gray-50':
                        links.prev,
                    'text-gray-400 bg-gray-100 border-gray-200 cursor-not-allowed':
                        !links.prev,
                }"
            >
                Previous
            </button>
            <button
                @click="links.next ? changePage(links.next) : null"
                :disabled="!links.next"
                :class="{
                    'relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium border rounded-md': true,
                    'text-gray-700 bg-white border-gray-300 hover:bg-gray-50':
                        links.next,
                    'text-gray-400 bg-gray-100 border-gray-200 cursor-not-allowed':
                        !links.next,
                }"
            >
                Next
            </button>
        </div>

        <!-- Desktop pagination -->
        <div
            class="hidden sm:flex sm:items-center sm:justify-between sm:flex-1"
        >
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium">{{ meta.from || 0 }}</span>
                    to
                    <span class="font-medium">{{ meta.to || 0 }}</span>
                    of
                    <span class="font-medium">{{ meta.total || 0 }}</span>
                    {{ meta.total === 1 ? "result" : "results" }}
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <!-- Per page selector -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-700">Show:</label>
                    <select
                        :value="meta.per_page"
                        @change="changePerPage($event.target.value)"
                        class="border border-gray-300 rounded-md text-sm"
                    >
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <!-- Page navigation -->
                <nav
                    v-if="meta.last_page > 1"
                    class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                    aria-label="Pagination"
                >
                    <!-- Previous page button -->
                    <button
                        v-if="links.prev"
                        @click="changePage(links.prev)"
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                    >
                        <span class="sr-only">Previous</span>
                        <svg
                            class="h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                    <span
                        v-else
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-300"
                    >
                        <span class="sr-only">Previous</span>
                        <svg
                            class="h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>

                    <!-- Page numbers -->
                    <template v-for="(link, index) in pageLinks" :key="index">
                        <button
                            v-if="link.url && !link.active"
                            @click="changePage(link.url)"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                            v-html="link.label"
                        ></button>
                        <span
                            v-else-if="link.active"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600"
                            v-html="link.label"
                        ></span>
                        <span
                            v-else
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                            v-html="link.label"
                        ></span>
                    </template>

                    <!-- Next page button -->
                    <button
                        v-if="links.next"
                        @click="changePage(links.next)"
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                    >
                        <span class="sr-only">Next</span>
                        <svg
                            class="h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                    <span
                        v-else
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-300"
                    >
                        <span class="sr-only">Next</span>
                        <svg
                            class="h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    data: Object,
    preserveState: {
        type: Boolean,
        default: true,
    },
});

const meta = computed(() => {
    // Handle Laravel pagination structure directly
    return {
        total: props.data?.total || 0,
        per_page: props.data?.per_page || 20,
        current_page: props.data?.current_page || 1,
        from: props.data?.from || 0,
        to: props.data?.to || 0,
        last_page: props.data?.last_page || 1,
    };
});

const links = computed(() => {
    // Force HTTPS for pagination URLs
    const prevUrl = props.data?.prev_page_url;
    const nextUrl = props.data?.next_page_url;

    let prev = prevUrl;
    let next = nextUrl;

    // Force HTTPS if we're on HTTPS
    if (window.location.protocol === "https:") {
        if (prevUrl && prevUrl.startsWith("http://")) {
            prev = prevUrl.replace("http://", "https://");
        }
        if (nextUrl && nextUrl.startsWith("http://")) {
            next = nextUrl.replace("http://", "https://");
        }
    }

    return { prev, next };
});

const pageLinks = computed(() => {
    if (!props.data?.links) return [];

    // Force HTTPS for all pagination links
    return props.data.links
        .filter(
            (link) =>
                link.label !== "&laquo; Previous" &&
                link.label !== "Next &raquo;"
        )
        .map((link) => {
            // Force HTTPS if we're on HTTPS
            if (
                window.location.protocol === "https:" &&
                link.url &&
                link.url.startsWith("http://")
            ) {
                link.url = link.url.replace("http://", "https://");
            }
            return link;
        });
});

const changePage = (url) => {
    if (!url) return;

    // Force HTTPS for the URL if we're on HTTPS
    let targetUrl = url;
    if (window.location.protocol === "https:" && url.startsWith("http://")) {
        targetUrl = url.replace("http://", "https://");
    }

    // Create a new URL object to preserve per_page parameter
    const currentUrl = new URL(window.location.href);
    const targetUrlObj = new URL(targetUrl);

    // Preserve the current per_page value
    const currentPerPage = currentUrl.searchParams.get("per_page");
    if (currentPerPage) {
        targetUrlObj.searchParams.set("per_page", currentPerPage);
    }

    router.visit(targetUrlObj.toString(), {
        preserveState: props.preserveState,
        preserveScroll: true,
        replace: true,
    });
};

const changePerPage = (perPage) => {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set("per_page", perPage);
    currentUrl.searchParams.delete("page"); // Reset to first page when changing per_page

    router.visit(currentUrl.toString(), {
        preserveState: props.preserveState,
        preserveScroll: false,
        replace: true,
    });
};
</script>
