const jalur = {
  dasbor: "M4 5h7v7H4zM13 5h7v4h-7zM13 12h7v7h-7zM4 15h7v4H4z",
  stasiun: "M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z M12 10.5a1.5 1.5 0 1 0 0.01 0",
  jadwal: "M7 3v3M17 3v3M4 8h16M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z",
  kereta: "M6 4h12a2 2 0 0 1 2 2v9H4V6a2 2 0 0 1 2-2zM4 15h16l-1.5 3h-13zM8 19.5a1.2 1.2 0 1 0 0.01 0M16 19.5a1.2 1.2 0 1 0 0.01 0M8 8h3M13 8h3",
  voucher: "M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4zM12 7v10",
  laporan: "M5 19V10M12 19V5M19 19v-7",
  cek: "M8 12.5l2.5 2.5L16 9 M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z",
  tiket: "M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2.2a2 2 0 0 0 0 3.6V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.2a2 2 0 0 0 0-3.6z",
  pintu: "M8 4h8v16H8zM12 13h.01M4 20h16",
  cari: "M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-3.5-3.5",
  pengguna: "M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM5 20a7 7 0 0 1 14 0",
  keluar: "M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2M4 12h10M11 9l3 3-3 3",
  unduh: "M12 4v10M8 10l4 4 4-4M5 19h14",
  jam: "M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v6l4 2",
  saring: "M4 6h16M7 12h10M10 18h4",
};

export default function Ikon({ nama, className = "h-4 w-4" }) {
  return (
    <svg viewBox="0 0 24 24" className={className} fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d={jalur[nama] ?? jalur.tiket} />
    </svg>
  );
}
