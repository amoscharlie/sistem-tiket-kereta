import { Fraunces, Outfit } from "next/font/google";
import Header from "@/components/Header";
import "./globals.css";

const outfit = Outfit({
  variable: "--font-sans",
  subsets: ["latin"],
});

const fraunces = Fraunces({
  variable: "--font-serif",
  subsets: ["latin"],
});

export const metadata = {
  title: "Tiket Kereta",
  description: "Cari jadwal dan pesan kursi kereta.",
};

export default function RootLayout({ children }) {
  return (
    <html lang="id" className={`${outfit.variable} ${fraunces.variable} h-full`}>
      <body className="min-h-full">
        <Header />
        {children}
      </body>
    </html>
  );
}
