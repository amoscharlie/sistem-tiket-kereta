"use client";

import { useEffect, useState } from "react";
import Modal from "@/components/Modal";
import { api } from "@/lib/api";
import { jam, keInputWaktu, rupiah, tanggalWib } from "@/lib/format";
import { konfirmasiHapus, pemberitahuan } from "@/lib/swal";

const kosong = {
  kereta_id: "",
  stasiun_asal_id: "",
  stasiun_tujuan_id: "",
  berangkat_at: "",
  tiba_at: "",
  harga: {},
};

export default function AdminJadwalPage() {
  const [master, setMaster] = useState(null);
  const [jadwal, setJadwal] = useState([]);
  const [keretaId, setKeretaId] = useState("");
  const [asalId, setAsalId] = useState("");
  const [tujuanId, setTujuanId] = useState("");
  const [tanggal, setTanggal] = useState("");
  const [form, setForm] = useState(null);
  const [error, setError] = useState("");

  function muat() {
    Promise.all([api("/pengelola/master"), api("/pengelola/jadwal")])
      .then(([dataMaster, dataJadwal]) => {
        setMaster(dataMaster);
        setJadwal(dataJadwal.data);
      })
      .catch((err) => setError(err.message));
  }

  useEffect(() => {
    muat();
  }, []);

  function bukaBaru() {
    const harga = {};
    master?.kelas.forEach((item) => {
      harga[item.id] = "";
    });
    setError("");
    setForm({ ...kosong, harga });
  }

  function bukaUbah(item) {
    const harga = {};
    item.kelas.forEach((kelas) => {
      harga[kelas.kelas_id] = String(Number(kelas.harga));
    });
    setError("");
    setForm({
      id: item.id,
      kereta_id: String(item.kereta_id),
      stasiun_asal_id: String(item.stasiun_asal_id),
      stasiun_tujuan_id: String(item.stasiun_tujuan_id),
      berangkat_at: keInputWaktu(item.berangkat_at),
      tiba_at: keInputWaktu(item.tiba_at),
      harga,
    });
  }

  async function simpan(event) {
    event.preventDefault();
    setError("");
    const kelas = (master?.kelas ?? [])
      .filter((item) => form.harga[item.id])
      .map((item) => ({ kelas_id: item.id, harga: Number(form.harga[item.id]) }));
    const body = {
      kereta_id: Number(form.kereta_id),
      stasiun_asal_id: Number(form.stasiun_asal_id),
      stasiun_tujuan_id: Number(form.stasiun_tujuan_id),
      berangkat_at: form.berangkat_at,
      tiba_at: form.tiba_at,
      kelas,
    };
    try {
      if (form.id) {
        await api(`/pengelola/jadwal/${form.id}`, { method: "PUT", body });
      } else {
        await api("/pengelola/jadwal", { method: "POST", body });
      }
      setForm(null);
      muat();
      pemberitahuan("Jadwal tersimpan");
    } catch (err) {
      const detail = Object.values(err.errors ?? {}).flat().join(" ");
      setError(detail || err.message);
    }
  }

  async function hapus(item) {
    const jawab = await konfirmasiHapus(`${item.kereta} · ${item.asal} ke ${item.tujuan}`);
    if (!jawab.isConfirmed) return;
    try {
      await api(`/pengelola/jadwal/${item.id}`, { method: "DELETE" });
      muat();
      pemberitahuan("Jadwal dihapus");
    } catch (err) {
      pemberitahuan(err.message, "error");
    }
  }

  if (!master) return <p>{error || "Memuat..."}</p>;

  const tampil = jadwal.filter((item) => {
    const cocokKereta = !keretaId || String(item.kereta_id) === keretaId;
    const cocokAsal = !asalId || String(item.stasiun_asal_id) === asalId;
    const cocokTujuan = !tujuanId || String(item.stasiun_tujuan_id) === tujuanId;
    const cocokHari = !tanggal || tanggalWib(item.berangkat_at) === tanggal;
    return cocokKereta && cocokAsal && cocokTujuan && cocokHari;
  });

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <h1 className="text-3xl">Jadwal</h1>
        <button type="button" className="rounded-xl bg-[#0e2a47] px-4 py-2.5 text-sm font-semibold text-white" onClick={bukaBaru}>
          Jadwal baru
        </button>
      </div>
      <div className="grid gap-3 md:grid-cols-4">
        <label className="text-sm">
          Kereta
          <select className="field" value={keretaId} onChange={(e) => setKeretaId(e.target.value)}>
            <option value="">Semua kereta</option>
            {master.kereta.map((item) => (
              <option key={item.id} value={item.id}>
                {item.nama}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          Stasiun asal
          <select className="field" value={asalId} onChange={(e) => setAsalId(e.target.value)}>
            <option value="">Semua asal</option>
            {master.stasiun.map((item) => (
              <option key={item.id} value={item.id}>
                {item.kota} · {item.nama}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          Stasiun tujuan
          <select className="field" value={tujuanId} onChange={(e) => setTujuanId(e.target.value)}>
            <option value="">Semua tujuan</option>
            {master.stasiun.map((item) => (
              <option key={item.id} value={item.id}>
                {item.kota} · {item.nama}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          Tanggal berangkat
          <input className="field" type="date" value={tanggal} onChange={(e) => setTanggal(e.target.value)} />
        </label>
      </div>
      {error && !form ? <p className="text-sm text-[#d7263d]">{error}</p> : null}
      <div className="overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table className="tabel">
          <thead>
            <tr>
              <th>Kereta</th>
              <th>Asal</th>
              <th>Tujuan</th>
              <th>Berangkat</th>
              <th>Harga</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {tampil.map((item) => (
              <tr key={item.id}>
                <td>{item.kereta}</td>
                <td>{item.asal}</td>
                <td>{item.tujuan}</td>
                <td className="whitespace-nowrap">{jam(item.berangkat_at)}</td>
                <td>{item.kelas.map((kelas) => `${kelas.nama} ${rupiah(kelas.harga)}`).join(" · ")}</td>
                <td className="whitespace-nowrap">
                  <button type="button" className="font-semibold text-[#0e2a47]" onClick={() => bukaUbah(item)}>
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
                <td colSpan="6">Tidak ada jadwal.</td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
      {form ? (
        <Modal judul={form.id ? "Ubah jadwal" : "Jadwal baru"} onTutup={() => setForm(null)}>
          <form onSubmit={simpan} className="space-y-3">
            <label className="block text-sm">
              Kereta
              <select className="field" required value={form.kereta_id} onChange={(e) => setForm({ ...form, kereta_id: e.target.value })}>
                <option value="">Pilih</option>
                {master.kereta.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.nama}
                  </option>
                ))}
              </select>
            </label>
            <label className="block text-sm">
              Asal
              <select className="field" required value={form.stasiun_asal_id} onChange={(e) => setForm({ ...form, stasiun_asal_id: e.target.value })}>
                <option value="">Pilih</option>
                {master.stasiun.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.kota} · {item.nama}
                  </option>
                ))}
              </select>
            </label>
            <label className="block text-sm">
              Tujuan
              <select className="field" required value={form.stasiun_tujuan_id} onChange={(e) => setForm({ ...form, stasiun_tujuan_id: e.target.value })}>
                <option value="">Pilih</option>
                {master.stasiun.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.kota} · {item.nama}
                  </option>
                ))}
              </select>
            </label>
            <label className="block text-sm">
              Berangkat
              <input className="field" type="datetime-local" required value={form.berangkat_at} onChange={(e) => setForm({ ...form, berangkat_at: e.target.value })} />
            </label>
            <label className="block text-sm">
              Tiba
              <input className="field" type="datetime-local" required value={form.tiba_at} onChange={(e) => setForm({ ...form, tiba_at: e.target.value })} />
            </label>
            {master.kelas.map((item) => (
              <label key={item.id} className="block text-sm">
                Harga {item.nama}
                <input
                  className="field"
                  type="number"
                  min="1"
                  required
                  value={form.harga[item.id] ?? ""}
                  onChange={(e) => setForm({ ...form, harga: { ...form.harga, [item.id]: e.target.value } })}
                />
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
