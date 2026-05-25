import { Helmet } from "react-helmet-async";

/**
 * Phase 4.5b — Helmet-backed SEO injection.
 * Phase 4.5d — legacy `src/lib/SeoHead.tsx` (Phase 1.6) deleted; this
 * file is now the sole SeoHead component in the codebase.
 *
 * Mirrors the FLAT shape returned by the Phase 4.5a
 * HasSeoMetadata::getSeoData() cascade (one key per attribute,
 * snake_case). Phase 4.5c retrofitted the 5 customer-facing pages
 * (Home / Services / ServiceCategory / ServiceDetail / ServiceCenters)
 * to consume it alongside the original /:slug / /explore consumers.
 *
 * react-helmet-async (single new dependency in Phase 4.5b) is
 * SSR-safe and renders nothing visible — it just mutates <head>.
 */
export interface SeoData {
  meta_title?: string | null;
  meta_description?: string | null;
  meta_keywords?: string | null;
  canonical_url?: string | null;
  robots_meta?: string | null;
  og_title?: string | null;
  og_description?: string | null;
  og_image?: string | null;
  og_type?: string | null;
  twitter_card?: string | null;
  twitter_title?: string | null;
  twitter_description?: string | null;
  twitter_image?: string | null;
  schema_jsonld?: string | null;
}

export function SeoHead({ seo }: { seo: SeoData }) {
  return (
    <Helmet>
      {seo.meta_title && <title>{seo.meta_title}</title>}
      {seo.meta_description && (
        <meta name="description" content={seo.meta_description} />
      )}
      {seo.meta_keywords && (
        <meta name="keywords" content={seo.meta_keywords} />
      )}
      {seo.canonical_url && <link rel="canonical" href={seo.canonical_url} />}
      {seo.robots_meta && <meta name="robots" content={seo.robots_meta} />}

      {/* Open Graph */}
      {seo.og_title && <meta property="og:title" content={seo.og_title} />}
      {seo.og_description && (
        <meta property="og:description" content={seo.og_description} />
      )}
      {seo.og_image && <meta property="og:image" content={seo.og_image} />}
      {seo.og_type && <meta property="og:type" content={seo.og_type} />}

      {/* Twitter */}
      {seo.twitter_card && (
        <meta name="twitter:card" content={seo.twitter_card} />
      )}
      {seo.twitter_title && (
        <meta name="twitter:title" content={seo.twitter_title} />
      )}
      {seo.twitter_description && (
        <meta name="twitter:description" content={seo.twitter_description} />
      )}
      {seo.twitter_image && (
        <meta name="twitter:image" content={seo.twitter_image} />
      )}

      {/* Schema.org JSON-LD (string, already encoded by the engine) */}
      {seo.schema_jsonld && (
        <script type="application/ld+json">{seo.schema_jsonld}</script>
      )}
    </Helmet>
  );
}

export default SeoHead;
