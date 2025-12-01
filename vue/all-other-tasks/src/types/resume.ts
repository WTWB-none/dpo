import type { EducationLevel, StatusOption } from "../constants/resume";

export type { StatusOption };

export type EducationEntryForm = {
  id: number;
  level: EducationLevel;
  institution: string;
  faculty: string;
  specialization: string;
  graduationYear: string;
};

export type ResumeFormState = {
  profession: string;
  city: string;
  photoUrl: string;
  fullName: string;
  phone: string;
  email: string;
  birthdate: string;
  salary: string;
  skills: string;
  about: string;
  status: StatusOption;
  educationEntries: EducationEntryForm[];
};

export type ResumeRecord = ResumeFormState & {
  id: string;
  createdAt: string;
  updatedAt: string;
};
