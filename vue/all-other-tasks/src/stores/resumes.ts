import { defineStore } from "pinia";
import * as api from "@/api/resumes";
import type { ResumeFormState, ResumeRecord, StatusOption } from "@/types/resume";

const STORAGE_KEY = "resume-db-cache";

const loadFromStorage = (): ResumeRecord[] => {
  if (typeof window === "undefined") return [];
  const raw = window.localStorage.getItem(STORAGE_KEY);
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed)) {
      return parsed;
    }
    if (Array.isArray(parsed.items)) {
      return parsed.items;
    }
    return [];
  } catch {
    return [];
  }
};

const persistToStorage = (items: ResumeRecord[]) => {
  if (typeof window === "undefined") return;
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
};

export const useResumeStore = defineStore("resumes", {
  state: () => ({
    items: [] as ResumeRecord[],
    loading: false,
    error: null as string | null,
    initialized: false,
  }),
  getters: {
    findById: (state) => {
      return (id: string) => state.items.find((item) => item.id === id) ?? null;
    },
  },
  actions: {
    hydrate() {
      if (this.initialized) return;
      const stored = loadFromStorage();
      if (stored.length) {
        this.items = stored;
      }
      this.initialized = true;
    },
    setError(message: string | null) {
      this.error = message;
    },
    upsert(record: ResumeRecord) {
      const index = this.items.findIndex((item) => item.id === record.id);
      if (index === -1) {
        this.items.push(record);
      } else {
        this.items[index] = record;
      }
      persistToStorage(this.items);
    },
    async fetchAll() {
      this.hydrate();
      this.loading = true;
      this.setError(null);
      try {
        const data = await api.fetchResumes();
        this.items = data;
        persistToStorage(this.items);
      } catch (error) {
        this.setError(error instanceof Error ? error.message : "Не удалось загрузить резюме");
      } finally {
        this.loading = false;
      }
    },
    async fetchById(id: string) {
      const existing = this.findById(id);
      if (existing) return existing;
      this.setError(null);
      try {
        const record = await api.fetchResume(id);
        this.upsert(record);
        return record;
      } catch (error) {
        this.setError(error instanceof Error ? error.message : "Резюме не найдено");
        throw error;
      }
    },
    async addResume(payload: ResumeFormState) {
      this.setError(null);
      try {
        const record = await api.addResume(payload);
        this.upsert(record);
        return record;
      } catch (error) {
        this.setError(error instanceof Error ? error.message : "Не удалось добавить резюме");
        throw error;
      }
    },
    async updateResume(id: string, payload: ResumeFormState) {
      this.setError(null);
      try {
        const record = await api.updateResume(id, payload);
        this.upsert(record);
        return record;
      } catch (error) {
        this.setError(error instanceof Error ? error.message : "Не удалось обновить резюме");
        throw error;
      }
    },
    async updateResumeStatus(id: string, status: StatusOption) {
      this.setError(null);
      const existing = this.findById(id);
      const previousStatus = existing?.status;
      if (existing) {
        existing.status = status;
      }
      try {
        const record = await api.updateResumeStatus(id, status);
        this.upsert(record);
        return record;
      } catch (error) {
        if (existing && previousStatus) {
          existing.status = previousStatus;
        }
        this.setError(error instanceof Error ? error.message : "Не удалось обновить статус");
        throw error;
      }
    },
  },
});
