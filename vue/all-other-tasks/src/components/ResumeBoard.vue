<script setup lang="ts">
import { computed, ref } from "vue";
import { STATUS_OPTIONS } from "@/constants/resume";
import type { ResumeRecord, StatusOption } from "@/types/resume";
import ResumeCard from "./ResumeCard.vue";

const props = defineProps<{
  items: ResumeRecord[];
  loading?: boolean;
}>();

const emit = defineEmits<{
  statusChange: [{ id: string; status: StatusOption }];
  open: [string];
}>();

const draggingId = ref<string | null>(null);

const columns = computed(() =>
  STATUS_OPTIONS.map(({ value, label }) => ({
    value: value as StatusOption,
    label,
    items: props.items.filter((item) => item.status === value),
  }))
);

const startDrag = (id: string) => {
  draggingId.value = id;
};

const handleDrop = (status: StatusOption) => {
  if (!draggingId.value) return;
  emit("statusChange", { id: draggingId.value, status });
  draggingId.value = null;
};

const openResume = (id: string) => {
  emit("open", id);
};
</script>

<template>
  <div class="board">
    <p v-if="loading" class="loading">Загружаем...</p>
    <div v-else class="columns">
      <section
        v-for="column in columns"
        :key="column.value"
        class="column"
        :class="{ 'drop-ready': draggingId }"
        @dragover.prevent
        @drop="handleDrop(column.value)"
      >
        <header>
          <h3>{{ column.label }}</h3>
          <span class="badge">{{ column.items.length }}</span>
        </header>
        <div class="list">
          <template v-if="column.items.length">
            <div
              v-for="resume in column.items"
              :key="resume.id"
              class="card-wrapper"
              draggable="true"
              @dragstart="startDrag(resume.id)"
              @dragend="draggingId = null"
            >
              <ResumeCard :resume="resume" @open="openResume(resume.id)" />
            </div>
          </template>
          <p v-else class="empty">Нет резюме</p>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.board {
  width: 100%;
}

.columns {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
}

.column {
  background: #f8fafc;
  border-radius: 14px;
  padding: 1rem;
  min-height: 300px;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  border: 2px dashed transparent;
}

.column.drop-ready {
  border-color: #bfdbfe;
}

.column header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.column h3 {
  margin: 0;
  font-size: 1rem;
}

.badge {
  background: #e0e7ff;
  color: #1d4ed8;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  font-size: 0.85rem;
}

.list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  min-height: 200px;
}

.card-wrapper {
  cursor: grab;
}

.empty {
  color: #94a3b8;
  font-size: 0.9rem;
}

.loading {
  color: #475467;
  font-weight: 600;
}
</style>
