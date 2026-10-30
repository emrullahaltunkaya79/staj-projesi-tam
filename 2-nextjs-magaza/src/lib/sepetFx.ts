"use client";

/**
 * Ürün görselini sepet ikonuna uçuran efekt (fly-to-cart).
 * @param gorsel Görsel URL'i
 * @param kaynak Animasyonun başlayacağı DOM elemanı (ör. tıklanan buton)
 */
export function sepeteUcur(gorsel: string | null | undefined, kaynak: HTMLElement | null) {
  if (typeof document === "undefined") return;
  const hedef = document.querySelector<HTMLElement>("[data-sepet-hedef]");
  if (!kaynak || !hedef || !gorsel) return;

  const kRect = kaynak.getBoundingClientRect();
  const hRect = hedef.getBoundingClientRect();

  const img = document.createElement("img");
  img.src = gorsel;
  img.alt = "";
  Object.assign(img.style, {
    position: "fixed",
    left: `${kRect.left + kRect.width / 2 - 30}px`,
    top: `${kRect.top + kRect.height / 2 - 30}px`,
    width: "60px",
    height: "60px",
    objectFit: "contain",
    borderRadius: "12px",
    zIndex: "9999",
    pointerEvents: "none",
    boxShadow: "0 8px 24px rgba(0,0,0,.25)",
    transition: "all .7s cubic-bezier(.22,.9,.35,1)",
  } satisfies Partial<CSSStyleDeclaration>);
  document.body.appendChild(img);

  requestAnimationFrame(() => {
    img.style.left = `${hRect.left + hRect.width / 2 - 12}px`;
    img.style.top = `${hRect.top + hRect.height / 2 - 12}px`;
    img.style.width = "24px";
    img.style.height = "24px";
    img.style.opacity = "0.2";
    img.style.transform = "rotate(20deg)";
  });

  setTimeout(() => img.remove(), 800);
}
