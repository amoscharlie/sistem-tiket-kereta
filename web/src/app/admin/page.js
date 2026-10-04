"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import Ikon from "@/components/Ikon";
import { ambilSesi, api } from "@/lib/api";
import { rupiah } from "@/lib/format";

function Kotak({ label, nilai, href, ikon }) {
  const isi = (
    <div className="rounded-2xl bg-white p-5 shadow-sm">
      <div className="flex items-center justify-between gap-3">
        <p className="text-sm text-[#5d6b7c]">{label}</p>
        <span className="grid h-9 w-9 place-items-center rounded-xl bg-[#eef3f8] text-[#0e2a47]">
          <Ikon nama={ikon} />
        </span>
      </div>
      <p className="mt-3 text-3xl font-semibold text-[#0e2a47]">{nilai}</p>
    </div>
  );
  return href ? <Link href={href}>{isi}</Link> : isi;
}

export default function AdminIndexPage() {
  const [peran, setPeran] = useState("");
  const [data, setData] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    const role = ambilSesi()?.user?.role ?? "";
    setPeran(role);
    const jalur = role === "pengelola" ? "/pengelola/ringkas" : "/admin/ringkas";
    api(jalur)
      .then((res) => setData(res.data))
      .catch((err) => setError(err.message));
  }, []);

  if (error) return <p className="text-[#d7263d]">{error}</p>;
  if (!data) return <p>Memuat dasbor...</p>;

  if (peran === "pengelola") {
    return (
      <div className="space-y-4">
        <h1 className="text-3xl">Dasbor pengelola</h1>
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <Kotak label="Stasiun" nilai={data.stasiun} href="/admin/stasiun" ikon="stasiun" />
          <Kotak label="Jadwal" nilai={data.jadwal} href="/admin/jadwal" ikon="jadwal" />
          <Kotak label="Berangkat hari ini" nilai={data.jadwal_hari_ini} href="/admin/laporan" ikon="kereta" />
          <Kotak label="Voucher aktif" nilai={data.voucher_aktif} href="/admin/voucher" ikon="voucher" />
          <Kotak label="Kursi terjual" nilai={`${data.terjual} / ${data.kursi}`} ikon="tiket" />
          <Kotak label="Pesanan lunas" nilai={data.pesanan_lunas} ikon="cek" />
          <Kotak label="Menunggu bayar" nilai={data.pesanan_menunggu} ikon="jam" />
          <Kotak label="Pendapatan lunas" nilai={rupiah(data.pendapatan)} href="/admin/laporan" ikon="laporan" />
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h1 className="text-3xl">Dasbor admin</h1>
      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <Kotak label="Menunggu verifikasi" nilai={data.menunggu_verifikasi} href="/admin/pembayaran" ikon="cek" />
        <Kotak label="Menunggu bayar" nilai={data.menunggu_bayar} href="/admin/pesanan" ikon="jam" />
        <Kotak label="Lunas" nilai={data.lunas} href="/admin/pesanan" ikon="tiket" />
        <Kotak label="Kedaluwarsa" nilai={data.kedaluwarsa} ikon="jadwal" />
        <Kotak label="Bukti ditolak" nilai={data.ditolak} ikon="keluar" />
        <Kotak label="Pendapatan lunas" nilai={rupiah(data.pendapatan)} ikon="laporan" />
      </div>
    </div>
  );
}
