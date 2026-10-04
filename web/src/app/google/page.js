"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState, useSyncExternalStore } from "react";
import { api, simpanSesi } from "@/lib/api";

function tokenGoogle() {
  return new URLSearchParams(window.location.search).get("token") ?? "";
}

function tanpaLangganan() {
  return () => {};
}

function tokenKosong() {
  return null;
}

export default function GooglePage() {
  const router = useRouter();
  const token = useSyncExternalStore(tanpaLangganan, tokenGoogle, tokenKosong);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!token) return;
    simpanSesi({ token, user: null });
    api("/saya")
      .then((res) => {
        const user = res.user ?? res;
        simpanSesi({ token, user });
        router.replace(user.role === "admin" || user.role === "pengelola" ? "/admin" : "/");
      })
      .catch((err) => setError(err.message));
  }, [router, token]);

  const teks = error || (token === "" ? "Token Google tidak ada." : "Menyelesaikan masuk Google...");
  return <p className="mx-auto max-w-xl px-4 py-8">{teks}</p>;
}
