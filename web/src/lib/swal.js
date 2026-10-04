"use client";

import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

export function konfirmasiHapus(nama) {
  return Swal.fire({
    title: "Hapus data ini?",
    text: nama,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#d7263d",
  });
}

export function konfirmasi(judul, teks) {
  return Swal.fire({
    title: judul,
    text: teks,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, batalkan",
    cancelButtonText: "Tidak",
    confirmButtonColor: "#d7263d",
  });
}

export function pemberitahuan(judul, ikon = "success") {
  return Swal.fire({
    title: judul,
    icon: ikon,
    timer: ikon === "success" ? 1400 : undefined,
    showConfirmButton: ikon !== "success",
  });
}
