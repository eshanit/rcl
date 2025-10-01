<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import {
  ArrowDownTrayIcon,
  CloudArrowUpIcon,
  DocumentTextIcon,
  DocumentCheckIcon,
  LinkIcon,
  ArrowPathIcon,
  ArrowLeftIcon,
  ArrowRightIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  UserGroupIcon,
  BuildingOfficeIcon,
  ArrowDownCircleIcon,
  ServerStackIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline';
import AppLayout from '@/layouts/AppLayout.vue';
import { Stepper } from '@/components/ui/stepper';
import ProgressBar from '@/components/ProgressBar.vue';
import { Progress } from '@/components/ui/progress';


const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Data Upload', href: '/upload' },
];

// Validation state
const props = defineProps<{ validationResult?: any }>();

const validationResults = ref(props.validationResult || {});
// Files state
const patientsFile = ref<File | null>(null);
const visitsFile = ref<File | null>(null);

// Stepper configuration
const steps = ref([
  { id: 1, title: 'Patients Data', description: 'Upload patients file', status: 'in-progress' },
  { id: 2, title: 'Visits Data', description: 'Upload visits file', status: 'pending' },
  { id: 3, title: 'Cross Validation', description: 'Validate consistency', status: 'pending' },
  { id: 4, title: 'Import', description: 'Import to database', status: 'pending' }
]);

// File state
const patientsForm = useForm({ patients_file: null });
const visitsForm = useForm({ visits_file: null });
const isDragging = ref({ patients: false, visits: false });

// Validation state

const validating = ref(false);
const validationProgress = ref(0);

// Import state
const importStatus = ref('idle'); // idle, importing, success, error
const importProgress = ref(0);
const importStats = ref();

// Computed properties
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

// File handling functions
function onFileChange(event: Event, type: 'patients' | 'visits') {
  const file = (event.target as HTMLInputElement).files?.[0] || null;
  setFile(file, type);
}

