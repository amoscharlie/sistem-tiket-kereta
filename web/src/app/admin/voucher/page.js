"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/Modal";
import { api } from "@/lib/api";
import { rupiah } from "@/lib/format";
import { konfirmasiHapus, pemberitahuan } from "@/lib/swal";

const kosong = { kode: "", jenis: "potongan", nilai: "", kuota: "10", aktif: true };

export default function AdminVoucherPage() {
  const [data, setData] = useState([]);
  const [kata, setKata] = useState("");
  const [jenis, setJenis] = useState("semua");
  const [form, setForm] = useState(null);
  const [error, setError] = useState("");

  function muat() {
    api("/pengelola/voucher")
      .then((res) => setData(res.data))
      .catch((err) => setError(err.message));
  }

  useEffect(() => {
    muat();
  }, []);

  async function simpan(event) {
    event.preventDefault();
    setError("");
    const body = {
      kode: form.kode,
      jenis: form.jenis,
      nilai: form.nilai,
      kuota: form.kuota,
      aktif: Boolean(form.aktif),
    };
    try {
      if (form.id) {
        await api(`/pengelola/voucher/${form.id}`, { method: "PUT", body });
      } else {
        await api("/pengelola/voucher", { method: "POST", body });
      }
      setForm(null);
      muat();
      pemberitahuan("Voucher tersimpan");
    } catch (err) {
      const detail = Object.values(err.errors ?? {}).flat().join(" ");
      setError(detail || err.message);
    }
  }

  async function hapus(item) {
    const jawab = await konfirmasiHapus(item.kode);
    if (!jawab.isConfirmed) return;
    try {
      await api(`/pengelola/voucher/${item.id}`, { method: "DELETE" });
      muat();
      pemberitahuan("Voucher dihapus");
    } catch (err) {
      pemberitahuan(err.message, "error");
    }
  }

  const tampil = data.filter((item) => {
    const teks = item.kode.toLowerCase().includes(kata.toLowerCase());
    return teks && (jenis === "semua" || item.jenis === jenis);
  });

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <h1 className="text-3xl">Voucher</h1>
        <button type="button" className="rounded-xl bg-[#0e2a47] px-4 py-2.5 text-sm font-semibold text-white" onClick={() => { setError(""); setForm(kosong); }}>
          Voucher baru
        </button>
      </div>
      <div className="grid gap-3 md:grid-cols-2">
        <input className="field" placeholder="Cari kode" value={kata} onChange={(e) => setKata(e.target.value)} />
        <select className="field" value={jenis} onChange={(e) => setJenis(e.target.value)}>
          <option value="semua">Semua jenis</option>
          <option value="potongan">Potongan rupiah</option>
          <option value="persen">Persen</option>
        </select>
      </div>
      {error && !form ? <p className="text-sm text-[#d7263d]">{error}</p> : null}
      <div className="overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table className="tabel">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Jenis</th>
              <th>Nilai</th>
              <th>Terpakai</th>
              <th>Kuota</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {tampil.map((item) => (
              <tr key={item.id}>
                <td>{item.kode}</td>
                <td>{item.jenis === "persen" ? "Persen" : "Potongan"}</td>
                <td>{item.jenis === "persen" ? `${Number(item.nilai)}%` : rupiah(item.nilai)}</td>
                <td>{item.terpakai}</td>
                <td>{item.kuota}</td>
                <td>{item.aktif ? "Aktif" : "Nonaktif"}</td>
                <td className="whitespace-nowrap">
                  <button
                    type="button"
                    className="font-semibold text-[#0e2a47]"
                    onClick={() => {
                      setError("");
                      setForm({ ...item, nilai: String(Number(item.nilai)), kuota: String(item.kuota) });
                    }}
                  >
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
                <td colSpan="7">Tidak ada voucher.</td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
      {form ? (
        <Modal judul={form.id ? "Ubah voucher" : "Voucher baru"} onTutup={() => setForm(null)}>
          <form onSubmit={simpan} className="space-y-3">
            <label className="block text-sm">
              Kode
              <input className="field" required value={form.kode} onChange={(e) => setForm({ ...form, kode: e.target.value })} />
            </label>
            <label className="block text-sm">
              Jenis
              <select className="field" value={form.jenis} onChange={(e) => setForm({ ...form, jenis: e.target.value })}>
                <option value="potongan">Potongan rupiah</option>
                <option value="persen">Persen</option>
              </select>
            </label>
            <label className="block text-sm">
              Nilai
              <input className="field" type="number" required value={form.nilai} onChange={(e) => setForm({ ...form, nilai: e.target.value })} />
            </label>
            <label className="block text-sm">
              Kuota
              <input className="field" type="number" required value={form.kuota} onChange={(e) => setForm({ ...form, kuota: e.target.value })} />
            </label>
            {form.id ? (
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={Boolean(form.aktif)} onChange={(e) => setForm({ ...form, aktif: e.target.checked })} />
                Aktif
              </label>
            ) : null}
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
