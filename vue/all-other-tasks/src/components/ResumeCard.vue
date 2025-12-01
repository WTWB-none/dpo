<script setup lang="ts">
import { computed } from "vue";
import type { ResumeRecord } from "@/types/resume";
import { STATUS_LABEL_MAP } from "@/constants/resume";

const props = defineProps<{
  resume: ResumeRecord;
}>();

const emit = defineEmits<{
  open: [];
}>();

const age = computed(() => {
  const birthdate = props.resume.birthdate;
  if (!birthdate) return null;
  const date = new Date(birthdate);
  if (Number.isNaN(date.getTime())) return null;
  const diff = Date.now() - date.getTime();
  const ageDate = new Date(diff);
  return Math.abs(ageDate.getUTCFullYear() - 1970);
});
</script>

<template>
  <article class="resume-card" @click="emit('open')">
    <div class="photo">
      <img v-if="resume.photoUrl" :src="resume.photoUrl" alt="Фото" />
      <span v-else>{{ resume.fullName.charAt(0) || "?" }}</span>
    </div>
    <div class="details">
      <p class="profession">{{ resume.profession || "Без должности" }}</p>
      <p class="name">{{ resume.fullName || "Без имени" }}</p>
      <p class="meta">
        <span>{{ STATUS_LABEL_MAP[resume.status] ?? "—" }}</span>
        <span v-if="age !== null">{{ age }} лет</span>
      </p>
    </div>
  </article>
</template>

<style scoped>
.resume-card {
  display: flex;
  gap: 0.75rem;
  padding: 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.15s;
}

.resume-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.1);
}

.photo {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  background: #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #334155;
  flex-shrink: 0;
}

.photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.profession {
  margin: 0;
  font-weight: 600;
  color: #0f172a;
}

.name {
  margin: 0;
  color: #475467;
}

.meta {
  margin: 0;
  color: #94a3b8;
  font-size: 0.85rem;
  display: flex;
  gap: 0.5rem;
}
</style>
