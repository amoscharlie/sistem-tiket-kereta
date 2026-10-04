"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { LABEL_STATUS, jam, rupiah } from "@/lib/format";

export default function AdminPesananPage() {
  const [data, setData] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    api("/admin/pesanan")
      .then((res) => setData(res.data))
      .catch((err) => setError(err.message));
  }, []);

  if (error && !data) return <p className="text-[#d7263d]">{error}</p>;
  if (!data) return <p>Memuat pesanan...</p>;

  return (
    <div className="space-y-3">
      <h1 className="text-3xl">Pesanan</h1>
      <div className="overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table className="tabel">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Pemesan</th>
              <th>Rute</th>
              <th>Kereta</th>
              <th>Berangkat</th>
              <th>Status</th>
              <th>Total</th>
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
                <td>{LABEL_STATUS[item.status] ?? item.status}</td>
                <td>{rupiah(item.total)}</td>
              </tr>
            ))}
            {data.length === 0 ? (
              <tr>
                <td colSpan="7">Belum ada pesanan.</td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}
