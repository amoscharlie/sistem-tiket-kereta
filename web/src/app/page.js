"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import Ikon from "@/components/Ikon";
import { ambilSesi, api } from "@/lib/api";
import { durasi, hariIni, pukul, rupiah, tanggalPanjang } from "@/lib/format";

const RUTE_POPULER = [
  ["GMR", "BD", "Jakarta → Bandung"],
  ["BD", "GMR", "Bandung → Jakarta"],
  ["GMR", "YK", "Jakarta → Yogyakarta"],
  ["GMR", "SGU", "Jakarta → Surabaya"],
  ["BD", "SLO", "Bandung → Solo"],
];

function Kartu({ item, sekaliJalan, pilihan, onPilih, dewasa, bayi }) {
  return item.kelas.map((kelas) => {
    const eksekutif = kelas.nama.toLowerCase().includes("eksekutif");
    const aktif = pilihan?.id === item.id && pilihan?.kelasId === kelas.id;
    const gaya = `block rounded-2xl p-5 text-left shadow-sm ${eksekutif ? "bg-[#fff8ec] ring-1 ring-[#e4c98a]" : "bg-white"} ${aktif ? "ring-2 ring-[#d7263d]" : ""}`;
    const isi = (
      <>
        <div className="flex items-center justify-between gap-3">
          <p className="text-sm font-medium text-[#d7263d]">{item.kereta}</p>
          <p className={`rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide ${eksekutif ? "bg-[#e4c98a] text-[#5c4310]" : "bg-[#e7eef6] text-[#35506e]"}`}>
            {kelas.nama}
          </p>
        </div>
        {item.alasan ? <p className="mt-2 text-xs font-semibold uppercase tracking-wide text-[#d7263d]">{item.alasan}</p> : null}
        <div className="mt-3 grid grid-cols-[auto_1fr_auto] items-center gap-4">
          <div>
            <p className="text-3xl font-semibold">{pukul(item.berangkat_at)}</p>
            <p className="text-sm text-[#5d6b7c]">{item.asal}</p>
          </div>
          <div className="text-center">
            <p className="text-xs text-[#5d6b7c]">{durasi(item.berangkat_at, item.tiba_at)}</p>
            <div className="mt-1 h-px bg-[#d5deea]" />
          </div>
          <div className="text-right">
            <p className="text-3xl font-semibold">{pukul(item.tiba_at)}</p>
            <p className="text-sm text-[#5d6b7c]">{item.tujuan}</p>
          </div>
        </div>
        <div className="mt-4 flex items-end justify-between gap-3">
          <p className="text-sm text-[#5d6b7c]">{kelas.sisa} kursi tersisa</p>
          <p className="text-2xl font-semibold text-[#0e2a47]">{rupiah(kelas.harga)}</p>
        </div>
      </>
    );
    if (sekaliJalan) {
      const query = new URLSearchParams({ kelas: String(kelas.id), dewasa: String(dewasa), bayi: String(bayi) });
      return (
        <Link key={`${item.id}-${kelas.id}`} href={`/jadwal/${item.id}?${query}`} className={gaya}>
          {isi}
        </Link>
      );
    }
    return (
      <button key={`${item.id}-${kelas.id}`} type="button" className={gaya} onClick={() => onPilih({ id: item.id, kelasId: kelas.id })}>
        {isi}
      </button>
    );
  });
}

