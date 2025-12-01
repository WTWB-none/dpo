<script setup lang="ts">
import { computed } from "vue";
import { STATUS_LABEL_MAP } from "../constants/resume";
import type { ResumeFormState } from "../types/resume";

const props = defineProps<{
  data: ResumeFormState | null;
}>();

const formatBirthdate = (value: string) => {
  const match = value.trim().match(/^(\d{1,2})[.\-/](\d{1,2})[.\-/](\d{4})$/);
  if (!match) return "";
  const [, day = "", month = "", year = ""] = match;
  const date = new Date(Number(year), Number(month) - 1, Number(day));
  if (
    Number.isNaN(date.getTime()) ||
    date.getDate() !== Number(day) ||
    date.getMonth() + 1 !== Number(month) ||
    date.getFullYear() !== Number(year)
  ) {
    return "";
  }
  return `${day.padStart(2, "0")}.${month.padStart(2, "0")}.${year}`;
};

const asText = (value: unknown) => {
  if (typeof value === "string") return value;
  if (value == null) return "";
  return String(value);
};

const formatPhone = (digits: string) => {
  if (digits.length !== 11) return digits;
  const normalized = digits.startsWith("8") ? `7${digits.slice(1)}` : digits;
  const parts = {
    country: normalized[0],
    city: normalized.slice(1, 4),
    block1: normalized.slice(4, 7),
    block2: normalized.slice(7, 9),
    block3: normalized.slice(9, 11),
  };
  return `+${parts.country} (${parts.city}) ${parts.block1}-${parts.block2}-${parts.block3}`;
};

const preview = computed(() => {
  const source = props.data;
  if (!source) {
    return {
      profession: "—",
      city: "—",
      photoUrl: "",
      fullName: "—",
      phone: "—",
      email: "—",
      birthdate: "—",
      salary: "—",
      skillsList: [] as string[],
      about: "—",
      status: "—",
      educationEntries: [] as Array<{
        level: string;
        extras: Array<{ label: string; value: string }>;
      }>,
    };
  }

  const matchableSkills = asText(source.skills);
  const skillItems = matchableSkills
    .split(/[\n,;]+/)
    .map((skill) => skill.trim())
    .filter(Boolean);

  return {
    profession: asText(source.profession).trim() || "—",
    city: asText(source.city).trim() || "—",
    photoUrl: asText(source.photoUrl).trim(),
    fullName: asText(source.fullName).trim() || "—",
    phone: formatPhone(asText(source.phone)) || "—",
    email: asText(source.email).trim() || "—",
    birthdate: formatBirthdate(asText(source.birthdate)) || "—",
    salary: asText(source.salary).trim()
      ? `${asText(source.salary).trim()} ₽`
      : "—",
    about: asText(source.about).trim() || "—",
    status: STATUS_LABEL_MAP[source.status] ?? "—",
    skillsList: skillItems,
    educationEntries: source.educationEntries.map((entry) => ({
      level: entry.level || "—",
      extras:
        entry.level === "Среднее"
          ? []
          : [
              { label: "Учебное заведение", value: asText(entry.institution).trim() },
              { label: "Факультет", value: asText(entry.faculty).trim() },
              { label: "Специализация", value: asText(entry.specialization).trim() },
              { label: "Год окончания", value: asText(entry.graduationYear).trim() },
            ].filter((item) => item.value),
    })),
  };
});
</script>

<template>
  <section class="resume-preview">
    <h2>Готовое резюме</h2>
    <article class="resume-card">
      <header class="resume-header">
        <div class="texts">
          <p class="profession">{{ preview.profession }}</p>
          <p class="name">{{ preview.fullName }}</p>
          <p class="city">{{ preview.city }}</p>
          <p class="status">Статус: {{ preview.status }}</p>
        </div>
        <div class="photo" aria-hidden="true">
          <img v-if="preview.photoUrl" :src="preview.photoUrl" alt="Фото кандидата" />
          <div v-else class="photo-placeholder">Фото</div>
        </div>
      </header>

      <section class="resume-block">
        <h3>Контакты</h3>
        <p><strong>Телефон:</strong> {{ preview.phone }}</p>
        <p><strong>Email:</strong> {{ preview.email }}</p>
        <p><strong>Дата рождения:</strong> {{ preview.birthdate }}</p>
      </section>

      <section class="resume-block">
        <h3>Образование</h3>
        <div
          v-if="preview.educationEntries.length"
          class="education-preview-list"
        >
          <article v-for="(entry, index) in preview.educationEntries" :key="index" class="education-item">
            <p class="education-level">{{ entry.level }}</p>
            <div v-if="entry.extras.length" class="education-extras">
              <p v-for="extra in entry.extras" :key="extra.label">
                <strong>{{ extra.label }}:</strong> {{ extra.value }}
              </p>
            </div>
          </article>
        </div>
        <p v-else>—</p>
      </section>

      <section class="resume-block">
        <h3>Желаемая зарплата</h3>
        <p>{{ preview.salary }}</p>
      </section>

      <section class="resume-block">
        <h3>Ключевые навыки</h3>
        <ul v-if="preview.skillsList.length" class="skills">
          <li v-for="skill in preview.skillsList" :key="skill">{{ skill }}</li>
        </ul>
        <p v-else>—</p>
      </section>

      <section class="resume-block">
        <h3>О себе</h3>
        <p>{{ preview.about }}</p>
      </section>
    </article>
  </section>
</template>

<style scoped>
.resume-preview h2 {
  margin: 0 0 1rem;
}

.resume-card {
  border: 1px solid #e4e7ec;
  border-radius: 16px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.resume-header {
  display: flex;
  gap: 1rem;
  align-items: center;
  border-bottom: 1px solid #e4e7ec;
  padding-bottom: 1rem;
}

.texts {
  flex: 1;
}

.profession {
  font-size: 1.15rem;
  font-weight: 600;
  color: #2563eb;
  margin: 0 0 0.2rem;
}

.name {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
}

.city,
.status {
  margin: 0.15rem 0 0;
  color: #475467;
}

.photo {
  width: 120px;
  height: 120px;
  border-radius: 12px;
  overflow: hidden;
  background: #f2f4f7;
  display: flex;
  align-items: center;
  justify-content: center;
}

.photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-placeholder {
  color: #94a3b8;
  font-weight: 600;
}

.resume-block {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.resume-block h3 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
}

.resume-block p {
  margin: 0;
  color: #475467;
}

.education-preview-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.education-item {
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 0.75rem;
}

.education-level {
  font-weight: 600;
  color: #1d4ed8;
  margin: 0 0 0.35rem;
}

.education-extras {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.skills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  list-style: none;
  padding: 0;
  margin: 0;
}

.skills li {
  background: #e0e7ff;
  color: #1d4ed8;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  font-size: 0.85rem;
}

@media (max-width: 640px) {
  .resume-header {
    flex-direction: column;
    text-align: center;
  }

  .photo {
    width: 100px;
    height: 100px;
  }
}
</style>
