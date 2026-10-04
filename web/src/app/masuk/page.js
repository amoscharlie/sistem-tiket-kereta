"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { API_ASAL, api, simpanSesi } from "@/lib/api";

export default function MasukPage() {
  const router = useRouter();
  const [email, setEmail] = useState("pelanggan@tiket.test");
  const [password, setPassword] = useState("password");
  const [error, setError] = useState("");

  async function kirim(event) {
    event.preventDefault();
    setError("");
    try {
      const res = await api("/masuk", { method: "POST", body: { email, password } });
      simpanSesi(res);
      const lanjut = new URLSearchParams(window.location.search).get("lanjut");
      if (res.user.role === "admin" || res.user.role === "pengelola") {
        router.push("/admin");
      } else {
        router.push(lanjut || "/");
      }
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <form onSubmit={kirim} className="mx-auto mt-10 max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-sm">
      <h1 className="text-3xl">Masuk</h1>
      <p className="text-sm text-[#5d6b7c]">
        Demo: pelanggan@tiket.test, admin@tiket.test, pengelola@tiket.test. Kata sandi: password.
      </p>
      <label className="block text-sm">
        Email
        <input className="field" value={email} onChange={(e) => setEmail(e.target.value)} />
      </label>
      <label className="block text-sm">
        Kata sandi
        <input className="field" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
      </label>
      {error ? <p className="text-sm text-[#d7263d]">{error}</p> : null}
      <button className="w-full rounded-xl bg-[#0e2a47] px-4 py-3 font-semibold text-white" type="submit">
        Masuk
      </button>
      <a className="block rounded-xl border border-[#d5deea] px-4 py-3 text-center text-sm font-semibold" href={`${API_ASAL}/auth/google`}>
        Masuk dengan Google
      </a>
      <p className="text-sm">
        Belum punya akun? <Link className="underline" href="/daftar">Daftar</Link>
      </p>
    </form>
  );
}
