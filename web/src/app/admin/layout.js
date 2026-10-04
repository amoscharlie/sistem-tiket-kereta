"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState, useSyncExternalStore } from "react";
import Ikon from "@/components/Ikon";
import { ambilSesi, api, hapusSesi, langgananSesi, penggunaStaf } from "@/lib/api";

function tanpaPengguna() {
  return null;
}

const MENU = {
  admin: [
    ["/admin", "Dasbor", "dasbor"],
    ["/admin/pembayaran", "Verifikasi", "cek"],
    ["/admin/pesanan", "Pesanan", "tiket"],
    ["/admin/pintu", "Pintu", "pintu"],
  ],
  pengelola: [
    ["/admin", "Dasbor", "dasbor"],
    ["/admin/stasiun", "Stasiun", "stasiun"],
    ["/admin/kereta", "Kereta", "kereta"],
    ["/admin/jadwal", "Jadwal", "jadwal"],
    ["/admin/voucher", "Voucher", "voucher"],
    ["/admin/laporan", "Laporan", "laporan"],
  ],
};

export default function AdminLayout({ children }) {
  const pathname = usePathname();
  const router = useRouter();
  const user = useSyncExternalStore(langgananSesi, penggunaStaf, tanpaPengguna);
  const [buka, setBuka] = useState(false);

  useEffect(() => {
    const sesi = ambilSesi();
    if (!sesi?.user || sesi.user.role === "pelanggan") {
      router.replace("/masuk?lanjut=/admin");
    }
  }, [router]);

  async function keluar() {
    try {
      await api("/keluar", { method: "POST" });
    } catch {
      // token sudah tidak berlaku
    }
    hapusSesi();
    router.push("/");
  }

  if (!user) return <p className="p-6">Memuat portal...</p>;

  const menu = MENU[user.role] ?? [];

  return (
    <div className="min-h-screen bg-[#eef2f6]">
      <header className="flex items-center justify-between bg-[#0e2a47] px-4 py-3 text-white">
        <Link href="/admin" className="font-semibold">
          Portal Tiket Kereta
        </Link>
        <div className="relative">
          <button type="button" className="rounded-lg px-3 py-2 hover:bg-white/10" onClick={() => setBuka((nilai) => !nilai)}>
            {user.name}
          </button>
          {buka ? (
            <div className="absolute right-0 z-10 mt-1 w-40 rounded-xl bg-white p-1 text-sm text-[#0e2a47] shadow-lg">
              <button type="button" className="w-full rounded-lg px-3 py-2 text-left hover:bg-[#eef2f6]" onClick={keluar}>
                Keluar
              </button>
            </div>
          ) : null}
        </div>
      </header>
      <div className="grid min-h-[calc(100vh-52px)] md:grid-cols-[220px_1fr]">
        <aside className="border-r border-[#d5deea] bg-white p-3">
          <nav className="flex gap-2 md:flex-col">
            {menu.map(([href, label, ikon]) => {
              const aktif = href === "/admin" ? pathname === "/admin" : pathname.startsWith(href);
              return (
                <Link key={href} href={href} className={`flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm ${aktif ? "bg-[#0e2a47] text-white" : "text-[#35506e] hover:bg-[#eef2f6]"}`}>
                  <Ikon nama={ikon} />
                  {label}
                </Link>
              );
            })}
          </nav>
        </aside>
        <main className="p-4 md:p-6">{children}</main>
      </div>
    </div>
  );
}
