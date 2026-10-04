"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/Modal";
import { api } from "@/lib/api";
import { konfirmasiHapus, pemberitahuan } from "@/lib/swal";

const kosong = { kode: "", nama: "", kota: "" };

export default function AdminStasiunPage() {
  const [stasiun, setStasiun] = useState([]);
  const [cari, setCari] = useState("");
  const [form, setForm] = useState(null);
  const [error, setError] = useState("");

  function muat() {
    api("/pengelola/master")
      .then((res) => setStasiun(res.stasiun))
      .catch((err) => setError(err.message));
  }

  useEffect(() => {
    muat();
  }, []);

  async function simpan(event) {
    event.preventDefault();
    setError("");
    try {
      if (form.id) {
        await api(`/pengelola/stasiun/${form.id}`, { method: "PUT", body: form });
      } else {
        await api("/pengelola/stasiun", { method: "POST", body: form });
      }
      setForm(null);
      muat();
      pemberitahuan("Stasiun tersimpan");
    } catch (err) {
      const detail = Object.values(err.errors ?? {}).flat().join(" ");
      setError(detail || err.message);
    }
  }

  async function hapus(item) {
    const jawab = await konfirmasiHapus(`${item.kode} · ${item.nama}`);
    if (!jawab.isConfirmed) return;
    try {
      await api(`/pengelola/stasiun/${item.id}`, { method: "DELETE" });
      muat();
      pemberitahuan("Stasiun dihapus");
    } catch (err) {
      pemberitahuan(err.message, "error");
    }
  }

  const kata = cari.toLowerCase();
  const tampil = stasiun.filter((item) => `${item.kode} ${item.nama} ${item.kota}`.toLowerCase().includes(kata));

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <h1 className="text-3xl">Stasiun</h1>
        <button type="button" className="rounded-xl bg-[#0e2a47] px-4 py-2.5 text-sm font-semibold text-white" onClick={() => { setError(""); setForm(kosong); }}>
          Stasiun baru
        </button>
      </div>
      <input className="field max-w-sm" placeholder="Cari kode, nama, atau kota" value={cari} onChange={(e) => setCari(e.target.value)} />
      {error && !form ? <p className="text-sm text-[#d7263d]">{error}</p> : null}
      <div className="overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table className="tabel">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama</th>
              <th>Kota</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {tampil.map((item) => (
              <tr key={item.id}>
                <td>{item.kode}</td>
                <td>{item.nama}</td>
                <td>{item.kota}</td>
                <td className="whitespace-nowrap">
                  <button type="button" className="font-semibold text-[#0e2a47]" onClick={() => { setError(""); setForm(item); }}>
                    Ubah
                  </button>
                  <button type="button" className="ml-3 font-semibold text-[#d7263d]" onClick={() => hapus(item)}>
                    Hapus
                  </button>
                </td>
              </tr>
            ))}
            {tampil.length === 0 ? (
              <tr>
                <td colSpan="4">Tidak ada stasiun.</td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
      {form ? (
        <Modal judul={form.id ? "Ubah stasiun" : "Stasiun baru"} onTutup={() => setForm(null)}>
          <form onSubmit={simpan} className="space-y-3">
            {["kode", "nama", "kota"].map((name) => (
              <label key={name} className="block text-sm capitalize">
                {name}
                <input className="field" required value={form[name] ?? ""} onChange={(e) => setForm({ ...form, [name]: e.target.value })} />
              </label>
            ))}
            {error ? <p className="text-sm text-[#d7263d]">{error}</p> : null}
            <button className="w-full rounded-xl bg-[#0e2a47] px-4 py-2.5 text-sm font-semibold text-white" type="submit">
              Simpan
            </button>
          </form>
        </Modal>
      ) : null}
    </div>
  );
}
