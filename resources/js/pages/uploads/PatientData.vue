<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card } from '@/components/ui/card';



const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Data Upload', href: '/upload' },
];

// Files state
const patientsFile = ref<File | null>(null);
const visitsFile = ref<File | null>(null);
const isDragging = ref({ patients: false, visits: false });

// Upload state
const uploading = ref({ patients: false, visits: false });
const uploadProgress = ref({ patients: 0, visits: 0 });

// Validation state
const props = defineProps<{ validationResult?: any }>();

const validationResults = ref(props.validationResult || {});

const validating = ref(false);
const validationProgress = ref(0);

// Computed properties
const patientFileName = computed(() => patientsFile.value?.name || 'No file selected');
const visitsFileName = computed(() => visitsFile.value?.name || 'No file selected');

const patientFileSize = computed(() => {
  if (!patientsFile.value) return '';
  return formatFileSize(patientsFile.value.size);
});

const visitsFileSize = computed(() => {
  if (!visitsFile.value) return '';
  return formatFileSize(visitsFile.value.size);
});

// File handling functions
function onFileChange(event: Event, type: 'patients' | 'visits') {
  const file = (event.target as HTMLInputElement).files?.[0] || null;
  setFile(file, type);
}

function setFile(file: File | null, type: 'patients' | 'visits') {
  if (type === 'patients') patientsFile.value = file;
  if (type === 'visits') visitsFile.value = file;

  // Reset validation when files change
  // validationResults.value = { patients: null, visits: null, crossValidation: null };
}

// Drag and drop functions
function onDragOver(event: DragEvent, type: 'patients' | 'visits') {
  event.preventDefault();
  isDragging.value[type] = true;
}

function onDragLeave(type: 'patients' | 'visits') {
  isDragging.value[type] = false;
}

function onDrop(event: DragEvent, type: 'patients' | 'visits') {
  event.preventDefault();
  isDragging.value[type] = false;

  const files = event.dataTransfer?.files;
  if (files && files.length > 0) {
    setFile(files[0], type);
  }
}

// Upload functions
async function uploadFile(type: 'patients' | 'visits') {
  const file = type === 'patients' ? patientsFile.value : visitsFile.value;
  if (!file) return;

  uploading.value[type] = true;
  uploadProgress.value[type] = 0;
  validationResults.value[type] = null;
  // Reset cross-validation
  validationResults.value.crossValidation = null;

  const formData = new FormData();
  // Use the correct field name for each type
  formData.append(type === 'patients' ? 'patients_file' : 'visits_file', file);

  router.post(
    `/upload/${type}`,
    formData,
    {
      forceFormData: true,
      onProgress: (progress) => {
        if (progress && progress.total) {
          uploadProgress.value[type] = Math.round(
            (progress.loaded * 100) / progress.total
          );
        }
      },
      onSuccess: (page) => {
        validationResults.value[type] = page.props.validationResult || {};
      },
      onError: (errors) => {
        validationResults.value[type] = { error: errors.message || 'Upload failed' };
      },
      onFinish: () => {
        uploading.value[type] = false;
      }
    }
  );
}

async function validateBothFiles() {
  if (!patientsFile.value || !visitsFile.value) return;

  validating.value = true;
  validationProgress.value = 0;
  validationResults.value.crossValidation = null;

  const formData = new FormData();

  // Append each patient number individually
  validationResults.value.patients.patient_numbers.forEach((number: string | Blob) => {
    formData.append('patients_data[]', number);
  });

  // Append each visit number individually
  validationResults.value.visits.patient_numbers.forEach((number: string | Blob) => {
    formData.append('visits_data[]', number);
  });

  router.post(
    '/upload/validate',
    formData,
    {
      forceFormData: true,
      onProgress: (progress) => {
        if (progress && progress.total) {
          validationProgress.value = Math.round(
            (progress.loaded * 100) / progress.total
          );
        }
      },
      onSuccess: (page) => {
        validationResults.value.crossValidation = page.props.validationResult || {};
      },
      onError: (errors) => {
        validationResults.value.crossValidation = { error: errors.message || 'Validation failed' };
      },
      onFinish: () => {
        validating.value = false;
        validationProgress.value = 100;
      }
    }
  );
}

