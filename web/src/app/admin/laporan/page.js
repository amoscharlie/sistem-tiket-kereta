"use client";

import { useEffect, useState } from "react";
import Ikon from "@/components/Ikon";
import { api } from "@/lib/api";
import { LABEL_STATUS, jam, rupiah } from "@/lib/format";

function unduhCsv(nama, baris) {
  const isi = baris.map((kolom) => kolom.map((sel) => `"${String(sel ?? "").replaceAll('"', '""')}"`).join(",")).join("\n");
  const berkas = new Blob([`\uFEFF${isi}`], { type: "text/csv;charset=utf-8" });
  const tautan = document.createElement("a");
  tautan.href = URL.createObjectURL(berkas);
  tautan.download = nama;
  tautan.click();
  URL.revokeObjectURL(tautan.href);
}

export default function LaporanPage() {
  const [bagian, setBagian] = useState("perjalanan");
  const [tanggal, setTanggal] = useState("");
  const [status, setStatus] = useState("");
  const [perjalanan, setPerjalanan] = useState(null);
  const [pembayaran, setPembayaran] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    const query = tanggal ? `?tanggal=${tanggal}` : "";
    api(`/pengelola/laporan/perjalanan${query}`)
      .then((res) => setPerjalanan(res.data))
      .catch((err) => setError(err.message));
  }, [tanggal]);

  useEffect(() => {
    const query = status ? `?status=${status}` : "";
    api(`/pengelola/laporan/pembayaran${query}`)
      .then((res) => setPembayaran(res))
      .catch((err) => setError(err.message));
  }, [status]);

  return (
    <div className="space-y-4">
      <h1 className="text-3xl">Laporan</h1>
      <div className="flex gap-2">
        <button type="button" className={`rounded-lg px-3 py-2 text-sm ${bagian === "perjalanan" ? "bg-[#0e2a47] text-white" : "bg-white"}`} onClick={() => setBagian("perjalanan")}>
          Perjalanan
        </button>
        <button type="button" className={`rounded-lg px-3 py-2 text-sm ${bagian === "pembayaran" ? "bg-[#0e2a47] text-white" : "bg-white"}`} onClick={() => setBagian("pembayaran")}>
          Pembayaran
        </button>
      </div>
      {error ? <p className="text-sm text-[#d7263d]">{error}</p> : null}

      {bagian === "perjalanan" ? (
        <>
          <div className="flex flex-wrap items-end gap-3">
            <label className="block max-w-xs text-sm">
              Tanggal berangkat
              <input className="field" type="date" value={tanggal} onChange={(e) => setTanggal(e.target.value)} />
            </label>
            <button
              type="button"
              className="flex items-center gap-2 rounded-xl border border-[#0e2a47] px-4 py-2.5 text-sm font-semibold"
              onClick={() =>
                unduhCsv("laporan-perjalanan.csv", [
                  ["Kereta", "Rute", "Berangkat", "Kelas", "Harga", "Kapasitas", "Terjual", "Ditahan", "Sisa", "Nilai terjual"],
                  ...(perjalanan ?? []).map((item) => [item.kereta, `${item.asal} ke ${item.tujuan}`, jam(item.berangkat_at), item.kelas, item.harga, item.kapasitas, item.terjual, item.ditahan, item.sisa, item.nilai_terjual]),
                ])
              }
            >
              <Ikon nama="unduh" />
              Unduh
            </button>
          </div>
          {!perjalanan ? <p>Memuat laporan perjalanan...</p> : null}
          {perjalanan ? (
            <div className="overflow-x-auto rounded-2xl bg-white shadow-sm">
              <table className="tabel">
                <thead>
                  <tr>
                    <th>Kereta</th>
                    <th>Rute</th>
                    <th>Berangkat</th>
                    <th>Kelas</th>
                    <th>Harga</th>
                    <th>Kapasitas</th>
                    <th>Terjual</th>
                    <th>Ditahan</th>
                    <th>Sisa</th>
                    <th>Nilai terjual</th>
                  </tr>
                </thead>
                <tbody>
                  {perjalanan.map((item) => (
                    <tr key={item.id}>
                      <td>{item.kereta}</td>
                      <td>
                        {item.asal} ke {item.tujuan}
                      </td>
                      <td>{jam(item.berangkat_at)}</td>
                      <td>{item.kelas}</td>
                      <td>{rupiah(item.harga)}</td>
                      <td>{item.kapasitas}</td>
                      <td>{item.terjual}</td>
                      <td>{item.ditahan}</td>
                      <td>{item.sisa}</td>
                      <td>{rupiah(item.nilai_terjual)}</td>
                    </tr>
                  ))}
                  {perjalanan.length === 0 ? (
                    <tr>
                      <td colSpan="10">Tidak ada perjalanan.</td>
                    </tr>
                  ) : null}
                </tbody>
              </table>
            </div>
          ) : null}
        </>
      ) : null}

      {bagian === "pembayaran" ? (
        <>
          <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            {(pembayaran?.ringkas ?? []).map((item) => (
              <div key={item.status} className="rounded-2xl bg-white p-4 shadow-sm">
                <p className="text-sm text-[#5d6b7c]">{LABEL_STATUS[item.status] ?? item.status}</p>
                <p className="mt-1 text-xl font-semibold">{Number(item.banyak)} transaksi</p>
                <p className="text-sm">{rupiah(item.total)}</p>
              </div>
            ))}
          </div>
          <div className="flex flex-wrap items-end gap-3">
          <label className="block max-w-xs text-sm">
            Status
            <select className="field" value={status} onChange={(e) => setStatus(e.target.value)}>
              <option value="">Semua</option>
              <option value="menunggu_bukti">Menunggu bukti</option>
              <option value="menunggu_verifikasi">Menunggu verifikasi</option>
              <option value="lunas">Lunas</option>
              <option value="ditolak">Ditolak</option>
            </select>
          </label>
            <button
              type="button"
              className="flex items-center gap-2 rounded-xl border border-[#0e2a47] px-4 py-2.5 text-sm font-semibold"
              onClick={() =>
                unduhCsv("laporan-pembayaran.csv", [
                  ["Kode", "Pemesan", "Rute", "Kereta", "Metode", "Status", "Jumlah", "Dibuat"],
                  ...(pembayaran?.data ?? []).map((item) => [item.kode, item.pemesan, item.rute, item.kereta, item.metode, LABEL_STATUS[item.status] ?? item.status, item.jumlah, jam(item.dibuat_at)]),
                ])
              }
            >
              <Ikon nama="unduh" />
              Unduh
            </button>
          </div>
          {!pembayaran ? <p>Memuat laporan pembayaran...</p> : null}
          {pembayaran ? (
            <div className="overflow-x-auto rounded-2xl bg-white shadow-sm">
              <table className="tabel">
                <thead>
                  <tr>
                    <th>Kode</th>
                    <th>Pemesan</th>
                    <th>Rute</th>
                    <th>Kereta</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Jumlah</th>
                    <th>Dibuat</th>
                  </tr>
                </thead>
                <tbody>
                  {pembayaran.data.map((item) => (
                    <tr key={item.id}>
                      <td>{item.kode}</td>
                      <td>{item.pemesan}</td>
                      <td>{item.rute}</td>
                      <td>{item.kereta}</td>
                      <td>{item.metode}</td>
                      <td>{LABEL_STATUS[item.status] ?? item.status}</td>
                      <td>{rupiah(item.jumlah)}</td>
                      <td>{jam(item.dibuat_at)}</td>
                    </tr>
                  ))}
                  {pembayaran.data.length === 0 ? (
                    <tr>
                      <td colSpan="8">Tidak ada pembayaran.</td>
                    </tr>
                  ) : null}
                </tbody>
              </table>
            </div>
          ) : null}
        </>
      ) : null}
    </div>
  );
}
