import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { useNavigate, useSearchParams } from "react-router-dom";
import { motion } from "motion/react";
import {
  fetchExplorePayloadByCategory,
  type ExploreCard,
  type ExploreCategoryBlock,
} from "../lib/api";
import ApiErrorState from "../components/ApiErrorState";
import PageBanner from "../components/PageBanner";
import ExploreSkeleton from "../components/explore/ExploreSkeleton";
import ExploreSearch from "../components/explore/ExploreSearch";
import ExploreRail from "../components/explore/ExploreRail";
import ExploreInternalLinks from "../components/explore/ExploreInternalLinks";
import CategoryFilterChip from "../components/explore/CategoryFilterChip";

import TrendingNowSection from "../components/explore/sections/TrendingNowSection";
import BrandServiceSection from "../components/explore/sections/BrandServiceSection";
import CityServiceSection from "../components/explore/sections/CityServiceSection";
import BigGridDualSection from "../components/explore/sections/BigGridDualSection";
import ServiceGuideSection from "../components/explore/sections/ServiceGuideSection";

import LeadFormWidget from "../components/explore/widgets/LeadFormWidget";
import TopPicksWidget from "../components/explore/widgets/TopPicksWidget";
import PopularBrandsWidget from "../components/explore/widgets/PopularBrandsWidget";
import RelatedTopicsWidget from "../components/explore/widgets/RelatedTopicsWidget";
import GetSocialWidget from "../components/explore/widgets/GetSocialWidget";

/**
 * Phase 4.5.7 — clean assembly per the operator's hand-drawn
 * mockup. Each section is its own dedicated component under
 * src/components/explore/sections/. The umbrella
 * ExploreCategorySection + ExploreTrendingGrid files were
 * deleted in this commit.
 *
 * Section flow (top to bottom):
 *   PageBanner
 *   TrendingNowSection                            (full-width, 5-card mosaic — replaces hero)
 *   ExploreSearch
 *   CategoryFilterChip
 *
 * Phase 4.5.8 — ExploreFeaturedGrid (hero) was removed. It rendered
 * a 5-card mosaic from `payload.hero` that was visually identical to
 * the TrendingNowSection mosaic right below it (both sourced from
 * the same heavy-overlay image pool), creating a confusing duplicate
 * impression. Trending Now is now the single editorial mosaic at the
 * top of the page. `payload.hero` is still consumed by `searchPool`
 * so its slugs remain searchable.
 *
 *   Container 1 — main 8-col + sticky aside 4-col
 *     main:   BrandServiceSection + CityServiceSection
 *     aside:  LeadFormWidget + TopPicksWidget
 *
 *   ExploreRail "Trending Searches"
 *
 *   Container 2 — main 8-col + sticky aside 4-col
 *     main:   BigGridDualSection + ServiceGuideSection
 *     aside:  PopularBrandsWidget + RelatedTopicsWidget + GetSocialWidget
 *
 *   ExploreRail "Most Read This Week"
 *   ExploreInternalLinks                          (footer 3-col)
 */
