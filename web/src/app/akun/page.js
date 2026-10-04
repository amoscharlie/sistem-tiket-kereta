"use client";

import { useEffect, useState } from "react";
import { api, ambilSesi, simpanSesi } from "@/lib/api";

const KANAL = [
  ["email", "Email", "email_terverifikasi"],
  ["hp", "Nomor HP", "hp_terverifikasi"],
  ["whatsapp", "WhatsApp", "whatsapp_terverifikasi"],
];

export default function AkunPage() {
  const [user, setUser] = useState(null);
  const [kodePercobaan, setKodePercobaan] = useState({});
  const [input, setInput] = useState({ email: "", hp: "", whatsapp: "" });
  const [error, setError] = useState("");

  useEffect(() => {
    api("/saya")
      .then((res) => setUser(res.user ?? res))
      .catch((err) => setError(err.message));
  }, []);

  async function kirim(kanal) {
    setError("");
    try {
      const res = await api("/verifikasi/kirim", { method: "POST", body: { kanal } });
      setKodePercobaan((sekarang) => ({ ...sekarang, [kanal]: res.kode }));
    } catch (err) {
      setError(err.message);
    }
  }

  async function konfirmasi(kanal) {
    setError("");
    try {
      const res = await api("/verifikasi/konfirmasi", { method: "POST", body: { kanal, kode: input[kanal] } });
      setUser(res.user);
      const sesi = ambilSesi();
      if (sesi) simpanSesi({ ...sesi, user: res.user });
      setKodePercobaan((sekarang) => ({ ...sekarang, [kanal]: "" }));
    } catch (err) {
      setError(err.message);
    }
  }

  if (!user) return <p className="mx-auto max-w-xl px-4 py-8">{error || "Memuat akun..."}</p>;

  return (
    <div className="mx-auto max-w-xl space-y-4 px-4 py-8">
      <h1 className="text-3xl">Verifikasi akun</h1>
      <p className="text-sm text-[#5d6b7c]">Mode percobaan. Kode muncul di layar, tidak dikirim ke email, SMS, atau WhatsApp sungguhan.</p>
      {KANAL.map(([kanal, label, kolom]) => (
        <section key={kanal} className="space-y-2 rounded-2xl bg-white p-4 shadow-sm">
          <h2 className="text-xl">
            {label} {user[kolom] ? "· sudah" : ""}
          </h2>
          {user[kolom] ? null : (
            <>
              <button type="button" className="rounded-xl bg-[#0e2a47] px-4 py-2 text-sm font-semibold text-white" onClick={() => kirim(kanal)}>
                Minta kode
              </button>
              {kodePercobaan[kanal] ? <p className="text-sm">Kode percobaan: {kodePercobaan[kanal]}</p> : null}
              <div className="flex gap-2">
                <input className="field" inputMode="numeric" maxLength={6} value={input[kanal]} onChange={(e) => setInput({ ...input, [kanal]: e.target.value })} />
                <button type="button" className="rounded-xl bg-[#d7263d] px-4 py-2 text-sm font-semibold text-white" onClick={() => konfirmasi(kanal)}>
                  Konfirmasi
                </button>
              </div>
            </>
          )}
        </section>
      ))}
      {error ? <p className="text-sm text-[#d7263d]">{error}</p> : null}
    </div>
  );
}
