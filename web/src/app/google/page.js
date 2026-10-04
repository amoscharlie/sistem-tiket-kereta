"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { api, simpanSesi } from "@/lib/api";

export default function GooglePage() {
  const router = useRouter();
  const [error, setError] = useState("");

  useEffect(() => {
    const token = new URLSearchParams(window.location.search).get("token");
    if (!token) {
      setError("Token Google tidak ada.");
      return;
    }
    simpanSesi({ token, user: null });
    api("/saya")
      .then((res) => {
        const user = res.user ?? res;
        simpanSesi({ token, user });
        router.replace(user.role === "admin" || user.role === "pengelola" ? "/admin" : "/");
      })
      .catch((err) => setError(err.message));
  }, [router]);

  return <p className="mx-auto max-w-xl px-4 py-8">{error || "Menyelesaikan masuk Google..."}</p>;
}
