"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import Ikon from "@/components/Ikon";
import { api } from "@/lib/api";
import { LABEL_STATUS, jam, rupiah } from "@/lib/format";
import { konfirmasi, pemberitahuan } from "@/lib/swal";

function HitungMundur({ sampai }) {
  const [teks, setTeks] = useState("");

  useEffect(() => {
    function hitung() {
      const sisa = new Date(sampai) - Date.now();
      if (sisa <= 0) {
        setTeks("Waktu bayar habis");
        return;
      }
      const totalDetik = Math.floor(sisa / 1000);
      const jamSisa = Math.floor(totalDetik / 3600);
      const menit = Math.floor((totalDetik % 3600) / 60);
      const detik = totalDetik % 60;
      setTeks(jamSisa > 0 ? `${jamSisa} jam ${menit} menit` : `${menit} menit ${detik} detik`);
    }
    hitung();
    const timer = setInterval(hitung, 1000);
    return () => clearInterval(timer);
  }, [sampai]);

  return teks;
}

function muatSnap(clientKey) {
  if (window.snap) return Promise.resolve();
  return new Promise((resolve, reject) => {
    const script = document.createElement("script");
    script.src = "https://app.sandbox.midtrans.com/snap/snap.js";
    script.dataset.clientKey = clientKey;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error("Snap Midtrans gagal dimuat."));
    document.body.appendChild(script);
  });
}

export default function DetailPesananPage() {
  const { id } = useParams();
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const [pesan, setPesan] = useState("");

  function muat() {
    api(`/pesanan/${id}`)
      .then((res) => setData(res.data))
      .catch((err) => setError(err.message));
  }

  useEffect(() => {
    muat();
  }, [id]);

  async function bayarMidtrans() {
    setError("");
    setPesan("");
    try {
      const res = await api(`/pesanan/${id}/midtrans`, { method: "POST" });
      await muatSnap(res.client_key);
      window.snap.pay(res.snap_token, {
        onSuccess: async () => {
          const cek = await api(`/pesanan/${id}/midtrans/cek`, { method: "POST" });
          setData(cek.data);
          setPesan("Pembayaran Midtrans berhasil.");
        },
        onPending: () => setPesan("Pembayaran Midtrans masih diproses. Cek lagi nanti."),
        onError: () => setError("Pembayaran Midtrans gagal."),
      });
    } catch (err) {
      setError(err.message);
    }
  }

  async function unggah(event) {
    event.preventDefault();
    const file = event.target.bukti.files[0];
    if (!file) return;
    const form = new FormData();
    form.append("bukti", file);
    setError("");
    try {
      const res = await api(`/pesanan/${id}/bukti`, { method: "POST", form });
      setData(res.data);
      setPesan("Bukti terkirim. Menunggu admin.");
    } catch (err) {
      setError(err.message);
    }
  }

  async function batalkan() {
    const jawab = await konfirmasi("Batalkan pesanan?", "Kursi akan dilepas dan pesanan tidak bisa dibayar lagi.");
    if (!jawab.isConfirmed) return;
    setError("");
    try {
      const res = await api(`/pesanan/${id}/batal`, { method: "POST" });
      setData(res.data);
      pemberitahuan("Pesanan dibatalkan");
    } catch (err) {
      setError(err.message);
    }
  }

  if (!data) return <p className="mx-auto max-w-3xl px-4 py-8">{error || "Memuat pesanan..."}</p>;

  const bolehUnggah = data.status === "menunggu_bayar";
  const bolehBatal = data.status === "menunggu_bayar" || data.status === "menunggu_verifikasi";

  return (
    <div className="mx-auto max-w-3xl space-y-4 px-4 py-8">
      <div className="space-y-4 rounded-2xl bg-white p-6 shadow-sm">
      <h1 className="text-4xl">{LABEL_STATUS[data.status] ?? data.status}</h1>
      {data.status === "lunas" ? (
        <div className="rounded-xl bg-[#e7f6ec] p-4">
          <p className="font-semibold">Pembayaran berhasil. Tiket siap dicetak.</p>
          <Link href={`/tiket?kode=${data.kode}&cetak=1`} className="mt-3 inline-block rounded-xl bg-[#0e2a47] px-5 py-3 font-semibold text-white">
            Cetak tiket
          </Link>
        </div>
      ) : (
        <p className="text-sm text-[#5d6b7c]">Tiket bisa dicetak setelah pembayaran berhasil.</p>
      )}
      <p>
        {data.kereta}, {data.kelas}. {data.asal} ke {data.tujuan}.
      </p>
      <p className="text-sm text-[#5c6570]">{jam(data.berangkat_at)}</p>
      <p className="text-xl">{rupiah(data.total)}</p>
      <ul className="text-sm">
        {data.penumpang.map((orang, index) => (
          <li key={`${orang.arah}-${orang.kursi}-${index}`}>
            {orang.gratis ? `${orang.nama} · bayi ${orang.umur ?? ""} tahun · gratis, tanpa kursi` : `${orang.nama}${orang.nik ? ` · NIK ${orang.nik}` : ""} · ${orang.arah} · kursi ${orang.kursi}`}
          </li>
        ))}
      </ul>
      {data.status === "menunggu_bayar" ? (
        <div className="flex items-center gap-2 rounded-xl bg-[#fff6e8] px-4 py-3 text-sm">
          <Ikon nama="jam" />
          <p>
            Sisa waktu bayar <strong><HitungMundur sampai={data.batas_bayar_at} /></strong>. Transfer sesuai nominal, lalu unggah foto bukti.
          </p>
        </div>
      ) : null}
      {data.pembayaran?.catatan ? <p className="text-sm text-[#b8432f]">{data.pembayaran.catatan}</p> : null}
      {bolehUnggah ? (
        <form onSubmit={unggah} className="space-y-3">
          <input name="bukti" type="file" accept="image/*" required />
          <button className="rounded-xl bg-[#0e2a47] px-4 py-2.5 text-sm font-semibold text-white" type="submit">
            Unggah bukti
          </button>
        </form>
      ) : null}
      {data.pembayaran?.bukti_url ? (
        <img src={data.pembayaran.bukti_url} alt="Bukti transfer" className="max-h-64 rounded-lg border border-[#d9d1c3]" />
      ) : null}
      {data.potongan && Number(data.potongan) > 0 ? (
        <p className="text-sm">Voucher {data.voucher} memotong {rupiah(data.potongan)}.</p>
      ) : null}
      {data.pulang ? (
        <p className="text-sm">
          Pulang: {data.pulang.kereta}, {data.pulang.kelas}. {data.pulang.asal} ke {data.pulang.tujuan}. {jam(data.pulang.berangkat_at)}
        </p>
      ) : null}
      {bolehUnggah ? (
        <button type="button" className="rounded-xl border border-[#0e2a47] px-4 py-2.5 text-sm font-semibold" onClick={bayarMidtrans}>
          Bayar Midtrans sandbox
        </button>
      ) : null}
      {bolehBatal ? (
        <button type="button" className="text-sm font-semibold text-[#d7263d]" onClick={batalkan}>
          Batalkan pesanan
        </button>
      ) : null}
      {pesan ? <p className="text-sm">{pesan}</p> : null}
      {error ? <p className="text-sm text-[#b8432f]">{error}</p> : null}
      </div>
    </div>
  );
}
