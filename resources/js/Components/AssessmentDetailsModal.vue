<script setup>
import { computed } from "vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import jsPDF from "jspdf";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    assessment: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close", "edit", "reassessment"]);

const formattedDate = computed(() => {
    if (!props.assessment?.assessment_date) return "N/A";
    return new Date(props.assessment.assessment_date).toLocaleDateString(
        "en-US",
        {
            year: "numeric",
            month: "long",
            day: "numeric",
        }
    );
});

const statusColor = computed(() => {
    switch (props.assessment?.status) {
        case "pending":
            return "bg-yellow-50 text-yellow-700 border border-yellow-200";
        case "completed":
            return "bg-blue-50 text-blue-700 border border-blue-200";
        default:
            return "bg-gray-100 text-gray-700";
    }
});

const typeColor = computed(() => {
    switch (props.assessment?.type) {
        case "theoretical":
            return "bg-blue-50 text-blue-700 border border-blue-200";
        case "practical":
            return "bg-green-50 text-green-700 border border-green-200";
        case "both":
            return "bg-purple-50 text-purple-700 border border-purple-200";
        default:
            return "bg-gray-100 text-gray-700";
    }
});

const close = () => {
    emit("close");
};

const editAssessment = () => {
    emit("edit", props.assessment);
};

const reassessment = () => {
    emit("reassessment", props.assessment);
};

// Helper function to load image as base64
const loadImageAsBase64 = (url) => {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.onload = () => {
            const canvas = document.createElement("canvas");
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0);
            try {
                const base64 = canvas.toDataURL("image/png");
                resolve(base64);
            } catch (e) {
                reject(e);
            }
        };
        img.onerror = reject;
        img.src = url;
    });
};

