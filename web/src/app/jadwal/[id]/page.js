"use client";

import Link from "next/link";
import { useParams, useRouter, useSearchParams } from "next/navigation";
import { Suspense, useEffect, useState } from "react";
import { ambilSesi, api } from "@/lib/api";
import { jam, rupiah } from "@/lib/format";

function Peta({ jadwal, kelas, pilihan, onPilih }) {
  const baris = [...new Set(kelas?.kursi.map((kursi) => kursi.baris) ?? [])];

  function tombolKursi(kursi) {
    const aktif = pilihan.some((item) => item.kursi_id === kursi.id);
    return (
      <button
        key={kursi.id}
        type="button"
        disabled={!kursi.tersedia}
        onClick={() => onPilih(kursi)}
        className={`h-12 w-12 rounded-lg text-sm font-semibold ${
          aktif ? "bg-[#d7263d] text-white" : kursi.tersedia ? "bg-white text-[#0e2a47] shadow-sm" : "bg-[#d5deea] text-[#8aa0b8]"
        }`}
      >
        {kursi.kode}
      </button>
    );
  }

  return (
    <section>
      <p className="text-sm font-medium text-[#d7263d]">{jadwal.kereta}</p>
      <h2 className="mt-1 text-3xl">
        {jadwal.asal} ke {jadwal.tujuan}
      </h2>
      <p className="mt-1 text-[#5d6b7c]">
        {jam(jadwal.berangkat_at)} · {kelas?.nama} · {rupiah(kelas?.harga)}
      </p>
      <div className="mt-6 rounded-3xl bg-[#dfe7f0] p-5">
        <div className="space-y-2">
          {baris.map((nomor) => {
            const diBaris = kelas.kursi.filter((kursi) => kursi.baris === nomor);
            return (
              <div key={nomor} className="flex items-center justify-center gap-4">
                <div className="grid grid-cols-2 gap-2">{diBaris.filter((kursi) => ["A", "B"].includes(kursi.kolom)).map(tombolKursi)}</div>
                <span className="w-6 text-center text-xs text-[#8aa0b8]">{nomor}</span>
                <div className="grid grid-cols-2 gap-2">{diBaris.filter((kursi) => ["C", "D"].includes(kursi.kolom)).map(tombolKursi)}</div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

function JadwalIsi() {
  const { id } = useParams();
  const cari = useSearchParams();
  const pulangId = cari.get("pulang");
  const kelasQuery = Number(cari.get("kelas") || 0);
  const kelasPulangQuery = Number(cari.get("kelasPulang") || 0);
  const dewasa = Math.min(4, Math.max(1, Number(cari.get("dewasa") || 1)));
  const jumlahBayi = Math.min(dewasa, Math.max(0, Number(cari.get("bayi") || 0)));
  const router = useRouter();
  const [jadwal, setJadwal] = useState(null);
  const [pulang, setPulang] = useState(null);
  const [kelasId, setKelasId] = useState(null);
  const [kelasPulangId, setKelasPulangId] = useState(null);
  const [pilihan, setPilihan] = useState([]);
  const [pilihanPulang, setPilihanPulang] = useState([]);
  const [bayi, setBayi] = useState(() => Array.from({ length: jumlahBayi }, () => ({ nama: "", umur: "" })));
  const [voucher, setVoucher] = useState("");
  const [error, setError] = useState("");
  const [mengirim, setMengirim] = useState(false);

  useEffect(() => {
    api(`/jadwal/${id}`)
      .then((res) => {
        setJadwal(res.data);
        const cocok = res.data.kelas.find((item) => item.id === kelasQuery);
        setKelasId((cocok ?? res.data.kelas[0])?.id ?? null);
      })
      .catch((err) => setError(err.message));
    if (pulangId) {
      api(`/jadwal/${pulangId}`)
        .then((res) => {
          setPulang(res.data);
          const cocok = res.data.kelas.find((item) => item.id === kelasPulangQuery);
          setKelasPulangId((cocok ?? res.data.kelas[0])?.id ?? null);
        })
        .catch((err) => setError(err.message));
    }
  }, [id, pulangId, kelasQuery, kelasPulangQuery]);

  function pilihKursi(kursi, kelasAktif, sekarang, setSekarang) {
    if (!kursi.tersedia) return;
    setSekarang((daftar) => {
      const ada = daftar.find((item) => item.kursi_id === kursi.id);
      if (ada) return daftar.filter((item) => item.kursi_id !== kursi.id);
      if (daftar.length >= dewasa) return daftar;
      if (daftar.some((item) => item.kelas_id !== kelasAktif)) {
        return [{ kursi_id: kursi.id, kode: kursi.kode, kelas_id: kelasAktif, nama: "", umur: "", nik: "" }];
      }
      return [...daftar, { kursi_id: kursi.id, kode: kursi.kode, kelas_id: kelasAktif, nama: "", umur: "", nik: "" }];
    });
  }

  async function pesan(event) {
    event.preventDefault();
    const lanjut = `${window.location.pathname}${window.location.search}`;
    if (!ambilSesi()) {
      router.push(`/masuk?lanjut=${encodeURIComponent(lanjut)}`);
      return;
    }
    if (pilihan.length !== dewasa || pilihan.some((item) => Number(item.umur) < 3)) {
      setError("Usia 3 tahun ke atas wajib satu kursi. Isi umur minimal 3.");
      return;
    }
    if (pilihan.some((item) => !/^\d{16}$/.test(item.nik))) {
      setError("NIK penumpang harus 16 digit.");
      return;
    }
    if (bayi.length !== jumlahBayi || bayi.some((item) => item.umur === "" || Number(item.umur) > 2)) {
      setError("Bayi di bawah 3 tahun gratis dan tidak mendapat kursi.");
      return;
    }
    if (pulang && pilihanPulang.length !== pilihan.length) {
      setError("Jumlah kursi pergi dan pulang harus sama.");
      return;
    }
    setMengirim(true);
    setError("");
    try {
      const res = await api("/pesanan", {
        method: "POST",
        body: {
          jadwal_id: Number(id),
          kelas_id: kelasId,
          jadwal_pulang_id: pulang ? Number(pulangId) : undefined,
          kelas_pulang_id: pulang ? kelasPulangId : undefined,
          voucher: voucher || undefined,
          penumpang: pilihan.map((item, index) => ({
            kursi_id: item.kursi_id,
            kursi_pulang_id: pulang ? pilihanPulang[index].kursi_id : undefined,
            nama: item.nama,
            umur: Number(item.umur),
            nik: item.nik,
          })),
          bayi: bayi.map((item) => ({ nama: item.nama, umur: Number(item.umur) })),
        },
      });
      router.push(`/pesanan/${res.data.id}`);
    } catch (err) {
      const detail = Object.values(err.errors ?? {}).flat().join(" ");
      setError(detail || err.message);
      setMengirim(false);
    }
  }

  if (!jadwal || (pulangId && !pulang)) {
    return <p className="mx-auto max-w-5xl px-4 py-8">{error || "Memuat jadwal..."}</p>;
  }

  return (
    <div className="mx-auto grid max-w-5xl gap-6 px-4 py-8 lg:grid-cols-[1.1fr_0.9fr]">
      <div className="space-y-8">
        <Link href="/" className="text-sm text-[#35506e]">
          Kembali ke pencarian
        </Link>
        <Peta jadwal={jadwal} kelas={jadwal.kelas.find((item) => item.id === kelasId)} pilihan={pilihan} onPilih={(kursi) => pilihKursi(kursi, kelasId, pilihan, setPilihan)} />
        {pulang ? (
          <Peta
            jadwal={pulang}
            kelas={pulang.kelas.find((item) => item.id === kelasPulangId)}
            pilihan={pilihanPulang}
            onPilih={(kursi) => pilihKursi(kursi, kelasPulangId, pilihanPulang, setPilihanPulang)}
          />
        ) : null}
      </div>

      <form onSubmit={pesan} className="h-fit space-y-3 rounded-2xl bg-white p-5 shadow-sm">
        <h2 className="text-2xl">Data penumpang</h2>
        <p className="text-sm text-[#5d6b7c]">
          {dewasa} dewasa · {jumlahBayi} bayi. Usia di bawah 3 tahun gratis dan tidak mendapat kursi.
        </p>
        <p className="text-sm font-semibold">
          {rupiah((Number(jadwal.kelas.find((item) => item.id === kelasId)?.harga ?? 0) + Number(pulang?.kelas.find((item) => item.id === kelasPulangId)?.harga ?? 0)) * dewasa)}
          {" "}
          untuk {dewasa} tiket
        </p>
        {pilihan.length < dewasa ? <p className="text-sm text-[#5d6b7c]">Pilih {dewasa} kursi di denah.</p> : null}
        {pilihan.map((item, index) => (
          <div key={item.kursi_id} className="space-y-2 rounded-xl bg-[#f6f8fb] p-3">
            <p className="text-sm font-medium">
              Dewasa · pergi {item.kode}
              {pilihanPulang[index] ? ` · pulang ${pilihanPulang[index].kode}` : ""}
            </p>
            <input
              required
              className="field mt-0"
              placeholder="Nama penumpang"
              value={item.nama}
              onChange={(event) => {
                const salinan = [...pilihan];
                salinan[index] = { ...item, nama: event.target.value };
                setPilihan(salinan);
              }}
            />
            <div className="grid grid-cols-[1fr_5.5rem] gap-2">
              <input
                required
                className="field mt-0"
                inputMode="numeric"
                maxLength={16}
                placeholder="NIK 16 digit"
                value={item.nik}
                onChange={(event) => {
                  const salinan = [...pilihan];
                  salinan[index] = { ...item, nik: event.target.value.replace(/\D/g, "").slice(0, 16) };
                  setPilihan(salinan);
                }}
              />
              <input
                required
                className="field mt-0"
                type="number"
                min="3"
                max="120"
                placeholder="Umur"
                value={item.umur}
                onChange={(event) => {
                  const salinan = [...pilihan];
                  salinan[index] = { ...item, umur: event.target.value };
                  setPilihan(salinan);
                }}
              />
            </div>
          </div>
        ))}
        {bayi.map((item, index) => (
          <div key={`bayi-${index}`} className="grid grid-cols-[1fr_5.5rem] gap-2">
            <label className="block text-sm font-medium">
              Bayi {index + 1} · gratis
              <input
                required
                className="field"
                placeholder="Nama bayi"
                value={item.nama}
                onChange={(event) => {
                  const salinan = [...bayi];
                  salinan[index] = { ...item, nama: event.target.value };
                  setBayi(salinan);
                }}
              />
            </label>
            <label className="block text-sm font-medium">
              Umur
              <input
                required
                className="field"
                type="number"
                min="0"
                max="2"
                value={item.umur}
                onChange={(event) => {
                  const salinan = [...bayi];
                  salinan[index] = { ...item, umur: event.target.value };
                  setBayi(salinan);
                }}
              />
            </label>
          </div>
        ))}
        <label className="block text-sm font-medium">
          Voucher (Opsional)
          <input className="field" placeholder="KERETA10 atau HEMAT50" value={voucher} onChange={(e) => setVoucher(e.target.value)} />
        </label>
        {error ? <p className="text-sm text-[#d7263d]">{error}</p> : null}
        <button className="w-full rounded-xl bg-[#d7263d] px-4 py-3 font-semibold text-white disabled:opacity-50" disabled={pilihan.length !== dewasa || mengirim} type="submit">
          {mengirim ? "Menyimpan..." : "Lanjut pesan"}
        </button>
      </form>
    </div>
  );
}

export default function JadwalPage() {
  return (
    <Suspense fallback={<p className="mx-auto max-w-5xl px-4 py-8">Memuat jadwal...</p>}>
      <JadwalIsi />
    </Suspense>
  );
}
