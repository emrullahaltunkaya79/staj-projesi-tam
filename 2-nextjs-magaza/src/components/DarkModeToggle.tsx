"use client";

import { useEffect, useState } from "react";
import { Moon, Sun } from "lucide-react";
import { cerezKategorisiAktif } from "@/lib/cerez";

/** Karanlık mod durumu — header butonu ve kullanıcı menüsündeki anahtar paylaşır. */
export function karanlikModKullan() {
  const [dark, setDark] = useState(false);

  useEffect(() => {
    const kayitli = localStorage.getItem("hba-dark");
    if (kayitli === "true" || (!kayitli && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
      setDark(true);
      document.documentElement.classList.add("dark");
    }
  }, []);

  const degistir = () => {
    const yeni = !dark;
    setDark(yeni);
    document.documentElement.classList.toggle("dark", yeni);
    // Tema tercihi fonksiyonel çerezdir — onay yoksa yalnızca bu oturum için geçerlidir
    if (cerezKategorisiAktif('fonksiyonel')) localStorage.setItem("hba-dark", String(yeni));
  };

  return { dark, degistir };
}

export function DarkModeToggle() {
  const { dark, degistir } = karanlikModKullan();

  return (
    <button
      type="button"
      onClick={degistir}
      title={dark ? "Açık temaya geç" : "Karanlık temaya geç"}
      aria-label="Tema"
      className="flex items-center justify-center rounded-full border border-white/20 bg-white/10 p-2 text-white backdrop-blur transition hover:border-white/40"
    >
      {dark ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
    </button>
  );
}
