<script setup lang="ts">
import { computed, watch } from "vue";
import { EDUCATION_LEVELS } from "../constants/resume";
import type { EducationEntryForm } from "../types/resume";

const entry = defineModel<EducationEntryForm>("entry", { required: true });

const props = defineProps<{
  canRemove: boolean;
  index: number;
}>();

const emit = defineEmits<{
  remove: [];
}>();

const showExtras = computed(() => entry.value.level !== "Среднее");

watch(
  () => entry.value.level,
  (level) => {
    if (level === "Среднее") {
      entry.value.institution = "";
      entry.value.faculty = "";
      entry.value.specialization = "";
      entry.value.graduationYear = "";
    }
  }
);
</script>

<template>
  <article class="education-entry">
    <header class="entry-header">
      <h4>Образование {{ index + 1 }}</h4>
      <button
        v-if="canRemove"
        type="button"
        class="remove-entry"
        aria-label="Удалить образование"
        @click="emit('remove')"
      >
        ✕
      </button>
    </header>

    <label class="field">
      <span>Уровень</span>
      <select v-model="entry.level">
        <option v-for="level in EDUCATION_LEVELS" :key="level" :value="level">{{ level }}</option>
      </select>
    </label>

    <div v-if="showExtras" class="extra-fields">
      <label class="field">
        <span>Учебное заведение</span>
        <input v-model="entry.institution" type="text" placeholder="МГУ" />
      </label>

      <label class="field">
        <span>Факультет</span>
        <input v-model="entry.faculty" type="text" placeholder="Факультет дизайна" />
      </label>

      <label class="field">
        <span>Специализация</span>
        <input v-model="entry.specialization" type="text" placeholder="Графический дизайн" />
      </label>

      <label class="field">
        <span>Год окончания</span>
        <input v-model="entry.graduationYear" type="number" min="1900" max="2100" placeholder="2022" />
      </label>

    </div>
  </article>
</template>

<style scoped>
.education-entry {
  border: 1px solid #e4e7ec;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  position: relative;
}

.entry-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.entry-header h4 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
}

.remove-entry {
  border: none;
  background: transparent;
  color: #dc2626;
  font-size: 1.1rem;
  cursor: pointer;
}

.extra-fields {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

</style>