// Print certificate for competent assessments
const printCertificate = async () => {
    const assessment = props.assessment;
    if (!assessment || assessment.result !== "competent") {
        return;
    }

    const doc = new jsPDF("landscape", "mm", "a4");
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();

    // Blue color for borders and title (dark blue: RGB 0, 51, 102)
    const blueColor = [0, 51, 102];
    const borderMargin = 15;

    // Draw outer blue border (thicker)
    doc.setLineWidth(2);
    doc.setDrawColor(...blueColor);
    doc.rect(
        borderMargin,
        borderMargin,
        pageWidth - 2 * borderMargin,
        pageHeight - 2 * borderMargin
    );

    // Draw inner blue border (thinner)
    doc.setLineWidth(1);
    doc.setDrawColor(...blueColor);
    doc.rect(
        borderMargin + 3,
        borderMargin + 3,
        pageWidth - 2 * borderMargin - 6,
        pageHeight - 2 * borderMargin - 6
    );

    // Load logos
    try {
        const ltpcLogoUrl = "/images/ltpc-logo.png";
        const pesdoLogoUrl = "/images/pesdo-logo.png";

        const ltpcLogo = await loadImageAsBase64(ltpcLogoUrl);
        const pesdoLogo = await loadImageAsBase64(pesdoLogoUrl);

        // Add logos at the top
        const logoHeight = 18; // Height in mm
        const logoWidth = 22; // Width in mm
        const logoTopY = borderMargin + 8;

        // LTPC logo at top left
        doc.addImage(
            ltpcLogo,
            "PNG",
            borderMargin + 8,
            logoTopY,
            logoWidth,
            logoHeight
        );

        // PESDO logo at top right
        doc.addImage(
            pesdoLogo,
            "PNG",
            pageWidth - borderMargin - 8 - logoWidth,
            logoTopY,
            logoWidth,
            logoHeight
        );
    } catch (error) {
        console.warn("Could not load logos:", error);
        // Continue without logos if they fail to load
    }

    // Title - "CERTIFICATE OF COMPETENCY" (styled like TESDA)
    doc.setFontSize(32);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(...blueColor);
    const titlePart1 = "CERTIFICATE";
    const titlePart1Width = doc.getTextWidth(titlePart1);
    doc.text(
        titlePart1,
        (pageWidth - titlePart1Width) / 2,
        borderMargin + 45
    );

    doc.setFontSize(24);
    const titlePart2 = "OF COMPETENCY";
    const titlePart2Width = doc.getTextWidth(titlePart2);
    doc.text(
        titlePart2,
        (pageWidth - titlePart2Width) / 2,
        borderMargin + 55
    );

    // Main body text - "This is to certify that"
    doc.setFontSize(12);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(0, 0, 0);
    const certifyText = "This is to certify that";
    const certifyTextWidth = doc.getTextWidth(certifyText);
    doc.text(
        certifyText,
        (pageWidth - certifyTextWidth) / 2,
        borderMargin + 75
    );

    // Applicant Name (prominent, bold)
    doc.setFontSize(20);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(0, 0, 0);
    const applicantName = assessment.applicant_name || "N/A";
    const nameWidth = doc.getTextWidth(applicantName);
    doc.text(
        applicantName,
        (pageWidth - nameWidth) / 2,
        borderMargin + 90
    );

    // Program information - "has completed the assessment for"
    doc.setFontSize(12);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(0, 0, 0);
    const programText = "has completed the assessment for";
    const programTextWidth = doc.getTextWidth(programText);
    doc.text(
        programText,
        (pageWidth - programTextWidth) / 2,
        borderMargin + 105
    );

    // Program name (bold, larger)
    doc.setFontSize(14);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(0, 0, 0);
    const programName = assessment.program_name || "N/A";
    const programNameWidth = doc.getTextWidth(programName);
    doc.text(
        programName,
        (pageWidth - programNameWidth) / 2,
        borderMargin + 120
    );

    // "on" text
    doc.setFontSize(12);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(0, 0, 0);
    const onText = "on";
    const onTextWidth = doc.getTextWidth(onText);
    doc.text(onText, (pageWidth - onTextWidth) / 2, borderMargin + 135);

    // Assessment date (bold, larger)
    const assessmentDate = assessment.assessment_date
        ? new Date(assessment.assessment_date).toLocaleDateString("en-US", {
              year: "numeric",
              month: "long",
              day: "numeric",
          })
        : "N/A";
    doc.setFontSize(14);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(0, 0, 0);
    const dateWidth = doc.getTextWidth(assessmentDate);
    doc.text(
        assessmentDate,
        (pageWidth - dateWidth) / 2,
        borderMargin + 150
    );

    // Result - "RESULT: COMPETENT" (green, bold)
    doc.setFontSize(13);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(34, 139, 34); // Green color
    const resultText = "RESULT: COMPETENT";
    const resultTextWidth = doc.getTextWidth(resultText);
    doc.text(
        resultText,
        (pageWidth - resultTextWidth) / 2,
        borderMargin + 170
    );

    // Disclaimer text (centered, smaller, gray)
    doc.setFontSize(9);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(120, 120, 120);
    const disclaimerText =
        "This is a computer generated certificate, it is valid even without signature.";
    const disclaimerWidth = doc.getTextWidth(disclaimerText);
    doc.text(
        disclaimerText,
        (pageWidth - disclaimerWidth) / 2,
        borderMargin + 185
    );

    // Footer section
    const footerY = pageHeight - borderMargin - 25;

    // Left side - Verification details
    doc.setFontSize(8);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(0, 0, 0);
    doc.text("For verification purposes, contact:", borderMargin + 8, footerY);
    doc.text("LTPC Assessment Office", borderMargin + 8, footerY + 5);
    doc.text("ltpc@example.com", borderMargin + 8, footerY + 10);
    doc.text("(02) 123 - 4567", borderMargin + 8, footerY + 15);

    // Right side - Certificate number
    doc.setFontSize(8);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(150, 150, 150);
    const certNumber = `CERT-${String(assessment.id).padStart(6, "0")}`;
    const certNumberWidth = doc.getTextWidth(certNumber);
    doc.text(
        certNumber,
        pageWidth - borderMargin - 8 - certNumberWidth,
        footerY + 10
    );

    // Generate filename
    const sanitizedName = applicantName.replace(/[^a-z0-9]/gi, "_").toLowerCase();
    const filename = `certificate_${sanitizedName}_${assessment.id}.pdf`;

    // Save the PDF
    doc.save(filename);
};
</script>

