<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { EDUCATION_LEVELS, STATUS_OPTIONS } from "../constants/resume";
import type { EducationEntryForm, ResumeFormState } from "../types/resume";
import EducationEntry from "./EducationEntry.vue";

const props = defineProps<{
  title?: string;
  submitLabel?: string;
  initialValue?: ResumeFormState | null;
}>();

const emit = defineEmits<{
  apply: [ResumeFormState];
  preview: [ResumeFormState];
}>();

const createEducationEntry = (): EducationEntryForm => ({
  id: Date.now() + Math.random(),
  level: EDUCATION_LEVELS[0],
  institution: "",
  faculty: "",
  specialization: "",
  graduationYear: "",
});

const createEmptyForm = (): ResumeFormState => ({
  profession: "",
  city: "",
  photoUrl: "",
  fullName: "",
  phone: "",
  email: "",
  birthdate: "",
  salary: "",
  skills: "",
  about: "",
  status: STATUS_OPTIONS[0].value,
  educationEntries: [createEducationEntry()],
});

const form = reactive<ResumeFormState>(createEmptyForm());
const phoneTouched = ref(false);

const snapshot = () => ({
  ...form,
  educationEntries: form.educationEntries.map((entry) => ({ ...entry })),
});

const emitPreview = () => {
  emit("preview", snapshot());
};

const hydrateForm = (value?: ResumeFormState | null) => {
  const base = { ...createEmptyForm(), ...(value ?? {}) };
  Object.assign(form, base);
  form.educationEntries = base.educationEntries.length
    ? base.educationEntries.map((entry) => ({ ...entry }))
    : [createEducationEntry()];
  emitPreview();
};

watch(
  () => props.initialValue,
  (value) => {
    hydrateForm(value ?? undefined);
    phoneTouched.value = false;
  },
  { immediate: true }
);

watch(
  form,
  () => {
    emitPreview();
  },
  { deep: true }
);

const sanitizePhone = (event: Event) => {
  const input = event.target as HTMLInputElement | null;
  if (!input) return;
  const cleaned = input.value.replace(/\D/g, "").slice(0, 11);
  input.value = cleaned;
  form.phone = cleaned;
  phoneTouched.value = true;
};

const markPhoneTouched = () => {
  phoneTouched.value = true;
};

const phoneHasElevenDigits = computed(() => form.phone.length === 11);
const phoneStartsWithValidDigit = computed(
  () => form.phone.startsWith("7") || form.phone.startsWith("8")
);
const isPhoneValid = computed(
  () => !!form.phone && phoneHasElevenDigits.value && phoneStartsWithValidDigit.value
);

const phoneError = computed(() => {
  if (!phoneTouched.value) return "";
  if (!form.phone) return "Введите номер телефона";
  if (!phoneHasElevenDigits.value) return "Номер должен содержать 11 цифр";
  if (!phoneStartsWithValidDigit.value) return "Номер должен начинаться с 7 или 8";
  return "";
});

const addEducationEntry = () => {
  form.educationEntries.push(createEducationEntry());
};

const removeEducationEntry = (id: number) => {
  if (form.educationEntries.length === 1) return;
  const index = form.educationEntries.findIndex((entry) => entry.id === id);
  if (index !== -1) {
    form.educationEntries.splice(index, 1);
  }
};

const applyForm = () => {
  phoneTouched.value = true;
  if (!isPhoneValid.value) return;

  const payload: ResumeFormState = {
    ...form,
    educationEntries: form.educationEntries.map((entry) => ({ ...entry })),
  };

  emit("apply", payload);
};
</script>

