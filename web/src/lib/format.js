export function rupiah(nilai) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(nilai));
}

export function pukul(iso) {
  return new Intl.DateTimeFormat("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
    hourCycle: "h23",
    timeZone: "Asia/Jakarta",
  }).format(new Date(iso));
}

export function durasi(mulai, selesai) {
  const menit = Math.max(0, Math.round((new Date(selesai) - new Date(mulai)) / 60000));
  const jamBerangkat = Math.floor(menit / 60);
  const sisa = menit % 60;
  return sisa ? `${jamBerangkat}j ${sisa}m` : `${jamBerangkat}j`;
}

export function jam(iso) {
  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
    timeZone: "Asia/Jakarta",
  }).format(new Date(iso));
}

export function tanggalWib(iso) {
  return new Date(iso).toLocaleDateString("en-CA", { timeZone: "Asia/Jakarta" });
}

export function keInputWaktu(iso) {
  const parts = new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Jakarta",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    hourCycle: "h23",
  }).formatToParts(new Date(iso));
  const ambil = (type) => parts.find((part) => part.type === type)?.value ?? "00";
  return `${ambil("year")}-${ambil("month")}-${ambil("day")}T${ambil("hour")}:${ambil("minute")}`;
}

export function hariIni() {
  return new Date().toLocaleDateString("en-CA", { timeZone: "Asia/Jakarta" });
}

export function tanggalPanjang(ymd) {
  return new Intl.DateTimeFormat("id-ID", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
    timeZone: "Asia/Jakarta",
  }).format(new Date(`${ymd}T00:00:00+07:00`));
}

export const LABEL_STATUS = {
  menunggu_bayar: "Menunggu bayar",
  menunggu_verifikasi: "Menunggu verifikasi",
  lunas: "Lunas",
  batal: "Batal",
  kedaluwarsa: "Kedaluwarsa",
  menunggu_bukti: "Menunggu bukti",
  ditolak: "Ditolak",
  ditahan: "Ditahan",
  terjual: "Terjual",
};