// Helper functions

// Add this helper function
function formatSampleValue(value: any): string {
  if (value === null || value === undefined) return '';
  if (typeof value === 'object' && value.date) {
    return new Date(value.date).toLocaleDateString();
  }
  if (typeof value === 'boolean') return value ? 'Yes' : 'No';
  return String(value);
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// function getValidationStatus(result: any) {
//   if (!result) return 'pending';
//   if (result.error) return 'error';
//   if (result.issues && result.issues.length > 0) return 'warning';
//   return 'success';
// }

const patientValidationStatus = computed(() => {
  const res = validationResults.value.patients;
  if (!res) return 'pending';
  if (res.status === 'error') return 'error';
  if (res.issues && res.issues.length > 0) return 'warning';
  return 'success';
});

const visitsValidationStatus = computed(() => {
  const res = validationResults.value.visits;
  if (!res) return 'pending';
  if (res.status === 'error') return 'error';
  if (res.issues && res.issues.length > 0) return 'warning';
  return 'success';
});

const crossValidationStatus = computed(() => {
  const res = validationResults.value.crossValidation;
  if (!res) return 'pending';
  if (res.status === 'error') return 'error';
  if (res.issues && res.issues.length > 0) return 'warning';
  return 'success';
});

// Helper to format issue messages
// function formatIssueMessage(issue: any): string {
//   if (issue.type === 'header_mismatch') {
//     return `Header mismatch: ${issue.message}`;
//   }
//   if (issue.type === 'blank_patient_number') {
//     return `Blank PatientNumber found in ${issue.count} rows`;
//   }
//   if (issue.type === 'invalid_date_format') {
//     return `Invalid date format in ${issue.field}: ${issue.count} issues`;
//   }
//   if (issue.type === 'non_numeric_value') {
//     return `Non-numeric values in ${issue.field}: ${issue.count} issues`;
//   }
//   return issue.message || 'Data quality issue';
// }
</script>

<template>

  <Head title="Data Upload" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 overflow-x-auto">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-2">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Medical Data Validation</h1>
          <p class="text-gray-600 mt-1">Upload Excel files for Patients and Visits data. Validate before importing into
            the database.</p>
        </div>
        <div class="flex items-center gap-3 bg-white p-3 rounded-lg shadow-sm">
          <div class="bg-blue-100 w-10 h-10 rounded-full flex items-center justify-center">
            <i class="fas fa-user-md text-blue-600"></i>
          </div>
          <div>
            <p class="text-sm text-gray-600">Validation Status</p>
            <p class="font-medium" v-if="validating">In Progress...</p>
            <p class="font-medium" v-else>Ready</p>
          </div>
        </div>
      </div>
      <!-- <pre>
  {{validationResults.visits  }}
</pre> -->
      <!-- File Upload Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Patients File Card -->
        <Card class="p-6 border border-blue-100 bg-gradient-to-br from-blue-50 to-white">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="fas fa-user-injured text-2xl text-blue-600"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">Patients File</h2>
              <a href="/patients_template.xlsx" download
                class="text-blue-600 hover:underline text-sm flex items-center gap-1">
                <i class="fas fa-download text-xs"></i> Download Template
              </a>
            </div>
          </div>

          <div @dragover.prevent="onDragOver($event, 'patients')" @dragleave="onDragLeave('patients')"
            @drop.prevent="onDrop($event, 'patients')" :class="[
              'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all mb-4',
              isDragging.patients
                ? 'border-blue-400 bg-blue-50'
                : 'border-blue-200 hover:border-blue-300'
            ]">
            <input type="file" accept=".xlsx,.xls" @change="(e: any) => onFileChange(e, 'patients')" class="hidden"
              id="patients-file-input" />
            <div class="flex flex-col items-center justify-center gap-3">
              <i class="fas fa-cloud-upload-alt text-3xl text-blue-500"></i>
              <p class="text-gray-600">Drag & drop your file here</p>
              <p class="text-sm text-gray-500 mb-3">or</p>
              <label for="patients-file-input"
                class="bg-blue-600 text-white px-4 py-2 rounded-md cursor-pointer hover:bg-blue-700 transition">
                Browse Files
              </label>
            </div>
          </div>

          <!-- File Info -->
          <div v-if="patientsFile" class="flex items-center justify-between bg-blue-50 p-3 rounded-lg mb-4">
            <div class="flex items-center gap-3">
              <i class="fas fa-file-excel text-blue-600"></i>
              <div>
                <p class="font-medium text-gray-800">{{ patientFileName }}</p>
                <p class="text-sm text-gray-600">{{ patientFileSize }}</p>
              </div>
            </div>
            <button @click="patientsFile = null" class="text-gray-500 hover:text-red-500 transition">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Upload Button -->
          <button
            class="w-full bg-blue-600 text-white px-4 py-3 rounded-md shadow hover:bg-blue-700 transition flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!patientsFile || uploading.patients" @click="uploadFile('patients')">
            <template v-if="uploading.patients">
              <i class="fas fa-spinner fa-spin mr-2"></i>
              Uploading... ({{ uploadProgress.patients }}%)
            </template>
            <template v-else>
              <i class="fas fa-upload mr-2"></i>
              Upload & Validate Patients Data
            </template>
          </button>
        </Card>

        <!-- Visits File Card -->
        <Card class="p-6 border border-green-100 bg-gradient-to-br from-green-50 to-white">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
              <i class="fas fa-notes-medical text-2xl text-green-600"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">Visits File</h2>
              <a href="/visits_template.xlsx" download
                class="text-green-600 hover:underline text-sm flex items-center gap-1">
                <i class="fas fa-download text-xs"></i> Download Template
              </a>
            </div>
          </div>

          <div @dragover.prevent="onDragOver($event, 'visits')" @dragleave="onDragLeave('visits')"
            @drop.prevent="onDrop($event, 'visits')" :class="[
              'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all mb-4',
              isDragging.visits
                ? 'border-green-400 bg-green-50'
                : 'border-green-200 hover:border-green-300'
            ]">
            <input type="file" accept=".xlsx,.xls" @change="(e: any) => onFileChange(e, 'visits')" class="hidden"
              id="visits-file-input" />
            <div class="flex flex-col items-center justify-center gap-3">
              <i class="fas fa-cloud-upload-alt text-3xl text-green-500"></i>
              <p class="text-gray-600">Drag & drop your file here</p>
              <p class="text-sm text-gray-500 mb-3">or</p>
              <label for="visits-file-input"
                class="bg-green-600 text-white px-4 py-2 rounded-md cursor-pointer hover:bg-green-700 transition">
                Browse Files
              </label>
            </div>
          </div>

          <!-- File Info -->
          <div v-if="visitsFile" class="flex items-center justify-between bg-green-50 p-3 rounded-lg mb-4">
            <div class="flex items-center gap-3">
              <i class="fas fa-file-excel text-green-600"></i>
              <div>
                <p class="font-medium text-gray-800">{{ visitsFileName }}</p>
                <p class="text-sm text-gray-600">{{ visitsFileSize }}</p>
              </div>
            </div>
            <button @click="visitsFile = null" class="text-gray-500 hover:text-red-500 transition">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Upload Button -->
          <button
            class="w-full bg-green-600 text-white px-4 py-3 rounded-md shadow hover:bg-green-700 transition flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!visitsFile || uploading.visits" @click="uploadFile('visits')">
            <template v-if="uploading.visits">
              <i class="fas fa-spinner fa-spin mr-2"></i>
              Uploading... ({{ uploadProgress.visits }}%)
            </template>
            <template v-else>
              <i class="fas fa-upload mr-2"></i>
              Upload & Validate Visits Data
            </template>
          </button>
        </Card>
      </div>

      <!-- Cross Validation Section -->
      <div class="mt-4">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold text-gray-800">Cross-File Validation</h2>
          <button
            class="bg-purple-600 text-white px-4 py-2 rounded-md shadow hover:bg-purple-700 transition flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!patientsFile || !visitsFile || validating" @click="validateBothFiles">
            <i class="fas fa-link mr-2"></i>
            Validate Patient Numbers
          </button>
        </div>

        <!-- Validation Progress -->
        <div v-if="validating" class="bg-gray-100 rounded-lg p-4 mb-6">
          <div class="flex items-center justify-between mb-2">
            <p class="font-medium text-gray-700">Validating data consistency...</p>
            <span class="font-bold text-purple-600">{{ validationProgress }}%</span>
          </div>
          <div class="h-2 bg-gray-300 rounded-full overflow-hidden">
            <div class="h-full bg-purple-600 transition-all duration-300" :style="{ width: validationProgress + '%' }">
            </div>
          </div>
        </div>

        <!-- Validation Results -->
        <div v-if="validationResults.patients || validationResults.visits || validationResults.crossValidation"
          class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Patients Validation Results -->
          <Card class="p-4 border-l-4" :class="{
            'border-blue-500': patientValidationStatus === 'pending',
            'border-green-500': patientValidationStatus === 'success',
            'border-yellow-500': patientValidationStatus === 'warning',
            'border-red-500': patientValidationStatus === 'error'
          }">
            <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
              <i class="fas fa-user-injured text-blue-500"></i>
              Patients Validation
            </h3>

            <div v-if="uploading.patients" class="gear-container">
              <i class="fas fa-cog text-blue-400 gear-large"></i>
              <i class="fas fa-cog text-blue-600 gear-medium"></i>
            </div>

            <div v-else>
              <div v-if="validationResults.patients">
                <!-- Error State -->
                <div v-if="patientValidationStatus === 'error'" class="text-red-600 bg-red-50 p-3 rounded">
                  <i class="fas fa-exclamation-circle mr-2"></i>
                  {{ validationResults.patients.message || 'Validation error' }}

                  <!-- Header Mismatch Details -->
                  <div v-if="validationResults.patients.missing_headers" class="mt-3">
                    <p class="font-medium text-sm">Missing Headers:</p>
                    <ul class="list-disc pl-5 mt-1">
                      <li v-for="header in validationResults.patients.missing_headers" :key="header" class="text-sm">
                        {{ header }}
                      </li>
                    </ul>
                  </div>

                  <div v-if="validationResults.patients.extra_headers" class="mt-3">
                    <p class="font-medium text-sm">Extra Headers:</p>
                    <ul class="list-disc pl-5 mt-1">
                      <li v-for="header in validationResults.patients.extra_headers" :key="header" class="text-sm">
                        {{ header }}
                      </li>
                    </ul>
                  </div>

                  <div v-if="validationResults.patients.required_headers" class="mt-3">
                    <p class="font-medium text-sm">Required Headers:</p>
                    <ul class="list-disc pl-5 mt-1 columns-2">
                      <li v-for="header in validationResults.patients.required_headers" :key="header" class="text-sm">
                        {{ header }}
                      </li>
                    </ul>
                  </div>
                </div>

                <!-- Success/Warning State -->
                <template v-else>
                  <div class="flex items-center gap-2 mb-3">
                    <i v-if="patientValidationStatus === 'warning'"
                      class="fas fa-exclamation-triangle text-yellow-500"></i>
                    <i v-else class="fas fa-check-circle text-green-500"></i>
                    <p class="font-medium">{{ validationResults.patients.message || 'Validation complete' }}</p>
                  </div>

                  <!-- Issues Display -->
                  <div v-if="validationResults.patients.issues?.length" class="bg-yellow-50 p-3 rounded text-sm">
                    <p class="font-medium mb-2">Data Quality Issues:</p>
                    <div v-for="(issue, index) in validationResults.patients.issues" :key="index" class="mb-2">
                      <p class="font-medium">{{ issue.message }}</p>
                      <p class="text-xs">Found in {{ issue.count }} records</p>

                      <!-- Example Rows -->
                      <div v-if="issue.example_rows" class="mt-1">
                        <p class="text-xs font-medium">Example rows:</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                          <span v-for="(row, idx) in issue.example_rows" :key="idx"
                            class="bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-xs">
                            {{ row }}
                          </span>
                        </div>
                      </div>

                      <!-- Example Values -->
                      <div v-if="issue.examples" class="mt-1">
                        <p class="text-xs font-medium">Example values:</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                          <span v-for="(ex, idx) in issue.examples" :key="idx"
                            class="bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-xs">
                            Row {{ ex.row }}: {{ ex.value }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- File Stats -->
                  <div class="mt-3 text-sm grid grid-cols-2 gap-2">
                    <div>
                      <p class="font-medium text-gray-600">File Name:</p>
                      <p class="truncate">{{ validationResults.patients.file_name }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-gray-600">File Size:</p>
                      <p>{{ formatFileSize(validationResults.patients.file_size) }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-gray-600">Records:</p>
                      <p>{{ validationResults.patients.record_count }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-gray-600">Unique IDs:</p>
                      <p>{{ validationResults.patients.unique_patient_numbers }}</p>
                    </div>
                  </div>

                  <!-- Data Sample Preview -->
                  <div v-if="validationResults.patients.data_sample" class="mt-4">
                    <p class="font-medium text-sm mb-2">Data Sample:</p>
                    <div class="overflow-x-auto max-h-40 overflow-y-auto border rounded">
                      <table class="min-w-full text-xs">
                        <thead class="bg-gray-100 sticky top-0">
                          <tr>
                            <th v-for="(header, idx) in validationResults.patients.headers" :key="idx"
                              class="px-2 py-1 text-left font-medium">
                              {{ header }}
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(row, rowIdx) in validationResults.patients.data_sample" :key="rowIdx">
                            <td v-for="(value, colIdx) in row" :key="colIdx" class="px-2 py-1 border-t">
                              {{ formatSampleValue(value) }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </template>
              </div>

              <div v-else class="text-gray-500 italic">
                No validation performed yet
              </div>
            </div>


          </Card>

          <!-- Visits Validation Results -->
          <Card class="p-4 border-l-4" :class="{
            'border-blue-500': visitsValidationStatus === 'pending',
            'border-green-500': visitsValidationStatus === 'success',
            'border-yellow-500': visitsValidationStatus === 'warning',
            'border-red-500': visitsValidationStatus === 'error'
          }">

            <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
              <i class="fas fa-calendar-days text-green-500"></i>
              Visits Validation
            </h3>


            <div v-if="uploading.visits" class="gear-container">
              <i class="fas fa-cog text-green-400 gear-large"></i>
              <i class="fas fa-cog text-green-600 gear-medium"></i>
            </div>
            <div v-else>

              <div v-if="validationResults.visits">
                <!-- Error State -->
                <div v-if="visitsValidationStatus === 'error'" class="text-red-600 bg-red-50 p-3 rounded">
                  <i class="fas fa-exclamation-circle mr-2"></i>
                  {{ validationResults.visits.message || 'Validation error' }}

                  <!-- Header Mismatch Details -->
                  <div v-if="validationResults.visits.missing_headers" class="mt-3">
                    <p class="font-medium text-sm">Missing Headers:</p>
                    <ul class="list-disc pl-5 mt-1">
                      <li v-for="header in validationResults.visits.missing_headers" :key="header" class="text-sm">
                        {{ header }}
                      </li>
                    </ul>
                  </div>

                  <div v-if="validationResults.visits.extra_headers" class="mt-3">
                    <p class="font-medium text-sm">Extra Headers:</p>
                    <ul class="list-disc pl-5 mt-1">
                      <li v-for="header in validationResults.visits.extra_headers" :key="header" class="text-sm">
                        {{ header }}
                      </li>
                    </ul>
                  </div>

                  <div v-if="validationResults.visits.required_headers" class="mt-3">
                    <p class="font-medium text-sm">Required Headers:</p>
                    <ul class="list-disc pl-5 mt-1 columns-2">
                      <li v-for="header in validationResults.visits.required_headers" :key="header" class="text-sm">
                        {{ header }}
                      </li>
                    </ul>
                  </div>
                </div>

                <!-- Success/Warning State -->
                <template v-else>
                  <div class="flex items-center gap-2 mb-3">
                    <i v-if="visitsValidationStatus === 'warning'"
                      class="fas fa-exclamation-triangle text-yellow-500"></i>
                    <i v-else class="fas fa-check-circle text-green-500"></i>
                    <p class="font-medium">{{ validationResults.visits.message || 'Validation complete' }}</p>
                  </div>

                  <!-- Issues Display -->
                  <div v-if="validationResults.visits.issues?.length" class="bg-yellow-50 p-3 rounded text-sm">
                    <p class="font-medium mb-2">Data Quality Issues:</p>
                    <div v-for="(issue, index) in validationResults.visits.issues" :key="index" class="mb-2">
                      <p class="font-medium">{{ issue.message }}</p>
                      <p class="text-xs">Found in {{ issue.count }} records</p>

                      <!-- Example Rows -->
                      <div v-if="issue.example_rows" class="mt-1">
                        <p class="text-xs font-medium">Example rows:</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                          <span v-for="(row, idx) in issue.example_rows" :key="idx"
                            class="bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-xs">
                            {{ row }}
                          </span>
                        </div>
                      </div>

                      <!-- Example Values -->
                      <div v-if="issue.examples" class="mt-1">
                        <p class="text-xs font-medium">Example values:</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                          <span v-for="(ex, idx) in issue.examples" :key="idx"
                            class="bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-xs">
                            Row {{ ex.row }}: {{ ex.value }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- File Stats -->
                  <div class="mt-3 text-sm grid grid-cols-2 gap-2">
                    <div>
                      <p class="font-medium text-gray-600">File Name:</p>
                      <p class="truncate">{{ validationResults.visits.file_name }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-gray-600">File Size:</p>
                      <p>{{ formatFileSize(validationResults.visits.file_size) }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-gray-600">Records:</p>
                      <p>{{ validationResults.visits.record_count }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-gray-600">Unique IDs:</p>
                      <p>{{ validationResults.visits.unique_patient_numbers }}</p>
                    </div>
                  </div>

                  <!-- Data Sample Preview -->
                  <div v-if="validationResults.visits.data_sample" class="mt-4">
                    <p class="font-medium text-sm mb-2">Data Sample:</p>
                    <div class="overflow-x-auto max-h-40 overflow-y-auto border rounded">
                      <table class="min-w-full text-xs">
                        <thead class="bg-gray-100 sticky top-0">
                          <tr>
                            <th v-for="(header, idx) in validationResults.visits.headers" :key="idx"
                              class="px-2 py-1 text-left font-medium">
                              {{ header }}
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(row, rowIdx) in validationResults.visits.data_sample" :key="rowIdx">
                            <td v-for="(value, colIdx) in row" :key="colIdx" class="px-2 py-1 border-t">
                              {{ formatSampleValue(value) }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </template>
              </div>

              <div v-else class="text-gray-500 italic">
                No validation performed yet
              </div>
            </div>


          </Card>

          <!-- Cross Validation Results -->
          <Card class="p-4 border-l-4" :class="{
            'border-blue-500': crossValidationStatus === 'pending',
            'border-green-500': crossValidationStatus === 'success',
            'border-yellow-500': crossValidationStatus === 'warning',
            'border-red-500': crossValidationStatus === 'error'
          }">
            <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
              <i class="fas fa-link text-purple-500"></i>
              Cross-File Validation
            </h3>

            <div v-if="validating" class="gear-container">
              <i class="fas fa-cog text-purple-400 gear-large"></i>
              <i class="fas fa-cog text-purple-600 gear-medium"></i>
              <p class="absolute bottom-0 text-gray-600 font-medium">Validating...</p>
            </div>

            <div v-else>
              <div v-if="validationResults.crossValidation">
                <template v-if="crossValidationStatus !== 'error'">
                  <div class="flex items-center gap-2 mb-3">
                    <i v-if="crossValidationStatus === 'warning'"
                      class="fas fa-exclamation-triangle text-yellow-500"></i>
                    <i v-else class="fas fa-check-circle text-green-500"></i>
                    <p class="font-medium">{{ validationResults.crossValidation.message }}</p>
                  </div>

                  <!-- Issues Display -->
                  <div v-if="validationResults.crossValidation.issues?.length" class="bg-yellow-50 p-3 rounded text-sm">
                    <p class="font-medium mb-2">Consistency Issues:</p>
                    <div v-for="(issue, index) in validationResults.crossValidation.issues" :key="index" class="mb-2">
                      <p class="font-medium">{{ issue.message }}</p>
                      <p class="text-xs">Count: {{ issue.count }}</p>

                      <!-- Example PatientNumbers -->
                      <div v-if="issue.examples" class="mt-1">
                        <p class="text-xs font-medium">Examples:</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                          <span v-for="(ex, idx) in issue.examples" :key="idx"
                            class="bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-xs">
                            {{ ex }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Stats -->
                  <div class="mt-3 text-sm grid grid-cols-2 gap-2">
                    <div>
                      <p class="font-medium text-gray-600">Orphaned Visits:</p>
                      <p>{{ validationResults.crossValidation.orphaned_visits_count || 0 }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-gray-600">Patients w/o Visits:</p>
                      <p>{{ validationResults.crossValidation.patients_without_visits_count || 0 }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-gray-600">Total Patients:</p>
                      <p>{{ validationResults.crossValidation.stats?.total_patients || 'N/A' }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-gray-600">Total Visits:</p>
                      <p>{{ validationResults.crossValidation.stats?.total_visits || 'N/A' }}</p>
                    </div>
                  </div>
                </template>
              </div>

              <div v-else class="text-gray-500 italic">
                No cross-validation performed yet
              </div>
            </div>


          </Card>
        </div>

        <!-- Final Action Buttons -->
        <div v-if="validationResults.crossValidation" class="flex justify-end gap-3 mt-8">
          <button class="bg-gray-600 text-white px-6 py-3 rounded-md shadow hover:bg-gray-700 transition">
            <i class="fas fa-redo mr-2"></i> Re-upload Files
          </button>
          <button
            class="bg-indigo-600 text-white px-6 py-3 rounded-md shadow hover:bg-indigo-700 transition flex items-center">
            <i class="fas fa-database mr-2"></i> Import to Database
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.card {
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.card:hover {
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

input[type='file'] {
  margin-bottom: 0.5rem;
}

@keyframes pulse {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.5;
  }
}

/* New gear animations */
.gear-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 150px;
  /* Adjust as needed */
  position: relative;
}

.gear-large {
  font-size: 5rem;
  /* Large size */
  animation: spin 4s linear infinite;
  position: absolute;
}

.gear-medium {
  font-size: 3.5rem;
  /* Medium size */
  animation: spin-reverse 3s linear infinite;
  position: absolute;
  margin-left: 45px;
  margin-top: 30px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

@keyframes spin-reverse {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(-360deg);
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>