<template>
  <section>
    <h1>{{ title ?? "Конструктор резюме" }}</h1>
    <form class="resume-form" @submit.prevent="applyForm">
      <label class="field">
        <span>Статус</span>
        <select v-model="form.status">
          <option v-for="status in STATUS_OPTIONS" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </label>

      <label class="field">
        <span>Профессия</span>
        <input v-model="form.profession" type="text" placeholder="Product designer" />
      </label>

      <label class="field">
        <span>Город</span>
        <input v-model="form.city" type="text" placeholder="Москва" />
      </label>

      <label class="field">
        <span>Ссылка на фото</span>
        <input v-model="form.photoUrl" type="url" placeholder="https://example.com/me.jpg" />
      </label>

      <label class="field">
        <span>ФИО</span>
        <input v-model="form.fullName" type="text" placeholder="Иванов Иван Иванович" />
      </label>

      <label class="field">
        <span>Телефон</span>
        <input
          v-model="form.phone"
          type="text"
          placeholder="79991234567"
          inputmode="numeric"
          pattern="\d*"
          @input="sanitizePhone"
          @blur="markPhoneTouched"
          :class="{ invalid: phoneError }"
        />
        <span v-if="phoneError" class="error">{{ phoneError }}</span>
      </label>

      <label class="field">
        <span>Email</span>
        <input v-model="form.email" type="email" placeholder="me@example.com" />
      </label>

      <label class="field">
        <span>Дата рождения (дд.мм.гггг)</span>
        <input v-model="form.birthdate" type="text" placeholder="12.04.1995" inputmode="numeric" />
      </label>

      <div class="education-section">
        <h3>Образование</h3>
        <p class="section-hint">
          Добавьте одно или несколько мест обучения. Для уровней выше среднего откроются
          дополнительные поля.
        </p>

        <EducationEntry
          v-for="(entry, index) in form.educationEntries"
          :key="entry.id"
          v-model:entry="form.educationEntries[index]!"
          :index="index"
          :can-remove="form.educationEntries.length > 1"
          @remove="removeEducationEntry(entry.id)"
        />

        <button type="button" class="link-button" @click="addEducationEntry">
          Указать ещё одно место обучения
        </button>
      </div>

      <label class="field">
        <span>Желаемая зарплата</span>
        <input v-model="form.salary" type="number" min="0" step="1000" placeholder="150000" />
      </label>

      <label class="field">
        <span>Ключевые навыки</span>
        <textarea
          v-model="form.skills"
          rows="2"
          placeholder="Figma, UX Research, UI Kit"
        ></textarea>
      </label>

      <label class="field">
        <span>О себе</span>
        <textarea v-model="form.about" rows="3" placeholder="Коротко расскажите о себе"></textarea>
      </label>

      <div class="form-actions">
        <button type="submit">{{ submitLabel ?? "Применить" }}</button>
      </div>
    </form>
  </section>
</template>

<style scoped>
section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.resume-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.95rem;
  color: #475467;
}

.field input,
.field textarea,
.field select {
  border: 1px solid #d0d5dd;
  border-radius: 10px;
  padding: 0.65rem 0.9rem;
  font-size: 0.95rem;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: #fdfdfd;
}

.field textarea {
  min-height: 70px;
  resize: vertical;
}

.field select {
  appearance: none;
  background-image: linear-gradient(45deg, transparent 50%, #94a3b8 50%),
    linear-gradient(135deg, #94a3b8 50%, transparent 50%);
  background-position: calc(100% - 20px) calc(50% - 3px), calc(100% - 14px) calc(50% - 3px);
  background-size: 6px 6px, 6px 6px;
  background-repeat: no-repeat;
}

.field input:focus,
.field textarea:focus,
.field select:focus {
  border-color: #2563eb;
  outline: none;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.field input.invalid {
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
}

.field .error {
  font-size: 0.85rem;
  color: #dc2626;
}

.education-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.education-section h3 {
  margin: 0;
}

.section-hint {
  margin: 0;
  font-size: 0.85rem;
  color: #94a3b8;
}

.link-button {
  align-self: flex-start;
  background: none;
  border: none;
  color: #2563eb;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}

.link-button:hover {
  text-decoration: underline;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.form-actions button {
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
}

.form-actions button:hover {
  background: #1e3fa9;
}

.form-actions button:active {
  transform: translateY(1px);
}
</style>
