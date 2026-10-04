"use client";

import { useEffect, useState } from "react";
import { jam, rupiah } from "@/lib/format";

export default function TiketCetak({ data }) {
  const [qr, setQr] = useState("");
  const tautan = typeof window === "undefined" ? "" : `${window.location.origin}/tiket?kode=${data.kode}`;

  useEffect(() => {
    let batal = false;
    import("qrcode").then((QR) =>
      QR.toDataURL(tautan, { margin: 1, width: 180 }).then((src) => {
        if (!batal) setQr(src);
      }),
    );
    return () => {
      batal = true;
    };
  }, [tautan]);

  return (
    <article className="rounded-2xl border border-[#d5deea] bg-white p-6">
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-semibold text-[#d7263d]">Tiket Kereta</p>
          <h1 className="mt-1 text-3xl">
            {data.asal} ke {data.tujuan}
          </h1>
          <p className="mt-1 text-sm text-[#5d6b7c]">
            {data.kereta} · {data.kelas} · {jam(data.berangkat_at)}
          </p>
          {data.pulang ? (
            <p className="mt-2 text-sm text-[#5d6b7c]">
              Pulang {data.pulang.asal} ke {data.pulang.tujuan} · {data.pulang.kereta} · {data.pulang.kelas} · {jam(data.pulang.berangkat_at)}
            </p>
          ) : null}
        </div>
        {qr ? <img src={qr} alt="QR tiket" className="h-28 w-28" /> : <div className="h-28 w-28 bg-[#eef2f6]" />}
      </div>
      <p className="mt-4 text-sm">Kode pesanan {data.kode}</p>
      <ul className="mt-3 space-y-1 text-sm">
        {data.penumpang.map((orang, index) => (
          <li key={`${orang.arah}-${orang.kursi}-${index}`}>
            {orang.gratis ? `${orang.nama} · bayi ${orang.umur ?? ""} tahun · gratis` : `${orang.nama}${orang.nik ? ` · NIK ${orang.nik}` : ""} · ${orang.arah} · kursi ${orang.kursi}`}
          </li>
        ))}
      </ul>
      <p className="mt-3 font-semibold">{rupiah(data.total)}</p>
    </article>
  );
}
