"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import Ikon from "@/components/Ikon";
import { ambilSesi, api, hapusSesi } from "@/lib/api";

export default function Header() {
  const pathname = usePathname();
  const router = useRouter();
  const [user, setUser] = useState(null);
  const [buka, setBuka] = useState(false);

  useEffect(() => {
    const muat = () => setUser(ambilSesi()?.user ?? null);
    muat();
    window.addEventListener("sesi-berubah", muat);
    return () => window.removeEventListener("sesi-berubah", muat);
  }, []);

  async function keluar() {
    try {
      await api("/keluar", { method: "POST" });
    } catch {
      // token sudah tidak berlaku tetap dianggap keluar
    }
    hapusSesi();
    setBuka(false);
    router.push("/");
  }

  if (pathname.startsWith("/admin")) return null;

  const staf = user && (user.role === "admin" || user.role === "pengelola");

  return (
    <header className="bg-[#0e2a47] text-white print:hidden">
      <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3.5">
        <Link href="/" className="flex items-center gap-2.5 font-semibold tracking-wide">
          <span className="grid h-9 w-9 place-items-center rounded-lg bg-[#d7263d] text-xs font-bold">KA</span>
          Tiket Kereta
        </Link>
        <nav className="flex items-center gap-1 text-sm sm:gap-2">
          <Link href="/" className="flex items-center gap-1.5 rounded-lg px-3 py-2 hover:bg-white/10">
            <Ikon nama="cari" />
            Cari tiket
          </Link>
          {user ? (
            <>
              <Link href="/pesanan" className="flex items-center gap-1.5 rounded-lg px-3 py-2 hover:bg-white/10">
                <Ikon nama="tiket" />
                Pesanan
              </Link>
              <div className="relative">
                <button type="button" className="flex items-center gap-1.5 rounded-lg px-3 py-2 hover:bg-white/10" onClick={() => setBuka((nilai) => !nilai)}>
                  <Ikon nama="pengguna" />
                  {user.name}
                </button>
                {buka ? (
                  <div className="absolute right-0 z-20 mt-1 w-44 rounded-xl bg-white p-1 text-[#0e2a47] shadow-lg">
                    {user.role === "pelanggan" ? (
                      <Link href="/akun" className="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-[#eef2f6]" onClick={() => setBuka(false)}>
                        <Ikon nama="pengguna" />
                        Akun
                      </Link>
                    ) : null}
                    {staf ? (
                      <Link href="/admin" className="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-[#eef2f6]" onClick={() => setBuka(false)}>
                        <Ikon nama="dasbor" />
                        Portal
                      </Link>
                    ) : null}
                    <button type="button" className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left hover:bg-[#eef2f6]" onClick={keluar}>
                      <Ikon nama="keluar" />
                      Keluar
                    </button>
                  </div>
                ) : null}
              </div>
            </>
          ) : (
            <>
              <Link href="/masuk" className="flex items-center gap-1.5 rounded-lg px-3 py-2 hover:bg-white/10">
                <Ikon nama="pengguna" />
                Masuk
              </Link>
              <Link href="/daftar" className="rounded-lg bg-white px-3 py-2 font-medium text-[#0e2a47]">
                Daftar
              </Link>
            </>
          )}
        </nav>
      </div>
    </header>
  );
}
