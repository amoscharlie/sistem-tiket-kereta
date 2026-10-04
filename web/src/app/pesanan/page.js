"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { LABEL_STATUS, jam, rupiah, tanggalWib } from "@/lib/format";

function kelompok(status) {
  if (status === "lunas") return "lunas";
  if (status === "menunggu_bayar" || status === "menunggu_verifikasi") return "menunggu";
  return "gagal";
}

export default function PesananPage() {
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const [status, setStatus] = useState("semua");
  const [tanggal, setTanggal] = useState("");

  useEffect(() => {
    api("/pesanan")
      .then((res) => setData(res.data))
      .catch((err) => setError(err.status === 401 ? "Masuk dulu untuk melihat pesanan." : err.message));
  }, []);

  if (error) return <p className="mx-auto max-w-5xl px-4 py-8">{error}</p>;
  if (!data) return <p className="mx-auto max-w-5xl px-4 py-8">Memuat pesanan...</p>;

  const tampil = data.filter((item) => {
    const lolosStatus = status === "semua" || kelompok(item.status) === status;
    const lolosTanggal = !tanggal || tanggalWib(item.berangkat_at) === tanggal;
    return lolosStatus && lolosTanggal;
  });

  return (
    <div className="mx-auto max-w-5xl space-y-4 px-4 py-8">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <h1 className="text-4xl">Pesanan saya</h1>
        <Link href="/tiket" className="rounded-xl border border-[#0e2a47] px-4 py-2 text-sm font-semibold">
          Cetak tiket
        </Link>
      </div>
      <div className="grid gap-3 rounded-2xl bg-white p-4 shadow-sm md:grid-cols-2">
        <label className="text-sm">
          Status
          <select className="field" value={status} onChange={(e) => setStatus(e.target.value)}>
            <option value="semua">Semua</option>
            <option value="lunas">Sudah bayar</option>
            <option value="menunggu">Menunggu bayar</option>
            <option value="gagal">Gagal</option>
          </select>
        </label>
        <label className="text-sm">
          Tanggal berangkat
          <input className="field" type="date" value={tanggal} onChange={(e) => setTanggal(e.target.value)} />
        </label>
      </div>
      {data.length === 0 ? <p>Belum ada pesanan.</p> : null}
      {data.length > 0 && tampil.length === 0 ? <p>Tidak ada pesanan untuk filter ini.</p> : null}
      {tampil.map((item) => (
        <article key={item.id} className="rounded-2xl bg-white p-5 shadow-sm">
          <div className="flex items-baseline justify-between gap-3">
            <h2 className="text-2xl">
              {item.asal} ke {item.tujuan}
            </h2>
            <span className="text-sm">{LABEL_STATUS[item.status] ?? item.status}</span>
          </div>
          <p className="mt-1 text-sm text-[#5c6570]">
            {item.kereta} · {item.kelas} · {jam(item.berangkat_at)}
          </p>
          <p className="mt-2">{rupiah(item.total)}</p>
          <div className="mt-3 flex gap-4 text-sm font-semibold">
            <Link className="text-[#d7263d]" href={`/pesanan/${item.id}`}>
              Detail
            </Link>
            {item.status === "lunas" ? (
              <Link className="text-[#0e2a47]" href={`/tiket?kode=${item.kode}`}>
                Cetak
              </Link>
            ) : null}
          </div>
        </article>
      ))}
    </div>
  );
}
