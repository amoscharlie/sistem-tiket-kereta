"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { api, simpanSesi } from "@/lib/api";

export default function DaftarPage() {
  const router = useRouter();
  const [form, setForm] = useState({ name: "", email: "", telepon: "", password: "", password_confirmation: "" });
  const [error, setError] = useState("");

  function ubah(event) {
    setForm({ ...form, [event.target.name]: event.target.value });
  }

  async function kirim(event) {
    event.preventDefault();
    setError("");
    try {
      const res = await api("/daftar", { method: "POST", body: form });
      simpanSesi(res);
      router.push("/akun");
    } catch (err) {
      const detail = Object.values(err.errors ?? {}).flat().join(" ");
      setError(detail || err.message);
    }
  }

  return (
    <form onSubmit={kirim} className="mx-auto mt-10 max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-sm">
      <h1 className="text-3xl">Daftar</h1>
      {["name", "email", "telepon", "password", "password_confirmation"].map((name) => (
        <label key={name} className="block text-sm capitalize">
          {name === "name" ? "Nama" : name === "telepon" ? "Nomor HP" : name === "password_confirmation" ? "Ulangi kata sandi" : name === "password" ? "Kata sandi" : "Email"}
          <input
            className="field"
            name={name}
            type={name.includes("password") ? "password" : name === "email" ? "email" : "text"}
            value={form[name]}
            onChange={ubah}
          />
        </label>
      ))}
      {error ? <p className="text-sm text-[#d7263d]">{error}</p> : null}
      <button className="w-full rounded-xl bg-[#0e2a47] px-4 py-3 font-semibold text-white" type="submit">
        Buat akun
      </button>
      <p className="text-sm">
        Sudah punya akun? <Link className="underline" href="/masuk">Masuk</Link>
      </p>
    </form>
  );
}
