import express from "express";
import cors from "cors";
import { nanoid } from "nanoid";
import { MongoClient } from "mongodb";

const STATUSES = ["new", "interview", "hired", "rejected"];

const MONGO_URI = process.env.MONGO_URI || "mongodb://127.0.0.1:27017";
const MONGO_DB = process.env.MONGO_DB || "resume_db";
const MONGO_COLLECTION = process.env.MONGO_COLLECTION || "resumes";

const client = new MongoClient(MONGO_URI);
await client.connect();
const collection = client.db(MONGO_DB).collection(MONGO_COLLECTION);
await collection.createIndex({ id: 1 }, { unique: true });

const app = express();
app.use(cors());
app.use(express.json({ limit: "2mb" }));

const normalizeEducation = (entries = []) =>
  Array.isArray(entries)
    ? entries.map((entry) => ({
        level: entry.level ?? "Среднее",
        institution: entry.institution ?? "",
        faculty: entry.faculty ?? "",
        specialization: entry.specialization ?? "",
        graduationYear: entry.graduationYear ?? "",
      }))
    : [];

const prepareResume = (payload = {}) => {
  const now = new Date().toISOString();
  return {
    id: payload.id ?? nanoid(),
    profession: payload.profession ?? "",
    city: payload.city ?? "",
    photoUrl: payload.photoUrl ?? "",
    fullName: payload.fullName ?? "",
    phone: payload.phone ?? "",
    email: payload.email ?? "",
    birthdate: payload.birthdate ?? "",
    salary: payload.salary ?? "",
    skills: payload.skills ?? "",
    about: payload.about ?? "",
    status: STATUSES.includes(payload.status) ? payload.status : STATUSES[0],
    educationEntries: normalizeEducation(payload.educationEntries),
    createdAt: payload.createdAt ?? now,
    updatedAt: now,
  };
};

const serialize = (doc) => {
  if (!doc) return null;
  const { _id, ...rest } = doc;
  return rest;
};

app.get("/api/cv", async (_req, res) => {
  const items = await collection.find({}, { sort: { createdAt: -1 } }).toArray();
  res.json({ data: items.map(serialize) });
});

app.get("/api/cv/:id", async (req, res) => {
  const record = await collection.findOne({ id: req.params.id });
  if (!record) {
    res.status(404).json({ error: "Resume not found" });
    return;
  }
  res.json({ data: serialize(record) });
});

app.post("/api/cv/:id/add", async (req, res) => {
  const id = req.params.id || req.body.id || nanoid();
  const existing = await collection.findOne({ id });
  if (existing) {
    res.status(409).json({ error: "Resume already exists" });
    return;
  }
  const record = prepareResume({ ...req.body, id });
  await collection.insertOne(record);
  res.status(201).json({ data: serialize(record) });
});

app.post("/api/cv/:id/edit", async (req, res) => {
  const existing = await collection.findOne({ id: req.params.id });
  if (!existing) {
    res.status(404).json({ error: "Resume not found" });
    return;
  }
  const updated = prepareResume({
    ...existing,
    ...req.body,
    id: existing.id,
    createdAt: existing.createdAt,
  });
  await collection.replaceOne({ id: existing.id }, updated);
  res.json({ data: serialize(updated) });
});

app.post("/api/cv/:id/status/update", async (req, res) => {
  const { status } = req.body ?? {};
  if (!STATUSES.includes(status)) {
    res.status(400).json({ error: "Invalid status" });
    return;
  }
  const updatedRecord = await collection.findOneAndUpdate(
    { id: req.params.id },
    { $set: { status, updatedAt: new Date().toISOString() } },
    { returnDocument: "after" }
  );
  if (!updatedRecord) {
    res.status(404).json({ error: "Resume not found" });
    return;
  }
  res.json({ data: serialize(updatedRecord) });
});

const PORT = process.env.PORT || 4000;

app.listen(PORT, () => {
  console.log(`API server listening on http://localhost:${PORT}`);
});
