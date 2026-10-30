import { defineConfig } from "@playwright/test";

/**
 * E2E — çalışan dev sunucuya karşı çalışır (localhost:3001 + Laravel:8000).
 * `npx playwright test`
 */
export default defineConfig({
  testDir: "./e2e",
  timeout: 90_000,
  retries: 0,
  workers: 1,
  use: {
    baseURL: "http://127.0.0.1:3001",
    headless: true,
    locale: "tr-TR",
    actionTimeout: 15_000,
  },
  reporter: [["list"]],
  outputDir: "./e2e/.sonuclar",
});
