import type { MetadataRoute } from "next";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        disallow: ["/api/", "/satici/", "/odeme", "/siparis/"],
      },
    ],
    sitemap: "http://127.0.0.1:3001/sitemap.xml",
  };
}