<template>
    <Modal :show="show" @close="close" custom-width="80vw">
        <div class="p-6" v-if="assessment">
            <!-- Header -->
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg
                            class="h-8 w-8 text-blue-600"
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
                        <h2 class="text-2xl font-bold text-gray-900">
                            {{ assessment.title }}
                        </h2>
                        <p
                            class="text-gray-600 mt-1"
                            v-if="assessment.description"
                        >
                            {{ assessment.description }}
                        </p>
                        <div class="flex items-center gap-3 mt-3">
                            <span
                                :class="typeColor"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                                {{
                                    assessment.type?.charAt(0).toUpperCase() +
                                    assessment.type?.slice(1)
                                }}
                            </span>
                            <span
                                :class="statusColor"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                                {{
                                    assessment.status?.charAt(0).toUpperCase() +
                                    assessment.status?.slice(1)
                                }}
                            </span>
                        </div>
                    </div>
                </div>
                <button
                    @click="close"
                    class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100"
                >
                    <svg
                        class="h-7 w-7"
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

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Assessment Information -->
                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
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
                        Assessment Details
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Program:</span
                            >
                            <span class="text-sm text-gray-900 font-semibold">
                                {{ assessment.program_name || "N/A" }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Assessment Date:</span
                            >
                            <span class="text-sm text-gray-900">{{
                                formattedDate
                            }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Type:</span
                            >
                            <span class="text-sm text-gray-900">
                                {{
                                    assessment.type?.charAt(0).toUpperCase() +
                                    assessment.type?.slice(1)
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Status:</span
                            >
                            <span
                                :class="statusColor"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                                {{
                                    assessment.status?.charAt(0).toUpperCase() +
                                    assessment.status?.slice(1)
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Participants Information -->
                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
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
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Applicant:</span
                            >
                            <span class="text-sm text-gray-900 font-semibold">
                                {{ assessment.applicant_name || "N/A" }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Assessor:</span
                            >
                            <span class="text-sm text-gray-900">
                                {{ assessment.trainer_name || "N/A" }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Assessment Status Information -->
                <div class="lg:col-span-2">
                    <div
                        class="bg-gray-50 rounded-lg p-6 border border-gray-200"
                    >
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
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Assessment Status
                        </h3>

                        <div class="flex justify-center">
                            <div
                                class="text-center bg-white rounded-lg p-6 border-2"
                            >
                                <div
                                    class="flex items-center justify-center gap-3 mb-2"
                                >
                                    <span
                                        :class="
                                            assessment.result_color ||
                                            statusColor
                                        "
                                        class="inline-flex px-3 py-1 text-lg font-semibold rounded-full"
                                    >
                                        {{
                                            assessment.result_status ||
                                            assessment.status
                                                ?.charAt(0)
                                                .toUpperCase() +
                                                assessment.status?.slice(1)
                                        }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    Assessment status based on current progress
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4 pt-6 border-t mt-6">
                <SecondaryButton @click="close"> Close </SecondaryButton>
                <PrimaryButton
                    v-if="assessment.can_be_reassessed"
                    @click="reassessment"
                    class="bg-orange-600 hover:bg-orange-700"
                >
                    <svg
                        class="-ml-1 mr-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    Schedule Re-assessment
                </PrimaryButton>
                <PrimaryButton
                    v-if="assessment.result === 'competent'"
                    @click="printCertificate"
                    class="bg-purple-600 hover:bg-purple-700"
                >
                    <svg
                        class="-ml-1 mr-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                        />
                    </svg>
                    Print Certificate
                </PrimaryButton>
                <PrimaryButton
                    @click="editAssessment"
                    class="bg-blue-600 hover:bg-blue-700"
                >
                    <svg
                        class="-ml-1 mr-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>
                    Edit Assessment
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
