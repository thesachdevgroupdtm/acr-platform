/**
 * SeoHead — dependency-free <head> sync component.
 *
 * Reads the SEO payload returned by the Laravel API (see app/Helpers/SeoHelper.php)
 * and mutates document.head in a useEffect. API-shape compatible with
 * react-helmet-async if you later install it.
 *
 * Usage:
 *   <SeoHead seo={data.seo} fallbackTitle="Home" />
 */
import { useEffect } from "react";

export interface SeoPayload {
  title?: string | null;
  description?: string | null;
  keywords?: string | null;
  canonical?: string | null;
  extra_meta?: string | null;
  og?: {
    title?: string | null;
    description?: string | null;
    type?: string | null;
    url?: string | null;
    image?: string | null;
    site_name?: string | null;
  };
  twitter?: {
    card?: string | null;
    title?: string | null;
    description?: string | null;
    image?: string | null;
  };
  json_ld?: unknown;
}

interface Props {
  seo?: SeoPayload | null;
  fallbackTitle?: string;
  fallbackDescription?: string;
}

const MANAGED_ATTR = "data-seo-managed";

function setMetaByName(name: string, content: string | null | undefined) {
  if (typeof document === "undefined") return;
  const sel = `meta[name="${name}"][${MANAGED_ATTR}]`;
  let el = document.head.querySelector<HTMLMetaElement>(sel);
  if (!content) {
    el?.remove();
    return;
  }
  if (!el) {
    el = document.createElement("meta");
    el.setAttribute("name", name);
    el.setAttribute(MANAGED_ATTR, "1");
    document.head.appendChild(el);
  }
  el.setAttribute("content", content);
}

function setMetaByProperty(prop: string, content: string | null | undefined) {
  if (typeof document === "undefined") return;
  const sel = `meta[property="${prop}"][${MANAGED_ATTR}]`;
  let el = document.head.querySelector<HTMLMetaElement>(sel);
  if (!content) {
    el?.remove();
    return;
  }
  if (!el) {
    el = document.createElement("meta");
    el.setAttribute("property", prop);
    el.setAttribute(MANAGED_ATTR, "1");
    document.head.appendChild(el);
  }
  el.setAttribute("content", content);
}

function setLinkRel(rel: string, href: string | null | undefined) {
  if (typeof document === "undefined") return;
  const sel = `link[rel="${rel}"][${MANAGED_ATTR}]`;
  let el = document.head.querySelector<HTMLLinkElement>(sel);
  if (!href) {
    el?.remove();
    return;
  }
  if (!el) {
    el = document.createElement("link");
    el.setAttribute("rel", rel);
    el.setAttribute(MANAGED_ATTR, "1");
    document.head.appendChild(el);
  }
  el.setAttribute("href", href);
}

function setJsonLd(payload: unknown) {
  if (typeof document === "undefined") return;
  const sel = `script[type="application/ld+json"][${MANAGED_ATTR}]`;
  let el = document.head.querySelector<HTMLScriptElement>(sel);
  if (!payload) {
    el?.remove();
    return;
  }
  if (!el) {
    el = document.createElement("script");
    el.setAttribute("type", "application/ld+json");
    el.setAttribute(MANAGED_ATTR, "1");
    document.head.appendChild(el);
  }
  el.textContent = JSON.stringify(payload);
}

export default function SeoHead({
  seo,
  fallbackTitle,
  fallbackDescription,
}: Props) {
  useEffect(() => {
    const title = seo?.title || fallbackTitle;
    const description = seo?.description || fallbackDescription;

    if (typeof document !== "undefined" && title) {
      document.title = title;
    }

    setMetaByName("description", description);
    setMetaByName("keywords", seo?.keywords);
    setLinkRel("canonical", seo?.canonical);

    const og = seo?.og || {};
    setMetaByProperty("og:title",       og.title       || title);
    setMetaByProperty("og:description", og.description || description);
    setMetaByProperty("og:type",        og.type        || "website");
    setMetaByProperty("og:url",         og.url);
    setMetaByProperty("og:image",       og.image);
    setMetaByProperty("og:site_name",   og.site_name);

    const tw = seo?.twitter || {};
    setMetaByName("twitter:card",        tw.card || "summary_large_image");
    setMetaByName("twitter:title",       tw.title || title);
    setMetaByName("twitter:description", tw.description || description);
    setMetaByName("twitter:image",       tw.image || og.image);

    setJsonLd(seo?.json_ld);
  }, [seo, fallbackTitle, fallbackDescription]);

  return null;
}
