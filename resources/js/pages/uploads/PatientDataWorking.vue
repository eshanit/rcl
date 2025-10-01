<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router, usePoll } from '@inertiajs/vue3';
import { ref, computed, watch, watchEffect } from 'vue';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import Label from '@/components/ui/label/Label.vue';
import Input from '@/components/ui/input/Input.vue';
import Progress from '@/components/ui/progress/Progress.vue';
import Button from '@/components/ui/button/Button.vue';
import Separator from '@/components/ui/separator/Separator.vue';
import Stepper from '@/components/ui/stepper/Stepper.vue';


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

const importing = ref(false);

// async function importData() {
//   router.post('/import-data')
// }

// Stepper state
const steps = ref([
  { id: 1, title: 'Patients Data', description: 'Upload and validate patients file', status: 'pending' },
  { id: 2, title: 'Visits Data', description: 'Upload and validate visits file', status: 'pending' },
  { id: 3, title: 'Cross Validation', description: 'Validate consistency between files', status: 'pending' },
  { id: 4, title: 'Import', description: 'Import validated data to database', status: 'pending' }
]);

// Update stepper status based on progress
watchEffect(() => {
  steps.value[0].status = validationResults.value.patients ?
    (patientValidationStatus.value === 'error' ? 'error' : 'completed') :
    (patientsFile.value ? 'in-progress' : 'pending');

  steps.value[1].status = validationResults.value.visits ?
    (visitsValidationStatus.value === 'error' ? 'error' : 'completed') :
    (visitsFile.value ? 'in-progress' : 'pending');

  steps.value[2].status = validationResults.value.crossValidation ?
    (crossValidationStatus.value === 'error' ? 'error' : 'completed') :
    (validationResults.value.patients && validationResults.value.visits ? 'in-progress' : 'pending');

  steps.value[3].status = importing.value ? 'in-progress' : 'pending';
});

// Reset next steps when files change
watch(patientsFile, () => {
  if (!patientsFile.value) {
    validationResults.value.patients = null;
    visitsFile.value = null;
    validationResults.value.visits = null;
    validationResults.value.crossValidation = null;
  }
});

watch(visitsFile, () => {
  if (!visitsFile.value) {
    validationResults.value.visits = null;
    validationResults.value.crossValidation = null;
  }
});

// new 
// Import progress tracking
const page = usePage();
const importStatus = ref<'idle' | 'importing' | 'success' | 'error'>('idle');
const importStats = ref<any>(null);
const importError = ref<string | null>(null);

// Progress polling
const importModalOpen = ref(false);
const importProgress = ref();
const importPolling = ref();
const importComplete = ref(false);



// Start polling when import begins
watch(importStatus, (newStatus) => {
  if (newStatus === 'importing') {
    usePoll(2000, {
          only: ['importProgress'],
         // preserveState: true,
          //preserveScroll: true,
    });
  }
});

// Add progress polling function
const startImportProgressPolling = () => {

  importPolling.value = setInterval(async () => {
    try {
       router.get('/import/progress',{},{
        onSuccess: (response) => {
          importProgress.value = response.props.stats || {};
        }
      });
      
      
      // Check if import is complete
      if (importProgress.value.patients.processed >= importProgress.value.patients.total &&
          importProgress.value.visits.processed >= importProgress.value.visits.total) {
        clearInterval(importPolling.value);
        importComplete.value = true;
      }
    } catch (e) {
      console.error('Error fetching progress', e);
    }
  }, 2000);
}


