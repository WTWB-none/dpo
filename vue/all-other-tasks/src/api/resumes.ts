import type { ResumeFormState, ResumeRecord, StatusOption } from "@/types/resume";

const BASE_URL = "/api/cv";

const handleResponse = async <T>(response: Response): Promise<T> => {
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    const errorMessage = (payload && payload.error) || response.statusText || "Ошибка запроса";
    throw new Error(errorMessage);
  }
  return payload as T;
};

const generateId = () => {
  if (typeof crypto !== "undefined" && "randomUUID" in crypto) {
    return crypto.randomUUID();
  }
  return `cv_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
};

export const fetchResumes = async (): Promise<ResumeRecord[]> => {
  const result = await handleResponse<{ data: ResumeRecord[] }>(await fetch(BASE_URL));
  return result.data ?? [];
};

export const fetchResume = async (id: string): Promise<ResumeRecord> => {
  const result = await handleResponse<{ data: ResumeRecord }>(await fetch(`${BASE_URL}/${id}`));
  return result.data;
};

export const addResume = async (payload: ResumeFormState): Promise<ResumeRecord> => {
  const id = (payload as ResumeRecord)?.id ?? generateId();
  const response = await handleResponse<{ data: ResumeRecord }>(
    await fetch(`${BASE_URL}/${id}/add`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
  );
  return response.data;
};

export const updateResume = async (
  id: string,
  payload: ResumeFormState
): Promise<ResumeRecord> => {
  const response = await handleResponse<{ data: ResumeRecord }>(
    await fetch(`${BASE_URL}/${id}/edit`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
  );
  return response.data;
};

export const updateResumeStatus = async (
  id: string,
  status: StatusOption
): Promise<ResumeRecord> => {
  const response = await handleResponse<{ data: ResumeRecord }>(
    await fetch(`${BASE_URL}/${id}/status/update`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status }),
    })
  );
  return response.data;
};
