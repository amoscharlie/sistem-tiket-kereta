"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { jam, rupiah } from "@/lib/format";

export default function AdminPembayaranPage() {
  const [data, setData] = useState(null);
  const [error, setError] = useState("");

  function muat() {
    api("/admin/pembayaran")
      .then((res) => setData(res.data))
      .catch((err) => setError(err.message));
  }

  useEffect(() => {
    muat();
  }, []);

  async function putuskan(id, setuju) {
    setError("");
    try {
      await api(`/admin/pembayaran/${id}/${setuju ? "setujui" : "tolak"}`, {
        method: "POST",
        body: setuju ? {} : { catatan: "Bukti tidak jelas. Unggah ulang." },
      });
      muat();
    } catch (err) {
      setError(err.message);
    }
  }

  if (error && !data) return <p>{error}</p>;
  if (!data) return <p>Memuat antrean...</p>;

  return (
    <div className="space-y-4">
      <h1 className="text-3xl">Verifikasi pembayaran</h1>
      {error ? <p className="text-[#b8432f]">{error}</p> : null}
      <div className="overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table className="tabel">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Pemesan</th>
              <th>Rute</th>
              <th>Kereta</th>
              <th>Berangkat</th>
              <th>Total</th>
              <th>Bukti</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {data.map((item) => (
              <tr key={item.id}>
                <td>{item.kode}</td>
                <td>{item.pemesan?.name}</td>
                <td>
                  {item.asal} ke {item.tujuan}
                </td>
                <td>{item.kereta}</td>
                <td className="whitespace-nowrap">{jam(item.berangkat_at)}</td>
                <td>{rupiah(item.total)}</td>
                <td>
                  {item.pembayaran?.bukti_url ? (
                    <a className="font-semibold text-[#0e2a47]" href={item.pembayaran.bukti_url} target="_blank" rel="noreferrer">
                      Lihat
                    </a>
                  ) : (
                    "Belum ada"
                  )}
                </td>
                <td className="whitespace-nowrap">
                  {item.pembayaran?.bukti_url ? (
                    <>
                      <button className="font-semibold text-[#0e2a47]" type="button" onClick={() => putuskan(item.pembayaran.id, true)}>
                        Setujui
                      </button>
                      <button className="ml-3 font-semibold text-[#d7263d]" type="button" onClick={() => putuskan(item.pembayaran.id, false)}>
                        Tolak
                      </button>
                    </>
                  ) : (
                    "Menunggu bukti"
                  )}
                </td>
              </tr>
            ))}
            {data.length === 0 ? (
              <tr>
                <td colSpan="8">Tidak ada pembayaran yang menunggu.</td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}