export default function ExploreEditorial() {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const category = params.get("category");

  const query = useQuery({
    queryKey: ["explore-payload", category ?? "all"],
    queryFn: ({ signal }) => fetchExplorePayloadByCategory(category, signal),
    staleTime: 60 * 1000,
  });

  const searchPool = useMemo<ExploreCard[]>(() => {
    if (!query.data) return [];
    const all: ExploreCard[] = [
      ...query.data.hero,
      ...query.data.trending_grid,
      ...query.data.rails.trending_searches,
      ...query.data.rails.most_read_week,
    ];
    for (const cat of query.data.categories) {
      all.push(cat.featured, ...cat.items);
    }
    const seen = new Set<string>();
    return all.filter((c) => {
      if (seen.has(c.slug)) return false;
      seen.add(c.slug);
      return true;
    });
  }, [query.data]);

  const activeCategoryName = useMemo<string | null>(() => {
    if (!category || !query.data) return null;
    const found = query.data.categories.find((c) => c.slug === category);
    return found?.name ?? null;
  }, [category, query.data]);

  if (query.isLoading) return <ExploreSkeleton />;

  if (query.isError || !query.data) {
    return (
      <div className="site-container py-16">
        <ApiErrorState
          message="Couldn't load Explore."
          detail="The editorial payload is temporarily unavailable. Try again in a moment."
          onRetry={() => { void query.refetch(); }}
          data-testid="explore-error"
        />
      </div>
    );
  }

  const { trending_grid, categories, rails } = query.data;

  // Phase 4.5.7 — slug-aware lookup. Each named section prefers
  // its own slug; if absent, falls back gracefully.
  const findCat = (slug: string): ExploreCategoryBlock | null =>
    categories.find((c) => c.slug === slug) ?? null;

  const brandService = findCat("brand-service");
  const cityService  = findCat("city-service");
  const serviceGuide = findCat("service-guide");

  // Big Grid Dual: prefer maintenance-tips/comparison, fall back
  // to first/second "spare" categories not already used by the
  // dedicated sections, and finally to any category[0]/[1].
  const usedSlugs = new Set(
    [brandService, cityService, serviceGuide]
      .filter((c): c is ExploreCategoryBlock => !!c)
      .map((c) => c.slug),
  );
  const spareCategories = categories.filter((c) => !usedSlugs.has(c.slug));
  const bigGridLeft  = findCat("maintenance-tips") ?? spareCategories[0] ?? categories[0] ?? null;
  const bigGridRight = findCat("comparison")       ?? spareCategories[1] ?? categories[1] ?? null;

  // Build a fallback pool for sections that need padding (City
  // Service 4×2 grid + Service Guide bottom row). Skip slugs
  // already on the page to avoid duplicates.
  const usedFromCategories: string[] = [];
  for (const c of [brandService, cityService, serviceGuide, bigGridLeft, bigGridRight]) {
    if (!c) continue;
    usedFromCategories.push(c.featured.slug);
    for (const it of c.items) usedFromCategories.push(it.slug);
  }
  const fallbackPool: ExploreCard[] = (() => {
    const skip = new Set<string>(usedFromCategories);
    const pool: ExploreCard[] = [];
    const candidates = [
      ...rails.most_read_week,
      ...rails.trending_searches,
      ...trending_grid,
    ];
    for (const c of candidates) {
      if (skip.has(c.slug)) continue;
      skip.add(c.slug);
      pool.push(c);
    }
    return pool;
  })();

  return (
    <div data-testid="explore-editorial">
      <PageBanner
        title="Explore"
        breadcrumbs={[
          { label: "Home", href: "/" },
          { label: "Explore" },
        ]}
      />

      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration: 0.3, ease: "easeOut" }}
      >
        {/* Phase 4.5.8 — Featured Grid (hero) removed. It was visually
            identical to Trending Now (same 5-card mosaic from the
            same payload pool) and confused readers into thinking the
            page was showing duplicated content. Trending Now stays
            as the single editorial mosaic at the top of /explore. */}

        {/* TRENDING NOW — full-width, no sidebar, ABOVE the search bar */}
        {trending_grid.length > 0 && (
          <section className="bg-neutral-50 py-12 md:py-16">
            <div className="site-container">
              <TrendingNowSection items={trending_grid} />
            </div>
          </section>
        )}

        <section className="bg-white border-b border-border">
          <div className="site-container py-4 md:py-5">
            <ExploreSearch pool={searchPool} />
          </div>
        </section>

        <CategoryFilterChip categoryName={activeCategoryName} />

        {/* ── Container 1: Brand + City in main; Lead + TopPicks in sticky aside ── */}
        {(brandService || cityService) && (
          <section className="bg-white py-12 md:py-16">
            <div className="site-container">
              <div className="grid grid-cols-12 gap-8">
                <main className="col-span-12 lg:col-span-8 space-y-12">
                  {brandService && <BrandServiceSection category={brandService} />}
                  {cityService && (
                    <CityServiceSection category={cityService} fallbackPool={fallbackPool} />
                  )}
                </main>

                <aside className="hidden lg:block col-span-4 lg:sticky lg:top-24 lg:self-start space-y-6">
                  <LeadFormWidget />
                  <TopPicksWidget pages={rails.most_read_week} />
                </aside>

                {/* Mobile-only widgets */}
                <div className="col-span-12 lg:hidden space-y-6">
                  <LeadFormWidget />
                  <TopPicksWidget pages={rails.most_read_week} />
                </div>
              </div>
            </div>
          </section>
        )}

        {rails.trending_searches.length > 0 && (
          <ExploreRail title="Trending Searches" items={rails.trending_searches} />
        )}

        {/* ── Container 2: BigGridDual + ServiceGuide in main; Popular/Related/Social aside ── */}
        {(bigGridLeft || bigGridRight || serviceGuide) && (
          <section className="bg-white py-12 md:py-16">
            <div className="site-container">
              <div className="grid grid-cols-12 gap-8">
                <main className="col-span-12 lg:col-span-8 space-y-12">
                  {(bigGridLeft || bigGridRight) && (
                    <BigGridDualSection
                      leftCategory={bigGridLeft}
                      rightCategory={bigGridRight}
                      fallbackPool={fallbackPool}
                    />
                  )}
                  {serviceGuide && (
                    <ServiceGuideSection
                      category={serviceGuide}
                      fallbackPool={fallbackPool}
                    />
                  )}
                </main>

                <aside className="hidden lg:block col-span-4 lg:sticky lg:top-24 lg:self-start space-y-6">
                  <PopularBrandsWidget payload={query.data} />
                  <RelatedTopicsWidget payload={query.data} />
                  <GetSocialWidget />
                </aside>

                <div className="col-span-12 lg:hidden space-y-6">
                  <PopularBrandsWidget payload={query.data} />
                  <RelatedTopicsWidget payload={query.data} />
                  <GetSocialWidget />
                </div>
              </div>
            </div>
          </section>
        )}

        {rails.most_read_week.length > 0 && (
          <ExploreRail title="Most Read This Week" items={rails.most_read_week} />
        )}

        <ExploreInternalLinks
          categories={categories}
          popularSlugs={rails.trending_searches.slice(0, 12).map((c) => c.slug)}
        />
      </motion.div>
    </div>
  );
}
