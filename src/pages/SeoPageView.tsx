import { lazy, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { fetchSeoPage, trackSeoPageView, ApiError } from "../lib/api";
import SeoHead from "../components/SeoHead";
import ApiErrorState from "../components/ApiErrorState";
import PageBanner from "../components/PageBanner";
import SeoPageContent from "../components/seo/SeoPageContent";
import SeoPageCta from "../components/seo/SeoPageCta";
import RelatedArticlesGrid from "../components/seo/RelatedArticlesGrid";
import ReadingProgressBar from "../components/seo/ReadingProgressBar";
import SeoPageMeta from "../components/seo/SeoPageMeta";
import SeoPageStickyCta from "../components/seo/SeoPageStickyCta";
import ContinueReading from "../components/seo/ContinueReading";
import InternalLinkingFooter from "../components/seo/InternalLinkingFooter";

// Lazy NotFound — already split by App.tsx so the chunk is shared.
const NotFound = lazy(() => import("./NotFound"));

/**
 * Phase 4.5b-polish — refactored layout with reading
 * enhancements:
 *
 *   ReadingProgressBar     (fixed top, scroll-driven)
 *   SeoPageBreadcrumbs     (Home › Explore › Category › Title)
 *   SeoPageHero            (dark hero — Phase 4.5b-fix)
 *   SeoPageMeta            (author/date/read-time + tag chips)
 *   ┌────────────────────────────┬──────────────────┐
 *   │ SeoPageContent             │ SeoPageStickyCta │
 *   │ SeoPageCta                 │ (desktop only)   │
 *   └────────────────────────────┴──────────────────┘
 *   RelatedArticlesGrid    (existing, category+tag relevance)
 *   ContinueReading        (NEW, newest in same category)
 *
 * Behavior contract from earlier phases unchanged:
 *   - Reserved-slug guard, redirect handling, NotFound on 404.
 *   - All existing data-testids on / preserved.
 */
const RESERVED_SLUGS = new Set([
  "login", "logout", "register", "forgot-password",
  "cart", "checkout", "orders", "profile", "settings",
  "services", "service-centers", "category", "coupons",
  "offers", "about", "contact", "privacy", "terms",
  "faq", "sitemap", "testimonials", "gallery",
  "insurance", "corporate", "cms-preview",
  "booking-history", "my-bookings", "order",
  "booking-confirmation", "not-found", "payment",
  "admin", "api", "storage",
  "explore", "home", "index", "main",
]);

export default function SeoPageView() {
  const { slug = "" } = useParams<{ slug: string }>();
  const navigate = useNavigate();

  const isReserved = RESERVED_SLUGS.has(slug);

  const query = useQuery({
    queryKey: ["seo-page", slug],
    queryFn: ({ signal }) => fetchSeoPage(slug, signal),
    enabled: !!slug && !isReserved,
    retry: false,
    staleTime: 5 * 60 * 1000,
  });

  useEffect(() => {
    if (query.data?.redirect) {
      navigate(query.data.redirect.to, { replace: true });
    }
  }, [query.data, navigate]);

  // Phase 4.5 — fire-and-forget view tracking. Backend
  // rate-limits per IP+slug (max 1 increment per 10 min) so
  // duplicate calls are harmless. We don't await; failure is
  // silent.
  useEffect(() => {
    if (!query.data?.page?.slug) return;
    const ctrl = new AbortController();
    void trackSeoPageView(query.data.page.slug, ctrl.signal).catch(() => {
      /* swallow — tracking is best-effort */
    });
    return () => ctrl.abort();
  }, [query.data?.page?.slug]);

  if (isReserved) {
    return <NotFound />;
  }

  if (query.isLoading) {
    return <PageSkeleton />;
  }

  if (query.isError) {
    const status = query.error instanceof ApiError ? query.error.status : 0;
    if (status === 404) {
      return <NotFound />;
    }
    return (
      <div className="site-container py-12">
        <ApiErrorState
          message="Couldn't load this page."
          detail="Check your connection and retry."
          onRetry={() => { void query.refetch(); }}
        />
      </div>
    );
  }

  if (query.data?.redirect || !query.data?.page) {
    return null;
  }

  const { page, seo, related_pages = [] } = query.data;

  return (
    <>
      <ReadingProgressBar />
      <SeoHead seo={seo ?? {}} />

      {/* Phase 4.7.1 — refactored hero. SeoPageHero (Phase 4.5b)
          used a solid bg-neutral-900 backdrop, which violated the
          operator-flagged brand consistency rule (D-4.7.1-3 says
          ALL page banners use the SAME PageBanner image+overlay
          pattern). Now uses PageBanner with breadcrumbs built
          from page.category. Category badge + excerpt move to
          a small intro strip below the banner. */}
      <PageBanner
        title={page.title}
        breadcrumbs={[
          { label: "Home",    onClick: () => navigate("/") },
          { label: "Explore", onClick: () => navigate("/explore") },
          ...(page.category
            ? [{ label: page.category, onClick: () => navigate(`/explore?category=${encodeURIComponent(page.category!)}`) }]
            : []),
          { label: page.title },
        ]}
      />

      {page.excerpt && (
        <section
          data-testid="seo-page-intro"
          className="bg-white border-b border-border"
        >
          <div className="site-container py-6 md:py-8">
            {page.category && (
              <span className="inline-block bg-primary text-white px-3 py-1 text-[10px] font-bold uppercase tracking-widest mb-3">
                {page.category}
              </span>
            )}
            <p className="text-base md:text-lg text-neutral-600 font-medium leading-relaxed max-w-3xl">
              {page.excerpt}
            </p>
          </div>
        </section>
      )}

      <article className="site-container py-12 md:py-16">
        <SeoPageMeta
          publishedAt={page.published_at}
          body={page.body}
          tags={page.tags}
        />

        <div className="lg:grid lg:grid-cols-[1fr_300px] lg:gap-12">
          <main>
            <SeoPageContent html={page.body} />

            {page.cta?.title && (
              <SeoPageCta
                title={page.cta.title}
                buttonText={page.cta.button_text}
                buttonUrl={page.cta.button_url}
              />
            )}
          </main>

          <div className="hidden lg:block">
            <SeoPageStickyCta
              body={page.body}
              pageTitle={page.title}
              ctaTitle={page.cta?.title}
              ctaButtonText={page.cta?.button_text}
              ctaButtonUrl={page.cta?.button_url}
            />
          </div>
        </div>

        <RelatedArticlesGrid related={related_pages} />
      </article>

      <ContinueReading currentSlug={page.slug} category={page.category} />

      {/* Phase 4.5 — internal-linking footer (categories + popular). */}
      <InternalLinkingFooter />
    </>
  );
}

function PageSkeleton() {
  return (
    <>
      <div className="bg-neutral-900 py-16 md:py-24">
        <div className="site-container">
          <div className="h-3 w-32 bg-neutral-800 animate-pulse mb-6" />
          <div className="h-8 w-24 bg-neutral-800 animate-pulse mb-4" />
          <div className="h-12 w-3/4 bg-neutral-800 animate-pulse mb-3" />
          <div className="h-12 w-1/2 bg-neutral-800 animate-pulse" />
        </div>
      </div>
      <div className="site-container max-w-3xl py-12 space-y-3">
        <div className="h-4 w-full bg-neutral-200 animate-pulse" />
        <div className="h-4 w-5/6 bg-neutral-200 animate-pulse" />
        <div className="h-4 w-4/6 bg-neutral-200 animate-pulse" />
        <div className="h-4 w-full bg-neutral-200 animate-pulse mt-6" />
        <div className="h-4 w-3/4 bg-neutral-200 animate-pulse" />
      </div>
    </>
  );
}