function setFile(file: File | null, type: 'patients' | 'visits') {
  if (type === 'patients') patientsFile.value = file;
  if (type === 'visits') visitsFile.value = file;

  // Reset validation when files change
  validationResults.value[type] = null;
  validationResults.value.crossValidation = null;
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

// Upload state
const uploading = ref({ patients: false, visits: false });
const uploadProgress = ref({ patients: 0, visits: 0 });

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
        steps.value[2].status = 'completed';
        steps.value[3].status = 'in-progress';
        validating.value = false;

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

const importing = ref(false);
const importError = ref('idle');
// Import function
async function importData() {
  importing.value = true;
  importStatus.value = 'importing';
  importError.value = 'idle';
  importStats.value = {};

  router.post('/import-data', {}, {
    onSuccess: (response) => {
      importStatus.value = 'success';
      importStats.value = response.props.stats || {};
      importProgress.value = 100;
      steps.value[3].status = 'completed';

      // Reset files and validation after successful import
      patientsFile.value = null;
      visitsFile.value = null;
      validationResults.value = {};
    },
    onError: (errors) => {
      importStatus.value = 'error';
      importError.value = errors.message || 'Import failed';
    },
    onFinish: () => {
      importing.value = false;
    }
  });
}



// Reset function
function resetProcess() {
  patientsForm.reset();
  visitsForm.reset();
  validationResults.value = {};
  importStatus.value = 'idle';
  importProgress.value = 0;

  steps.value.forEach((step, index) => {
    step.status = index === 0 ? 'in-progress' : 'pending';
  });
}

// Helper functions
function formatFileSize(bytes: number) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Watch for step changes to update stepper status
watch(importProgress, (newVal) => {
  if (newVal === 100) {
    importStatus.value = 'success';
  }
});

// Add this helper function
function formatSampleValue(value: any): string {
  if (value === null || value === undefined) return '';
  if (typeof value === 'object' && value.date) {
    return new Date(value.date).toLocaleDateString();
  }
  if (typeof value === 'boolean') return value ? 'Yes' : 'No';
  return String(value);
}

</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen py-8 bg-gray-50">
      <div class="max-w-6xl mx-auto px-4">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Medical Data Validation</h1>
            <p class="text-gray-600 mt-2">
              Upload and validate patient and visit data before importing to the database
            </p>
          </div>
          <div class="flex items-center gap-4">
            <div class="bg-white rounded-xl p-4 shadow-sm flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                <DocumentCheckIcon class="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <p class="text-sm text-gray-600">Validation Status</p>
                <p v-if="validating" class="font-medium text-blue-600">In Progress...</p>
                <p v-else class="font-medium text-green-600">Ready</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Stepper -->
        <Stepper :steps="steps" class="mb-10" />

        <!-- Step 1: Patients Data -->
        <div v-if="steps[0].status !== 'pending'" class="card p-6 bg-white rounded-xl shadow">
          <div class="flex items-start gap-5 mb-8">
            <div class="w-14 h-14 rounded-xl bg-blue-50 flex items-center justify-center">
              <UserGroupIcon class="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900">Upload Patients Data</h2>
              <p class="text-gray-600 mt-1">Upload and validate your patients data file</p>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Upload Section -->
            <div>
              <div
                class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center cursor-pointer transition-all"
                :class="{
                  'border-blue-500 bg-blue-50': isDragging.patients,
                  'border-red-300': patientsForm.errors.patients_file
                }" @dragover.prevent="onDragOver($event, 'patients')" @dragleave="onDragLeave('patients')"
                @drop.prevent="onDrop($event, 'patients')">
                <Input type="file" accept=".xlsx,.xls" @change="(e: any) => onFileChange(e, 'patients')" class="hidden"
                  id="patients-file-input" />
                <div class="flex flex-col items-center justify-center gap-4">
                  <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center">
                    <CloudArrowUpIcon class="w-8 h-8 text-blue-600" />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">Drag & drop your file here</p>
                    <p class="text-gray-500 text-sm mt-1">Supports .xlsx, .xls, or .csv files</p>
                  </div>
                  <p class="text-sm text-gray-500 mb-3">or</p>
                  <Label for="patients-file-input"
                    class="bg-blue-600 text-white px-5 py-2.5 rounded-lg cursor-pointer hover:bg-blue-700 transition font-medium mt-2 inline-block">
                    Browse Files
                  </Label>
                </div>
              </div>

              <!-- File Info -->
              <div v-if="patientsFile" class="mt-6 bg-gray-50 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                    <DocumentTextIcon class="w-5 h-5 text-green-600" />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 truncate max-w-[200px]">{{ patientFileName }}</p>
                    <p class="text-gray-500 text-sm">{{ patientFileSize }}</p>
                  </div>
                </div>
                <button @click="patientsFile = null" class="text-gray-400 hover:text-red-500 transition">
                  <XCircleIcon class="w-5 h-5" />
                </button>
              </div>

              <!-- Upload Button -->
              <Button @click="uploadFile('patients')" :disabled="!patientsFile || uploading.patients"
                class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition flex items-center justify-center disabled:opacity-70 disabled:cursor-not-allowed">
                <ArrowPathIcon v-if="uploading.patients" class="w-5 h-5 mr-2 animate-spin" />
                <CloudArrowUpIcon v-else class="w-5 h-5 mr-2" />
                {{ patientsForm.processing ? 'Uploading...' : 'Upload & Validate' }}
              </Button>
            </div>

            <!-- Validation Results -->
            <div>
              <div class="bg-gray-50 rounded-xl p-5 border-l-4" :class="{
                'border-gray-300': patientValidationStatus === 'pending',
                'border-green-500': patientValidationStatus === 'success',
                'border-yellow-500': patientValidationStatus === 'warning',
                'border-red-500': patientValidationStatus === 'error'
              }">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2 mb-4">
                  <DocumentCheckIcon class="w-5 h-5" />
                  Patients Validation
                </h3>

                <div v-if="patientsForm.processing" class="flex flex-col items-center justify-center py-8">
                  <ArrowPathIcon class="w-10 h-10 text-blue-600 mb-4 animate-spin" />
                  <p class="text-gray-600">Validating your file...</p>
                </div>

                <div v-else>
                  <div v-if="validationResults.patients">
                    <!-- Error State -->
                    <div v-if="patientValidationStatus === 'error'" class="bg-red-50 text-red-700 p-4 rounded-lg">
                      <div class="flex items-center gap-2 mb-2">
                        <ExclamationTriangleIcon class="w-5 h-5" />
                        <p class="font-medium">{{ validationResults.patients.message || 'Validation error' }}</p>
                      </div>

                      <div v-if="validationResults.patients.missing_headers" class="mt-3">
                        <p class="font-medium text-sm">Missing Headers:</p>
                        <ul class="list-disc pl-5 mt-1">
                          <li v-for="header in validationResults.patients.missing_headers" :key="header"
                            class="text-sm">
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
                          <li v-for="header in validationResults.patients.required_headers" :key="header"
                            class="text-sm">
                            {{ header }}
                          </li>
                        </ul>
                      </div>
                    </div>

                    <!-- Success/Warning State -->
                    <template v-else>
                      <div class="flex items-center gap-2 mb-3">
                        <ExclamationTriangleIcon v-if="patientValidationStatus === 'warning'"
                          class="w-5 h-5 text-yellow-500" />
                        <CheckCircleIcon v-else class="w-5 h-5 text-green-500" />
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

                      <!-- Stats -->
                      <div class="grid grid-cols-2 gap-4 mt-5">
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                          <p class="text-sm text-gray-500">Total Records</p>
                          <p class="text-xl font-bold mt-1">{{ validationResults.patients.record_count || 0 }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                          <p class="text-sm text-gray-500">Unique Patient IDs</p>
                          <p class="text-xl font-bold mt-1">{{ validationResults.patients.unique_patient_numbers?.length
                            || 0 }}</p>
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

                  <div v-else class="flex flex-col items-center justify-center py-8 text-gray-500">
                    <DocumentTextIcon class="w-10 h-10 mb-3" />
                    <p>No validation performed yet</p>
                  </div>
                </div>
              </div>

              <!-- Navigation -->
              <div v-if="validationResults.patients && patientValidationStatus !== 'error'"
                class="mt-6 flex justify-end">
                <button @click="steps[1].status = 'in-progress'"
                  class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-lg transition flex items-center">
                  Continue to Visits
                  <ArrowRightIcon class="w-4 h-4 ml-2" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Visits Data -->
        <div v-if="steps[1].status !== 'pending'" class="card p-6 bg-white rounded-xl shadow mt-8">
          <div class="flex items-start gap-5 mb-8">
            <div class="w-14 h-14 rounded-xl bg-purple-50 flex items-center justify-center">
              <BuildingOfficeIcon class="w-6 h-6 text-purple-600" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900">Upload Visits Data</h2>
              <p class="text-gray-600 mt-1">Upload and validate your medical visits data file</p>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Upload Section -->
            <div>
              <div
                class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center cursor-pointer transition-all"
                :class="{
                  'border-purple-500 bg-purple-50': isDragging.visits,
                  'border-red-300': visitsForm.errors.visits_file
                }" @dragover.prevent="onDragOver($event, 'visits')" @dragleave="onDragLeave('visits')"
                @drop.prevent="onDrop($event, 'visits')">
                <Input type="file" accept=".xlsx,.xls,.csv" @change="onFileChange($event, 'visits')" class="hidden"
                  id="visits-file-input" />
                <div class="flex flex-col items-center justify-center gap-4">
                  <div class="w-16 h-16 rounded-full bg-purple-50 flex items-center justify-center">
                    <CloudArrowUpIcon class="w-8 h-8 text-purple-600" />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">Drag & drop your file here</p>
                    <p class="text-gray-500 text-sm mt-1">Supports .xlsx, .xls, or .csv files</p>
                  </div>
                  <Label for="visits-file-input"
                    class="bg-purple-600 text-white px-5 py-2.5 rounded-lg cursor-pointer hover:bg-purple-700 transition font-medium mt-2 inline-block">
                    Browse Files
                  </Label>
                </div>
              </div>

              <!-- File Info -->
              <div v-if="visitsFile" class="mt-6 bg-gray-50 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                    <DocumentTextIcon class="w-5 h-5 text-green-600" />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 truncate max-w-[200px]">{{ visitsFileName }}</p>
                    <p class="text-gray-500 text-sm">{{ visitsFileSize }}</p>
                  </div>
                </div>
                <button @click="visitsFile = null" class="text-gray-400 hover:text-red-500 transition">
                  <XCircleIcon class="w-5 h-5" />
                </button>
              </div>

              <!-- Upload Button -->
              <Button @click="uploadFile('visits')" :disabled="!visitsFile || uploading.visits"
                class="w-full mt-6 bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 rounded-lg transition flex items-center justify-center disabled:opacity-70 disabled:cursor-not-allowed">
                <ArrowPathIcon v-if="uploading.visits" class="w-5 h-5 mr-2 animate-spin" />
                <CloudArrowUpIcon v-else class="w-5 h-5 mr-2" />
                {{ uploading.visits ? 'Uploading...' : 'Upload & Validate' }}
              </Button>
            </div>

            <!-- Validation Results -->
            <div>
              <div class="bg-gray-50 rounded-xl p-5 border-l-4" :class="{
                'border-gray-300': visitsValidationStatus === 'pending',
                'border-green-500': visitsValidationStatus === 'success',
                'border-yellow-500': visitsValidationStatus === 'warning',
                'border-red-500': visitsValidationStatus === 'error'
              }">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2 mb-4">
                  <DocumentCheckIcon class="w-5 h-5" />
                  Visits Validation
                </h3>

                <div v-if="uploading.visits" class="flex flex-col items-center justify-center py-8">
                  <ArrowPathIcon class="w-10 h-10 text-purple-600 mb-4 animate-spin" />
                  <p class="text-gray-600">Validating your file...</p>
                </div>

                <div v-else>
                  <div v-if="validationResults.visits">
                    <!-- Error State -->
                    <div v-if="visitsValidationStatus === 'error'" class="bg-red-50 text-red-700 p-4 rounded-lg">
                      <div class="flex items-center gap-2 mb-2">
                        <ExclamationTriangleIcon class="w-5 h-5" />
                        <p class="font-medium">{{ validationResults.visits.message || 'Validation error' }}</p>
                      </div>

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
                        <ExclamationTriangleIcon v-if="visitsValidationStatus === 'warning'"
                          class="w-5 h-5 text-yellow-500" />
                        <CheckCircleIcon v-else class="w-5 h-5 text-green-500" />
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

                      <!-- Stats -->
                      <div class="grid grid-cols-2 gap-4 mt-5">
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                          <p class="text-sm text-gray-500">Total Records</p>
                          <p class="text-xl font-bold mt-1">{{ validationResults.visits.record_count || 0 }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                          <p class="text-sm text-gray-500">Unique Patient IDs</p>
                          <p class="text-xl font-bold mt-1">{{ validationResults.visits.unique_patient_numbers?.length
                            || 0 }}</p>
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

                  <div v-else class="flex flex-col items-center justify-center py-8 text-gray-500">
                    <DocumentTextIcon class="w-10 h-10 mb-3" />
                    <p>No validation performed yet</p>
                  </div>
                </div>
              </div>

              <!-- Navigation -->
              <div class="mt-6 flex justify-between">
                <button @click="steps[0].status = 'in-progress'"
                  class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-6 rounded-lg transition flex items-center">
                  <ArrowLeftIcon class="w-4 h-4 mr-2" />
                  Back
                </button>

                <button v-if="validationResults.visits && visitsValidationStatus !== 'error'"
                  @click="steps[2].status = 'in-progress'"
                  class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2.5 px-6 rounded-lg transition flex items-center">
                  Continue to Cross Validation
                  <ArrowRightIcon class="w-4 h-4 ml-2" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3: Cross Validation -->
        <div v-if="steps[2].status !== 'pending'" class="card p-6 bg-white rounded-xl shadow mt-8">
          <div class="flex items-start gap-5 mb-8">
            <div class="w-14 h-14 rounded-xl bg-indigo-50 flex items-center justify-center">
              <LinkIcon class="w-6 h-6 text-indigo-600" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900">Cross-File Validation</h2>
              <p class="text-gray-600 mt-1">Verify consistency between patients and visits data</p>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Description -->
            <div class="bg-indigo-50 rounded-2xl p-6">
              <div class="flex items-center gap-3 mb-4">
                <LinkIcon class="w-6 h-6 text-indigo-600" />
                <h3 class="text-lg font-semibold text-gray-900">Data Consistency Check</h3>
              </div>
              <p class="text-gray-700">
                This step verifies that patient IDs in the visits file match those in the patients file
                to ensure data integrity before importing.
              </p>

              <div class="mt-6 grid grid-cols-2 gap-4">
                <div class="bg-white rounded-xl p-4 shadow-sm">
                  <p class="text-sm text-gray-500">Patients File Records</p>
                  <p class="text-xl font-bold text-gray-900 mt-1">
                    {{ validationResults.patients?.record_count || 0 }}
                  </p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm">
                  <p class="text-sm text-gray-500">Visits File Records</p>
                  <p class="text-xl font-bold text-gray-900 mt-1">
                    {{ validationResults.visits?.record_count || 0 }}
                  </p>
                </div>
              </div>

              <button @click="validateBothFiles"
                :disabled="validating || !validationResults.patients || !validationResults.visits"
                class="w-full mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-lg transition flex items-center justify-center disabled:opacity-70 disabled:cursor-not-allowed">
                <ArrowPathIcon v-if="validating" class="w-5 h-5 mr-2 animate-spin" />
                <LinkIcon v-else class="w-5 h-5 mr-2" />
                {{ validating ? 'Validating...' : 'Run Cross Validation' }}
              </button>
            </div>

            <!-- Validation Results -->
            <div>
              <div class="bg-gray-50 rounded-xl p-5 border-l-4" :class="{
                'border-gray-300': crossValidationStatus === 'pending',
                'border-green-500': crossValidationStatus === 'success',
                'border-yellow-500': crossValidationStatus === 'warning',
                'border-red-500': crossValidationStatus === 'error'
              }">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2 mb-4">
                  <DocumentCheckIcon class="w-5 h-5" />
                  Cross Validation Results
                </h3>

                <div v-if="validating" class="flex flex-col items-center justify-center py-8">
                  <ArrowPathIcon class="w-10 h-10 text-indigo-600 mb-4 animate-spin" />
                  <p class="text-gray-600">Validating data consistency...</p>
                </div>

                <div v-else>
                  <div v-if="validationResults.crossValidation">
                    <!-- Error State -->
                    <div v-if="crossValidationStatus === 'error'" class="bg-red-50 text-red-700 p-4 rounded-lg">
                      <div class="flex items-center gap-2 mb-2">
                        <ExclamationTriangleIcon class="w-5 h-5" />
                        <p class="font-medium">{{ validationResults.crossValidation.message || 'Validation error' }}</p>
                      </div>
                    </div>

                    <!-- Success/Warning State -->
                    <template v-else>
                      <div class="flex items-center gap-2 mb-3">
                        <ExclamationTriangleIcon v-if="crossValidationStatus === 'warning'"
                          class="w-5 h-5 text-yellow-500" />
                        <CheckCircleIcon v-else class="w-5 h-5 text-green-500" />
                        <p class="font-medium">{{ validationResults.crossValidation.message || 'Cross validation complete' }}</p>
                      </div>

                      <!-- Stats -->
                      <div class="grid grid-cols-2 gap-4 mt-5">
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                          <p class="text-sm text-gray-500">Orphaned Visits</p>
                          <p class="text-xl font-bold mt-1">{{ validationResults.crossValidation.orphaned_visits_count
                            || 0 }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 shadow-sm">
                          <p class="text-sm text-gray-500">Patients w/o Visits</p>
                          <p class="text-xl font-bold mt-1">{{
                            validationResults.crossValidation.patients_without_visits_count || 0 }}</p>
                        </div>
                      </div>
                    </template>
                  </div>

                  <div v-else class="flex flex-col items-center justify-center py-8 text-gray-500">
                    <LinkIcon class="w-10 h-10 mb-3" />
                    <p>Cross validation not performed yet</p>
                  </div>
                </div>
              </div>

              <!-- Navigation -->
              <div class="mt-6 flex justify-between">
                <button @click="steps[1].status = 'in-progress'"
                  class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-6 rounded-lg transition flex items-center">
                  <ArrowLeftIcon class="w-4 h-4 mr-2" />
                  Back
                </button>

                <button v-if="validationResults.crossValidation && crossValidationStatus !== 'error'"
                  @click="steps[3].status = 'in-progress'"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-6 rounded-lg transition flex items-center">
                  Continue to Import
                  <ArrowRightIcon class="w-4 h-4 ml-2" />
                </button>
              </div>
            </div>
          </div>
        </div>


        <!-- Step 4: Import Data -->
        <div v-if="steps[3].status !== 'pending'" class="card p-6 bg-white rounded-xl shadow mt-8">
          <div class="flex items-start gap-5 mb-8">
            <div class="w-14 h-14 rounded-xl bg-teal-50 flex items-center justify-center">
              <ServerStackIcon class="w-6 h-6 text-teal-600" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900">Import Data to Database</h2>
              <p class="text-gray-600 mt-1">Finalize and import validated data</p>
            </div>
          </div>

          <div class="max-w-3xl mx-auto">
            <div class="bg-gray-50 rounded-2xl p-8">
              <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 rounded-full bg-teal-100 flex items-center justify-center mb-4">
                  <ArrowDownCircleIcon class="w-8 h-8 text-teal-600" />
                </div>
                <h3 class="text-xl font-bold text-gray-900">Ready to Import</h3>
                <p class="text-gray-600 mt-2 max-w-md">
                  All validations have passed. You can now import the data into the database.
                </p>

                <div class="mt-8 grid grid-cols-2 gap-6 w-full max-w-md">
                  <div class="bg-white rounded-xl p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Patients to Import</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                      {{ validationResults.patients?.record_count || 0 }}
                    </p>
                  </div>
                  <div class="bg-white rounded-xl p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Visits to Import</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                      {{ validationResults.visits?.record_count || 0 }}
                    </p>
                  </div>
                </div>

                <!-- Progress Bar -->
                <div v-if="importStatus === 'importing'" class="w-full mt-8">
                  <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Importing data...</span>
                    <span>{{ importProgress }}%</span>
                  </div>
                  <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-teal-600 rounded-full" :style="{ width: importProgress + '%' }"
                      :class="{ 'transition-all duration-500': importStatus === 'importing' }"></div>
                  </div>
                </div>

                <!-- Success Message -->
                <div v-if="importStatus === 'success'"
                  class="mt-8 bg-green-50 text-green-700 p-4 rounded-lg w-full max-w-md">
                  <div class="flex items-center justify-center gap-3">
                    <CheckCircleIcon class="w-5 h-5" />
                    <p class="font-medium">Data imported successfully!</p>
                  </div>

                  <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="bg-white p-3 rounded-lg">
                      <p class="text-sm text-gray-600">Patients Imported</p>
                      <p class="font-bold text-lg">{{ importStats?.patients || 0 }}</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg">
                      <p class="text-sm text-gray-600">Visits Imported</p>
                      <p class="font-bold text-lg">{{ importStats?.visits || 0 }}</p>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex gap-4">
                  <button @click="resetProcess"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-6 rounded-lg transition flex items-center">
                    <ArrowPathIcon class="w-5 h-5 mr-2" />
                    Start New Import
                  </button>

                  <button @click="importData" :disabled="importStatus === 'importing' || importStatus === 'success'"
                    class="bg-teal-600 hover:bg-teal-700 text-white font-medium py-2.5 px-6 rounded-lg transition flex items-center disabled:opacity-70 disabled:cursor-not-allowed">
                    <ArrowPathIcon v-if="importStatus === 'importing'" class="w-5 h-5 mr-2 animate-spin" />
                    <ServerStackIcon v-else class="w-5 h-5 mr-2" />
                    {{ importStatus === 'importing' ? 'Importing...' : 'Import to Database' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.card {
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border-radius: 16px;
  overflow: hidden;
  background: white;
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

.upload-area.dragover {
  border-color: #4361ee;
  background: #eef2ff;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>