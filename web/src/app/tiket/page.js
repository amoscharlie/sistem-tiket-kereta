"use client";

import { Suspense, useEffect, useRef, useState } from "react";
import { useSearchParams } from "next/navigation";
import TiketCetak from "@/components/TiketCetak";
import { api } from "@/lib/api";

function HalamanTiket() {
  const awal = useSearchParams().get("kode") ?? "";
  const cetak = useSearchParams().get("cetak") === "1";
  const [kode, setKode] = useState(awal);
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const [kamera, setKamera] = useState(false);
  const videoRef = useRef(null);
  const sudahCetak = useRef(false);

  async function muat(nilai) {
    const cari = (nilai || kode).trim();
    if (!cari) return;
    setError("");
    setData(null);
    try {
      const res = await api(`/tiket/${encodeURIComponent(cari)}`);
      setData(res.data);
    } catch (err) {
      setError(err.message);
    }
  }

  useEffect(() => {
    if (awal) muat(awal);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [awal]);

  useEffect(() => {
    if (!data || !cetak || sudahCetak.current) return;
    sudahCetak.current = true;
    const timer = setTimeout(() => window.print(), 500);
    return () => clearTimeout(timer);
  }, [data, cetak]);

  useEffect(() => {
    if (!kamera || !videoRef.current) return undefined;
    let berhenti = false;
    let stream;
    const video = videoRef.current;

    async function scan() {
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
        if (berhenti) return;
        video.srcObject = stream;
        await video.play();
        if (!("BarcodeDetector" in window)) {
          setError("Pemindaian QR tidak didukung di browser ini. Masukkan kode pesanan.");
          return;
        }
        const detektor = new window.BarcodeDetector({ formats: ["qr_code"] });
        const ulang = async () => {
          if (berhenti) return;
          try {
            const kodeQr = await detektor.detect(video);
            const isi = kodeQr[0]?.rawValue ?? "";
            const ketemu = isi.match(/kode=([^&]+)/i)?.[1] || isi;
            if (ketemu) {
              setKode(decodeURIComponent(ketemu));
              setKamera(false);
              muat(decodeURIComponent(ketemu));
              return;
            }
          } catch {
            // frame kosong
          }
          requestAnimationFrame(ulang);
        };
        ulang();
      } catch (err) {
        setError(err.message || "Kamera tidak bisa dibuka.");
      }
    }

    scan();
    return () => {
      berhenti = true;
      stream?.getTracks().forEach((track) => track.stop());
    };
  }, [kamera]);

  return (
    <div className="mx-auto max-w-xl space-y-4 px-4 py-8">
      <form
        className="no-print space-y-3 rounded-2xl bg-white p-4 shadow-sm"
        onSubmit={(event) => {
          event.preventDefault();
          muat(kode);
        }}
      >
        <h1 className="text-3xl">Cetak tiket</h1>
        <p className="text-sm text-[#5d6b7c]">Pindai QR atau masukkan kode pesanan. Tiket yang sudah lunas bisa dicetak.</p>
        <label className="block text-sm">
          Kode pesanan
          <input className="field" value={kode} onChange={(e) => setKode(e.target.value)} />
        </label>
        <div className="flex gap-2">
          <button className="rounded-xl bg-[#0e2a47] px-4 py-2.5 text-sm font-semibold text-white" type="submit">
            Tampilkan
          </button>
          <button className="rounded-xl border border-[#d5deea] px-4 py-2.5 text-sm font-semibold" type="button" onClick={() => setKamera((nilai) => !nilai)}>
            {kamera ? "Tutup kamera" : "Pindai QR"}
          </button>
        </div>
        {kamera ? <video ref={videoRef} className="w-full rounded-xl bg-black" muted playsInline /> : null}
        {error ? <p className="text-sm text-[#d7263d]">{error}</p> : null}
      </form>
      {data ? (
        <>
          <TiketCetak data={data} />
          <button type="button" className="no-print rounded-xl bg-[#d7263d] px-4 py-2.5 text-sm font-semibold text-white" onClick={() => window.print()}>
            Cetak sendiri
          </button>
        </>
      ) : null}
    </div>
  );
}

export default function TiketPage() {
  return (
    <Suspense fallback={<p className="px-4 py-8">Memuat tiket...</p>}>
      <HalamanTiket />
    </Suspense>
  );
}
