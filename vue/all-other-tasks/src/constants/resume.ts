export const STATUS_OPTIONS = [
  { value: "new", label: "Новый" },
  { value: "interview", label: "Назначено собеседование" },
  { value: "hired", label: "Принят" },
  { value: "rejected", label: "Отказ" },
] as const;

export const STATUS_LABEL_MAP = STATUS_OPTIONS.reduce<Record<string, string>>((acc, option) => {
  acc[option.value] = option.label;
  return acc;
}, {});

export const EDUCATION_LEVELS = [
  "Среднее",
  "Среднее специальное",
  "Неоконченное высшее",
  "Высшее",
] as const;

export type EducationLevel = (typeof EDUCATION_LEVELS)[number];
export type StatusOption = (typeof STATUS_OPTIONS)[number]["value"];