export default function HomePage() {
  const router = useRouter();
  const [stasiun, setStasiun] = useState([]);
  const [asal, setAsal] = useState("");
  const [tujuan, setTujuan] = useState("");
  const [tanggal, setTanggal] = useState(hariIni());
  const [tanggalPulang, setTanggalPulang] = useState(hariIni());
  const [sekaliJalan, setSekaliJalan] = useState(true);
  const [dewasa, setDewasa] = useState(1);
  const [bayi, setBayi] = useState(0);
  const [hasil, setHasil] = useState(null);
  const [hasilPulang, setHasilPulang] = useState(null);
  const [rekomendasi, setRekomendasi] = useState([]);
  const [rekomendasiPulang, setRekomendasiPulang] = useState([]);
  const [urut, setUrut] = useState("pagi");
  const [saringKelas, setSaringKelas] = useState("semua");
  const [pergi, setPergi] = useState(null);
  const [pulang, setPulang] = useState(null);
  const [error, setError] = useState("");
  const [memuat, setMemuat] = useState(false);

  useEffect(() => {
    const peran = ambilSesi()?.user?.role;
    if (peran === "admin" || peran === "pengelola") router.replace("/admin");
  }, [router]);

  useEffect(() => {
    api("/stasiun")
      .then((res) => {
        setStasiun(res.data);
        const gambir = res.data.find((item) => item.kode === "GMR");
        const bandung = res.data.find((item) => item.kode === "BD");
        setAsal(String((gambir ?? res.data[0])?.id ?? ""));
        setTujuan(String((bandung ?? res.data[1])?.id ?? ""));
      })
      .catch((err) => setError(err.message));
  }, []);

  async function cari(event, asalId = asal, tujuanId = tujuan) {
    event?.preventDefault();
    setMemuat(true);
    setError("");
    setPergi(null);
    setPulang(null);
    try {
      const query = new URLSearchParams({ asal: asalId, tujuan: tujuanId, tanggal });
      if (!sekaliJalan) query.set("pulang", tanggalPulang);
      const res = await api(`/jadwal?${query}`);
      setHasil(res.data);
      setHasilPulang(sekaliJalan ? null : res.pulang);
      setRekomendasi(res.rekomendasi ?? []);
      setRekomendasiPulang(sekaliJalan ? [] : (res.rekomendasi_pulang ?? []));
    } catch (err) {
      setError(err.message);
      setHasil(null);
      setHasilPulang(null);
      setRekomendasi([]);
      setRekomendasiPulang([]);
    } finally {
      setMemuat(false);
    }
  }

  function pilihRute(kodeAsal, kodeTujuan) {
    const asalId = String(stasiun.find((item) => item.kode === kodeAsal)?.id ?? "");
    const tujuanId = String(stasiun.find((item) => item.kode === kodeTujuan)?.id ?? "");
    if (!asalId || !tujuanId) return;
    setAsal(asalId);
    setTujuan(tujuanId);
    cari(null, asalId, tujuanId);
  }

  function siapkan(daftar) {
    if (!daftar) return daftar;
    const hasilSaring = daftar
      .map((item) => ({
        ...item,
        kelas: item.kelas.filter((kelas) => saringKelas === "semua" || kelas.nama.toLowerCase() === saringKelas),
      }))
      .filter((item) => item.kelas.length > 0);
    hasilSaring.sort((a, b) => {
      if (urut === "murah") {
        const harga = (item) => Math.min(...item.kelas.map((kelas) => Number(kelas.harga)));
        return harga(a) - harga(b);
      }
      return new Date(a.berangkat_at) - new Date(b.berangkat_at);
    });
    return hasilSaring;
  }

  const namaAsal = stasiun.find((item) => String(item.id) === asal);
  const namaTujuan = stasiun.find((item) => String(item.id) === tujuan);

  return (
    <div>
      <section className="bg-[#0e2a47] text-white">
        <div className="mx-auto max-w-5xl px-4 pb-20 pt-10">
          <p className="text-sm text-white/70">Kereta antar kota</p>
          <h1 className="mt-2 max-w-xl text-4xl leading-tight sm:text-5xl">Pesan tiket kereta, pilih kursimu.</h1>
        </div>
      </section>

      <div className="mx-auto -mt-10 max-w-5xl px-4">
        <form onSubmit={cari} className="space-y-3 rounded-2xl bg-white p-4 shadow-[0_16px_40px_rgba(14,42,71,0.12)]">
          <div className="grid gap-3 md:grid-cols-2">
            <label className="text-sm font-medium">
              Stasiun asal
              <select className="field" value={asal} onChange={(e) => setAsal(e.target.value)}>
                {stasiun.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.kota} · {item.nama} ({item.kode})
                  </option>
                ))}
              </select>
            </label>
            <label className="text-sm font-medium">
              Stasiun tujuan
              <select className="field" value={tujuan} onChange={(e) => setTujuan(e.target.value)}>
                {stasiun.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.kota} · {item.nama} ({item.kode})
                  </option>
                ))}
              </select>
            </label>
          </div>
          <label className="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" checked={sekaliJalan} onChange={(e) => setSekaliJalan(e.target.checked)} />
            Sekali jalan
          </label>
          <div className="grid gap-3 md:grid-cols-2">
            <div className="rounded-xl border border-[#d5deea] p-3">
              <div className="flex items-center justify-between gap-3">
                <div>
                  <p className="text-sm font-medium">Dewasa</p>
                  <p className="text-xs text-[#5d6b7c]">3 tahun ke atas, 1 tiket</p>
                </div>
                <div className="flex items-center gap-3">
                  <button
                    type="button"
                    className="h-8 w-8 rounded-full border border-[#d5deea] text-lg"
                    onClick={() => {
                      const berikutnya = Math.max(1, dewasa - 1);
                      setDewasa(berikutnya);
                      setBayi((nilai) => Math.min(nilai, berikutnya));
                    }}
                  >
                    −
                  </button>
                  <span className="w-4 text-center font-semibold">{dewasa}</span>
                  <button type="button" className="h-8 w-8 rounded-full border border-[#d5deea] text-lg" onClick={() => setDewasa((nilai) => Math.min(4, nilai + 1))}>
                    +
                  </button>
                </div>
              </div>
            </div>
            <div className="rounded-xl border border-[#d5deea] p-3">
              <div className="flex items-center justify-between gap-3">
                <div>
                  <p className="text-sm font-medium">Bayi</p>
                  <p className="text-xs text-[#5d6b7c]">Di bawah 3 tahun, gratis, tanpa kursi</p>
                </div>
                <div className="flex items-center gap-3">
                  <button type="button" className="h-8 w-8 rounded-full border border-[#d5deea] text-lg" onClick={() => setBayi((nilai) => Math.max(0, nilai - 1))}>
                    −
                  </button>
                  <span className="w-4 text-center font-semibold">{bayi}</span>
                  <button type="button" className="h-8 w-8 rounded-full border border-[#d5deea] text-lg" onClick={() => setBayi((nilai) => Math.min(dewasa, nilai + 1))}>
                    +
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div className="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <label className="text-sm font-medium">
              Tanggal pergi
              <input className="field" type="date" value={tanggal} onChange={(e) => setTanggal(e.target.value)} />
            </label>
            {sekaliJalan ? (
              <span />
            ) : (
              <label className="text-sm font-medium">
                Tanggal pulang
                <input className="field" type="date" min={tanggal} value={tanggalPulang} onChange={(e) => setTanggalPulang(e.target.value)} />
              </label>
            )}
            <button className="rounded-xl bg-[#d7263d] px-5 py-3 font-semibold text-white disabled:opacity-60" type="submit" disabled={memuat}>
              {memuat ? "Mencari..." : "Cari tiket"}
            </button>
          </div>
        </form>

        <div className="mt-4 flex flex-wrap gap-2">
          {RUTE_POPULER.map(([kodeAsal, kodeTujuan, label]) => (
            <button
              key={label}
              type="button"
              onClick={() => pilihRute(kodeAsal, kodeTujuan)}
              className="rounded-full border border-[#d5deea] bg-white px-3 py-1.5 text-sm text-[#35506e]"
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      <section className="mx-auto max-w-5xl space-y-3 px-4 py-8">
        {error ? <p className="text-[#d7263d]">{error}</p> : null}
        {hasil ? (
          <div className="flex flex-wrap items-end justify-between gap-3">
            <h2 className="text-2xl">
              {namaAsal?.nama} ke {namaTujuan?.nama}
              <span className="mt-1 block text-base font-sans font-normal text-[#5d6b7c]">{tanggalPanjang(tanggal)}</span>
            </h2>
            <div className="flex flex-wrap gap-2">
              <label className="text-sm">
                <span className="mb-1 flex items-center gap-1 text-[#5d6b7c]">
                  <Ikon nama="saring" className="h-3.5 w-3.5" /> Kelas
                </span>
                <select className="field mt-0" value={saringKelas} onChange={(e) => setSaringKelas(e.target.value)}>
                  <option value="semua">Semua</option>
                  <option value="ekonomi">Ekonomi</option>
                  <option value="eksekutif">Eksekutif</option>
                </select>
              </label>
              <label className="text-sm">
                <span className="mb-1 flex items-center gap-1 text-[#5d6b7c]">
                  <Ikon nama="jadwal" className="h-3.5 w-3.5" /> Urutan
                </span>
                <select className="field mt-0" value={urut} onChange={(e) => setUrut(e.target.value)}>
                  <option value="pagi">Paling pagi</option>
                  <option value="murah">Paling murah</option>
                </select>
              </label>
            </div>
          </div>
        ) : (
          <p className="text-[#5d6b7c]">Cari rute atau pilih salah satu rute di atas.</p>
        )}
        {hasil?.length === 0 ? <p>Tidak ada kereta pada tanggal dan stasiun itu.</p> : null}
        {siapkan(hasil)?.map((item) => (
          <Kartu key={item.id} item={item} sekaliJalan={sekaliJalan} pilihan={pergi} onPilih={setPergi} dewasa={dewasa} bayi={bayi} />
        ))}
        {hasil?.length === 0 && rekomendasi.length > 0 ? (
          <>
            <h3 className="pt-2 text-xl">Jadwal lain</h3>
            <p className="text-sm text-[#5d6b7c]">Stasiun atau tanggal terdekat, dari semua stasiun.</p>
            {siapkan(rekomendasi).map((item) => (
              <Kartu key={`r-${item.id}`} item={item} sekaliJalan={sekaliJalan} pilihan={pergi} onPilih={setPergi} dewasa={dewasa} bayi={bayi} />
            ))}
          </>
        ) : null}

        {hasilPulang ? (
          <>
            <h2 className="pt-4 text-2xl">
              {namaTujuan?.nama} ke {namaAsal?.nama}
              <span className="mt-1 block text-base font-sans font-normal text-[#5d6b7c]">{tanggalPanjang(tanggalPulang)}</span>
            </h2>
            {hasilPulang.length === 0 ? <p>Tidak ada kereta pulang pada tanggal dan stasiun itu.</p> : null}
            {siapkan(hasilPulang).map((item) => (
              <Kartu key={item.id} item={item} sekaliJalan={false} pilihan={pulang} onPilih={setPulang} dewasa={dewasa} bayi={bayi} />
            ))}
            {hasilPulang.length === 0 && rekomendasiPulang.length > 0 ? (
              <>
                <h3 className="pt-2 text-xl">Jadwal pulang lain</h3>
                {siapkan(rekomendasiPulang).map((item) => (
                  <Kartu key={`rp-${item.id}`} item={item} sekaliJalan={false} pilihan={pulang} onPilih={setPulang} dewasa={dewasa} bayi={bayi} />
                ))}
              </>
            ) : null}
          </>
        ) : null}
        {!sekaliJalan && hasil ? (
          pergi && pulang ? (
            <Link href={`/jadwal/${pergi.id}?kelas=${pergi.kelasId}&pulang=${pulang.id}&kelasPulang=${pulang.kelasId}&dewasa=${dewasa}&bayi=${bayi}`} className="inline-block rounded-xl bg-[#d7263d] px-5 py-3 font-semibold text-white">
              Lanjut pilih kursi
            </Link>
          ) : (
            <p className="text-sm text-[#5d6b7c]">Pilih kelas untuk kereta pergi dan kereta pulang.</p>
          )
        ) : null}
      </section>
    </div>
  );
}
