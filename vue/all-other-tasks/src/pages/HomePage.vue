<script setup lang="ts">
import { computed, onMounted } from "vue";
import { RouterLink, useRouter } from "vue-router";
import ResumeBoard from "@/components/ResumeBoard.vue";
import { useResumeStore } from "@/stores/resumes";
import { STATUS_OPTIONS } from "@/constants/resume";

const resumeStore = useResumeStore();
const router = useRouter();

onMounted(() => {
  resumeStore.fetchAll();
});

const statusCounts = computed(() =>
  STATUS_OPTIONS.map(({ value, label }) => ({
    value,
    label,
    count: resumeStore.items.filter((item) => item.status === value).length,
  }))
);

const handleStatusChange = async ({
  id,
  status,
}: {
  id: string;
  status: typeof STATUS_OPTIONS[number]["value"];
}) => {
  try {
    await resumeStore.updateResumeStatus(id, status);
  } catch {
    // errors handled in store
  }
};

const handleOpenResume = (id: string) => {
  router.push(`/edit/${id}`);
};
</script>

<template>
  <div class="home-page">
    <div class="home-bar">
      <RouterLink class="primary-btn" to="/add">Новое резюме</RouterLink>
      <button class="ghost-btn" type="button" @click="resumeStore.fetchAll">Обновить</button>
    </div>

    <p v-if="resumeStore.error" class="error-banner">{{ resumeStore.error }}</p>

    <div class="counters">
      <span v-for="status in statusCounts" :key="status.value">
        {{ status.label }} ({{ status.count }})
      </span>
    </div>

    <ResumeBoard
      :items="resumeStore.items"
      :loading="resumeStore.loading"
      @status-change="handleStatusChange"
      @open="handleOpenResume"
    />
  </div>
</template>

<style scoped>
.home-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.home-bar {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: space-between;
}

.primary-btn {
  background: #2563eb;
  color: #fff;
  text-decoration: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  border: none;
}

.ghost-btn {
  border: 1px solid #94a3b8;
  background: transparent;
  color: #0f172a;
  border-radius: 10px;
  padding: 0.65rem 1.25rem;
  font-weight: 600;
  cursor: pointer;
}

.error-banner {
  background: #fee2e2;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 10px;
}

.counters {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  color: #475467;
  font-weight: 600;
}
</style>
