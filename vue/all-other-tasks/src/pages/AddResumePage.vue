<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import ResumeForm from "@/components/ResumeForm.vue";
import ResumePreview from "@/components/ResumePreview.vue";
import type { ResumeFormState } from "@/types/resume";
import { useResumeStore } from "@/stores/resumes";

const router = useRouter();
const resumeStore = useResumeStore();
const previewData = ref<ResumeFormState | null>(null);
const saving = ref(false);
const error = ref<string | null>(null);

const handleApply = async (payload: ResumeFormState) => {
  saving.value = true;
  error.value = null;
  try {
    await resumeStore.addResume(payload);
    router.push("/");
  } catch (err) {
    error.value = err instanceof Error ? err.message : "Не удалось сохранить резюме";
  } finally {
    saving.value = false;
  }
};

const handlePreview = (payload: ResumeFormState) => {
  previewData.value = payload;
};
</script>

<template>
  <div class="form-page">
    <div class="panel">
      <ResumeForm
        title="Новое резюме"
        :submit-label="saving ? 'Сохраняем...' : 'Сохранить'"
        :initial-value="null"
        @apply="handleApply"
        @preview="handlePreview"
      />
      <p v-if="error" class="error-text">{{ error }}</p>
    </div>
    <div class="panel">
      <ResumePreview :data="previewData" />
    </div>
  </div>
</template>

<style scoped>
.form-page {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.5rem;
}

.panel {
  background: #fff;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
}

.error-text {
  margin-top: 1rem;
  color: #dc2626;
  font-weight: 600;
}
</style>
