"use client";

import { useEffect, useRef, useState } from "react";
import Ikon from "@/components/Ikon";
import { api } from "@/lib/api";
import { jam } from "@/lib/format";

export default function PintuPage() {
  const [kode, setKode] = useState("");
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const [kamera, setKamera] = useState(false);
  const videoRef = useRef(null);

  async function periksa(nilai) {
    const cari = (nilai || kode).trim();
    if (!cari) return;
    setError("");
    setData(null);
    try {
      const res = await api("/admin/pintu", { method: "POST", body: { kode: cari } });
      setData(res.data);
      setKamera(false);
    } catch (err) {
      setError(err.message);
    }
  }

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
              const bersih = decodeURIComponent(ketemu);
              setKode(bersih);
              setKamera(false);
              periksa(bersih);
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
    <div className="mx-auto max-w-xl space-y-4">
      <h1 className="text-3xl">Pintu keberangkatan</h1>
      <p className="text-sm text-[#5d6b7c]">Pindai QR atau masukkan kode pesanan. Tiket yang belum lunas atau sudah naik akan ditolak.</p>
      <form
        className="flex gap-2"
        onSubmit={(event) => {
          event.preventDefault();
          periksa();
        }}
      >
        <input className="field" placeholder="Kode pesanan" value={kode} onChange={(e) => setKode(e.target.value)} />
        <button className="rounded-xl bg-[#0e2a47] px-4 font-semibold text-white" type="submit">
          Periksa
        </button>
      </form>
      <button type="button" className="flex items-center gap-2 text-sm font-semibold text-[#0e2a47]" onClick={() => setKamera((nilai) => !nilai)}>
        <Ikon nama="pintu" />
        {kamera ? "Tutup kamera" : "Pindai QR"}
      </button>
      {kamera ? <video ref={videoRef} className="w-full rounded-2xl bg-black" muted playsInline /> : null}
      {error ? <p className="rounded-xl bg-[#fde8ea] px-4 py-3 text-sm text-[#d7263d]">{error}</p> : null}
      {data ? (
        <article className="rounded-2xl bg-white p-5 shadow-sm">
          <p className="text-sm font-semibold text-[#1f7a45]">Boleh naik</p>
          <h2 className="mt-1 text-2xl">
            {data.asal} ke {data.tujuan}
          </h2>
          <p className="text-sm text-[#5d6b7c]">
            {data.kereta} · {data.kelas} · {jam(data.berangkat_at)}
          </p>
          <p className="mt-2 text-sm">Kode pesanan {data.kode}</p>
          <ul className="mt-3 space-y-1 text-sm">
            {data.penumpang.map((orang, index) => (
              <li key={`${orang.nama}-${index}`}>
                {orang.nama}
                {orang.nik ? ` · NIK ${orang.nik}` : ""}
                {orang.kursi ? ` · kursi ${orang.kursi}` : " · bayi"}
              </li>
            ))}
          </ul>
        </article>
      ) : null}
    </div>
  );
}