async function importData() {
  importing.value = true;
  importStatus.value = 'importing';
  importError.value = null;
  importStats.value = null;
  
  router.post('/import-data', {}, {
    onSuccess: (response) => {
      importStatus.value = 'success';
      importStats.value = response.props.stats || {};
      
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


</script>

<template>

  <Head title="Data Upload" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 overflow-x-auto">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-2">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Medical Data Validation</h1>
          <p class="text-gray-600 mt-1">Follow these steps to upload and validate your medical data files</p>
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

      <!-- Stepper -->
      <Stepper :steps="steps" class="mb-8" />

      <!-- File Upload Section -->

      <!-- Step 1: Patients Data -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
            <span class="text-blue-800 font-bold">1</span>
          </div>
          <h2 class="text-xl font-bold text-gray-800">Upload Patients Data</h2>
        </div>

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
                <Label for="patients-file-input"
                  class="bg-blue-600 text-white px-4 py-2 rounded-md cursor-pointer hover:bg-blue-700 transition">
                  Browse Files
                </Label>
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
              <Button @click="patientsFile = null" class="text-gray-500 hover:text-red-500 transition">
                <i class="fas fa-times"></i>
              </Button>
            </div>

            <!-- Upload Button -->
            <Button
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
            </Button>
          </Card>

          <!-- Patients Validation Results -->
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
        </div>
      </div>


      <!-- Step 2: Visits Data (appears after patients validation) -->
      <div v-if="validationResults.patients" class="mb-8">
        <Separator class="my-8" />

        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
            <span class="text-green-800 font-bold">2</span>
          </div>
          <h2 class="text-xl font-bold text-gray-800">Upload Visits Data</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
              <Input type="file" accept=".xlsx,.xls" @change="(e: any) => onFileChange(e, 'visits')" class="hidden"
                id="visits-file-input" />
              <div class="flex flex-col items-center justify-center gap-3">
                <i class="fas fa-cloud-upload-alt text-3xl text-green-500"></i>
                <p class="text-gray-600">Drag & drop your file here</p>
                <p class="text-sm text-gray-500 mb-3">or</p>
                <Label for="visits-file-input"
                  class="bg-green-600 text-white px-4 py-2 rounded-md cursor-pointer hover:bg-green-700 transition">
                  Browse Files
                </Label>
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
              <Button @click="visitsFile = null" class="text-gray-500 hover:text-red-500 transition">
                <i class="fas fa-times"></i>
              </Button>
            </div>

            <!-- Upload Button -->
            <Button
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
            </Button>
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
        </div>
      </div>

           <!-- Step 3: Cross Validation (appears after both files validated) -->
      <div v-if="validationResults.patients && validationResults.visits" class="mb-8">
        <Separator class="my-8" />
        
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
            <span class="text-purple-800 font-bold">3</span>
          </div>
          <h2 class="text-xl font-bold text-gray-800">Cross-File Validation</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card class="p-6 border border-purple-100 bg-gradient-to-br from-purple-50 to-white">
            <div class="mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Data Consistency Check</h3>
              <p class="text-gray-600 mt-1">Validate patient IDs match between files to ensure data integrity</p>
            </div>
            
            <div class="flex justify-between items-center">
              <div>
                <p class="text-sm text-gray-600">Status</p>
                <p v-if="validating" class="font-medium text-purple-600">Validating...</p>
                <p v-else-if="validationResults.crossValidation" class="font-medium">
                  {{ crossValidationStatus === 'success' ? 'Completed' : 'Ready to validate' }}
                </p>
                <p v-else class="font-medium">Ready</p>
              </div>
              
              <Button
                class="bg-purple-600 text-white px-4 py-2 rounded-md shadow hover:bg-purple-700 transition flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="!patientsFile || !visitsFile || validating" @click="validateBothFiles">
                <i class="fas fa-link mr-2"></i>
                Validate Patient Numbers
              </Button>
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
                  <i v-if="crossValidationStatus === 'warning'" class="fas fa-exclamation-triangle text-yellow-500"></i>
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
      </div>
    </div>


    <!-- Final Action Buttons -->
    <div v-if="validationResults.crossValidation" class="mt-6">
    <Separator class="my-8" />
    
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
        <span class="text-indigo-800 font-bold">4</span>
      </div>
      <h2 class="text-xl font-bold text-gray-800">Import Data to Database</h2>
    </div>

    <Card class="p-6 bg-gradient-to-br from-indigo-50 to-white">
      <div class="flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="flex-1">
          <h3 class="text-lg font-semibold text-gray-800">Ready to Import</h3>
          <p class="text-gray-600 mt-1">
            All validations completed successfully. You can now import the data into the database.
          </p>
          
          <!-- Progress Display -->
          <div v-if="importStatus === 'importing'" class="mt-4 space-y-4">
            <div>
              <p class="text-sm text-gray-600 mb-1">
                Patients: {{ importProgress.patients.processed }} / {{ importProgress.patients.total }}
              </p>
              <Progress :value="(importProgress.patients.processed / importProgress.patients.total) * 100" />
            </div>
            <div>
              <p class="text-sm text-gray-600 mb-1">
                Visits: {{ importProgress.visits.processed }} / {{ importProgress.visits.total }}
              </p>
              <Progress :value="(importProgress.visits.processed / importProgress.visits.total) * 100" />
            </div>
          </div>
          
          <!-- Success Message -->
          <div v-if="importStatus === 'success'" class="mt-4 bg-green-50 text-green-700 p-4 rounded-lg">
            <div class="flex items-center gap-2">
              <i class="fas fa-check-circle text-green-500"></i>
              <p class="font-medium">Import completed successfully!</p>
            </div>
            
            <div v-if="importStats" class="mt-3 grid grid-cols-2 gap-3">
              <div class="bg-white p-3 rounded shadow-sm">
                <p class="text-sm text-gray-500">Patients Imported</p>
                <p class="text-xl font-bold">{{ importStats.patients }}</p>
              </div>
              <div class="bg-white p-3 rounded shadow-sm">
                <p class="text-sm text-gray-500">Visits Imported</p>
                <p class="text-xl font-bold">{{ importStats.visits }}</p>
              </div>
            </div>
          </div>
          
          <!-- Error Message -->
          <div v-if="importStatus === 'error'" class="mt-4 bg-red-50 text-red-700 p-4 rounded-lg">
            <div class="flex items-center gap-2">
              <i class="fas fa-exclamation-circle text-red-500"></i>
              <p class="font-medium">Import failed</p>
            </div>
            <p v-if="importError" class="mt-2 text-sm">{{ importError }}</p>
          </div>
        </div>
        
        <div class="flex flex-col gap-3 min-w-[200px]">
          <Button class="bg-gray-600 text-white px-6 py-3 rounded-md shadow hover:bg-gray-700 transition"
            @click="patientsFile = null; visitsFile = null; validationResults = {}; importStatus = 'idle';">
            <i class="fas fa-redo mr-2"></i> Re-upload Files
          </Button>

          <Button
            class="bg-indigo-600 text-white px-6 py-3 rounded-md shadow hover:bg-indigo-700 transition flex items-center disabled:opacity-50"
            :disabled="importing || importStatus === 'success'" 
            @click="importData">
            <i v-if="importing" class="fas fa-spinner fa-spin mr-2"></i>
            <i v-else class="fas fa-database mr-2"></i>
            {{ importing ? 'Importing...' : 'Import to Database' }}
          </Button>
        </div>
      </div>
    </Card>
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