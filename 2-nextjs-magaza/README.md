This is a [Next.js](https://nextjs.org) project bootstrapped with [`create-next-app`](https://nextjs.org/docs/app/api-reference/cli/create-next-app).

## Getting Started

First, run the development server:

```bash
npm run dev
# or
yarn dev
# or
pnpm dev
# or
bun dev
```

Open [http://localhost:3000](http://localhost:3000) with your browser to see the result.

You can start editing the page by modifying `app/page.tsx`. The page auto-updates as you edit the file.

This project uses [`next/font`](https://nextjs.org/docs/app/building-your-application/optimizing/fonts) to automatically optimize and load [Geist](https://vercel.com/font), a new font family for Vercel.

## Learn More

To learn more about Next.js, take a look at the following resources:

- [Next.js Documentation](https://nextjs.org/docs) - learn about Next.js features and API.
- [Learn Next.js](https://nextjs.org/learn) - an interactive Next.js tutorial.

You can check out [the Next.js GitHub repository](https://github.com/vercel/next.js) - your feedback and contributions are welcome!

## Deploy on Vercel

The easiest way to deploy your Next.js app is to use the [Vercel Platform](https://vercel.com/new?utm_medium=default-template&filter=next.js&utm_source=create-next-app&utm_campaign=create-next-app-readme) from the creators of Next.js.

Check out our [Next.js deployment documentation](https://nextjs.org/docs/app/building-your-application/deploying) for more details.

## Deployment (Vercel)

Mağaza Vercel'de çalışmaya hazır:

1. `vercel` CLI ile veya GitHub repo bağlayarak projeyi içe aktarın.
2. Environment değişkenlerini ayarlayın:
   - `BACKOFFICE_URL` — Laravel arka ofisin herkese açık API adresi (örn. `https://api.sizinalanadi.com/api/v1`)
   - `BACKOFFICE_TOKEN` — Sanctum personal access token
   - `NEXT_PUBLIC_GA_ID` — (opsiyonel) Google Analytics 4 ölçüm kimliği; çerez onayı "Tümünü Kabul Et" ile yüklenir
3. Laravel tarafı: PHP 8.2+ destekli bir host'a (Herd, Forge, VPS) `1-arka-ofis` projesini kurun, `php artisan serve` yerine php-fpm ile çalıştırın; CORS ayarına Vercel alan adını ekleyin.
4. Testler: Laravel `php artisan test` (112 test) · Mağaza `npx playwright test` (3 e2e; yerel sunucular açıkken)

Not: `vercel.json` Next.js varsayılan ayarlarını kullanır — ek yapılandırma gerekmez.
