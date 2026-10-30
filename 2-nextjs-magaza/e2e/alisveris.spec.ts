import { test, expect } from "@playwright/test";

/**
 * Ana alışveriş hunisi: ana sayfa → ürün → sepet → ödeme → sipariş sayfası.
 * Laravel (8000) ve Next dev (3001) sunucularının çalıştığı varsayılır.
 */
test.describe("Alışveriş hunisi", () => {
  test("ana sayfa ürün kartları ve indirim rozetleri", async ({ page }) => {
    await page.goto("/");
    await expect(page.locator("a[href^='/urun/']")).toHaveCount(74, { timeout: 30_000 });
    // 22 indirimli üründen en az bir rozet ana sayfada görünmeli
    await page.waitForTimeout(1500);
    const rozet = page.locator("span", { hasText: /%\d+ İndirim/ }).first();
    await rozet.waitFor({ state: "visible", timeout: 15_000 });
  });

  test("arama sonuç filtreler", async ({ page }) => {
    await page.goto("/ara?q=Kulakl%C4%B1k");
    await page.waitForLoadState("domcontentloaded");
    await expect(page.locator("p", { hasText: /için \d+ sonuç/ })).toBeVisible({ timeout: 20_000 });
    const kartlar = await page.locator("a[href^='/urun/']").count();
    expect(kartlar).toBeGreaterThan(0);
    expect(kartlar).toBeLessThan(74);
  });

  test("ürün detayı → sepete ekle → sepet → sipariş oluştur", async ({ page }) => {
    test.setTimeout(150_000);

    // Stoklu, varyantsız bir ürün: Defter A4 100Y (id 6)
    await page.goto("/urun/6");
    await page.waitForLoadState("domcontentloaded");

    const ekle = page.getByRole("button", { name: "Sepete Ekle" });
    await ekle.waitFor({ state: "visible", timeout: 20_000 });
    await ekle.click();
    await expect(page.getByText("Sepete Eklendi")).toBeVisible();

    // Sepet
    await page.goto("/sepet");
    await expect(page.getByText("Defter A4 100Y")).toBeVisible({ timeout: 10_000 });

    // Ödeme — hidrasyonun tamamlanmasını bekle (networkidle), sonra doldur
    await page.goto("/odeme", { waitUntil: "networkidle" });
    await expect(page.getByRole("button", { name: /Siparişi Onayla/ })).toBeVisible({ timeout: 30_000 });
    await page.getByPlaceholder("Ayşe Yılmaz").fill("E2E Test Kullanıcı");
    await page.getByPlaceholder("ayse@ornek.com").fill(`e2e-${Date.now()}@test.local`);
    await page.getByPlaceholder("Mahalle, cadde, no, ilçe / il").fill("E2E Test Mahallesi, Cadde No 1, İstanbul");

    // Tıkla ve aynı anda navigasyonu bekle
    await Promise.all([
      page.waitForURL(/\/siparis\/ORD-/, { timeout: 90_000 }),
      page.getByRole("button", { name: /Siparişi Onayla/ }).click(),
    ]);
    await expect(page.locator("h1", { hasText: "Sipariş" })).toBeVisible();

    const orderNo = (page.url().split("/").pop() ?? "").split("?")[0];
    expect(orderNo).toMatch(/^ORD-\d+$/);
  });
});
