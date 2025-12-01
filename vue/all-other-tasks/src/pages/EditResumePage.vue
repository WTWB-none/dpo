<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import ResumeForm from "@/components/ResumeForm.vue";
import ResumePreview from "@/components/ResumePreview.vue";
import type { ResumeFormState, ResumeRecord } from "@/types/resume";
import { useResumeStore } from "@/stores/resumes";

const route = useRoute();
const router = useRouter();
const resumeStore = useResumeStore();

const resume = ref<ResumeRecord | null>(null);
const previewData = ref<ResumeFormState | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const resumeId = computed(() => route.params.id as string);

const loadResume = async () => {
  loading.value = true;
  error.value = null;
  try {
    const data = await resumeStore.fetchById(resumeId.value);
    resume.value = data;
    previewData.value = data;
  } catch (err) {
    error.value = err instanceof Error ? err.message : "Резюме не найдено";
  } finally {
    loading.value = false;
  }
};

onMounted(loadResume);

watch(
  () => resumeId.value,
  () => {
    loadResume();
  }
);

const handleApply = async (payload: ResumeFormState) => {
  if (!resume.value) return;
  error.value = null;
  try {
    await resumeStore.updateResume(resume.value.id, payload);
    router.push("/");
  } catch (err) {
    error.value = err instanceof Error ? err.message : "Не удалось обновить резюме";
  }
};

const handlePreview = (payload: ResumeFormState) => {
  previewData.value = payload;
};
</script>

<template>
  <div class="form-page">
    <div class="panel">
      <p v-if="loading">Загружаем резюме...</p>
      <p v-else-if="error" class="error-text">
        {{ error }}
      </p>
      <ResumeForm
        v-else
        title="Редактирование резюме"
        submit-label="Сохранить изменения"
        :initial-value="resume"
        @apply="handleApply"
        @preview="handlePreview"
      />
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
  color: #dc2626;
  font-weight: 600;
}
</style>
