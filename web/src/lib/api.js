const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";
export const API_ASAL = BASE.replace(/\/api\/v1$/, "");

export function ambilSesi() {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem("tiket_sesi");
  return raw ? JSON.parse(raw) : null;
}

let cuplikanSesi = { raw: undefined, user: null };

function penggunaDariPenyimpanan() {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem("tiket_sesi");
  if (raw === cuplikanSesi.raw) return cuplikanSesi.user;
  let user = null;
  if (raw) {
    try {
      user = JSON.parse(raw).user ?? null;
    } catch {
      user = null;
    }
  }
  cuplikanSesi = { raw, user };
  return user;
}

export function langgananSesi(pemberitahu) {
  window.addEventListener("sesi-berubah", pemberitahu);
  return () => window.removeEventListener("sesi-berubah", pemberitahu);
}

export function penggunaSesi() {
  return penggunaDariPenyimpanan();
}

export function penggunaStaf() {
  const user = penggunaDariPenyimpanan();
  if (!user || user.role === "pelanggan") return null;
  return user;
}

export function simpanSesi(sesi) {
  localStorage.setItem("tiket_sesi", JSON.stringify(sesi));
  window.dispatchEvent(new Event("sesi-berubah"));
}

export function hapusSesi() {
  localStorage.removeItem("tiket_sesi");
  window.dispatchEvent(new Event("sesi-berubah"));
}

export async function api(path, { method = "GET", body, form } = {}) {
  const sesi = ambilSesi();
  const headers = { Accept: "application/json" };
  if (sesi?.token) headers.Authorization = `Bearer ${sesi.token}`;

  let payload;
  if (form) {
    payload = form;
  } else if (body !== undefined) {
    headers["Content-Type"] = "application/json";
    payload = JSON.stringify(body);
  }

  const res = await fetch(`${BASE}${path}`, { method, headers, body: payload });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const error = new Error(data.message || "Permintaan gagal.");
    error.status = res.status;
    error.errors = data.errors ?? {};
    throw error;
  }
  return data;
}
