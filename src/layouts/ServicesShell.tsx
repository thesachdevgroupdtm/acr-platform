import type * as React from "react";
import { useMemo, useRef, useState, Suspense } from "react";
import { Outlet, useLocation, useNavigate } from "react-router-dom";
import { motion, AnimatePresence } from "motion/react";
import { ChevronLeft, ChevronRight } from "lucide-react";
import PageBanner from "../components/PageBanner";
import { CarSidebar } from "../components/car-sidebar";
import { categoryIcon } from "../components/service/categoryIcon";
import { useBookingContext } from "../hooks/useBookingContext";
import { useApiQuery } from "../hooks/useApiQuery";
import { fetchServices, fetchServiceDetail } from "../lib/api";

/**
 * Phase 2b (D-2b-1/2/3/8) — persistent shell for the three service layers:
 *   /services            (Layer 1 — all categories / active-category tabs)
 *   /category/:slug       (Layer 2 — one category)
 *   /services/:cat/:svc   (Layer 3 — service detail)
 *
 * The shell mounts the sticky cross-category bar (top) + CarSidebar (right)
 * ONCE; React Router keeps this layout-route component mounted while only the
 * <Outlet/> swaps between child routes. App.tsx gives all three routes a
 * STABLE animation key so the App-level page transition doesn't remount the
 * shell — the in-place crossfade lives here, around the Outlet only, so the
 * sidebar + bar never unmount or animate (the "one-page feel").
 *
 * Per-route sidebar props are derived from the URL (D-2b-2): the detail route
 * fetches the service (shared React Query cache with the page → one request)
 * to pass currentService + vehiclePrice; the category route passes
 * categorySlug; /services passes neither.
 */

const humanize = (slug: string) =>
  slug.replace(/-/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());

/** Center-column skeleton shown only while a layer's lazy chunk loads.
 *  Scoped inside the shell so the sticky bar + sidebar stay mounted. */
function OutletFallback() {
  return (
    <div className="space-y-6 animate-pulse" aria-hidden="true">
      <div className="h-8 w-2/3 bg-neutral-200" />
      <div className="h-4 w-full bg-neutral-100" />
      <div className="h-4 w-5/6 bg-neutral-100" />
      <div className="h-64 bg-neutral-100" />
    </div>
  );
}

interface ShellProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

/** Phase 2c — context the shell hands to its child routes via <Outlet/>.
 *  Layer 1 (Services.tsx) reads `activeTab` to render only that category's
 *  cards; the shell's cross-category bar is the single source of truth for
 *  the active tab on /services (D-2c-2). Other layers ignore it. */
export interface ServicesShellContext {
  activeTab: string;
  setActiveTab: (slug: string) => void;
  categories: { id: number; slug: string; title: string }[];
}

export default function ServicesShell(_props: ShellProps) {
  const location = useLocation();
  const navigate = useNavigate();
  const { state: booking } = useBookingContext();

  // Derive layer + slugs from the pathname (useParams is unreliable in a
  // pathless layout route).
  const segs = location.pathname.split("/").filter(Boolean);
  let categorySlug = "";
  let serviceSlug = "";
  if (segs[0] === "category" && segs[1]) {
    categorySlug = segs[1];
  } else if (segs[0] === "services" && segs[1] && segs[2]) {
    categorySlug = segs[1];
    serviceSlug = segs[2];
  }
  const isDetail = serviceSlug !== "";
  // Layer 1 is exactly /services (no further segments). There the bar drives
  // a client-side active TAB; everywhere else it navigates to /category/:slug.
  const isServicesLayer = segs[0] === "services" && segs.length === 1;

  // Category list for the cross-category bar (same call the Services page makes
  // → shared cache). ids drive the bar's price availability indirectly; here we
  // only need {slug,title}.
  const carContext = useMemo(
    () => ({
      brand_id: booking.car?.brand_id ?? null,
      model_id: booking.car?.model_id ?? null,
      fuel_id: booking.car?.fuel_id ?? null,
    }),
    [booking.car],
  );
  const servicesQuery = useApiQuery(["services", carContext], (signal) =>
    fetchServices(carContext, signal),
  );
  const categories = servicesQuery.data?.categories ?? [];

  // Phase 2c — Layer-1 active tab. Client state only (URL stays /services so
  // the shell + sidebar never remount on a tab switch — instant, by
  // construction). Defaults reactively to the first category until the user
  // picks one; the choice persists across category↔detail nav (shell stays
  // mounted) and resets only when the shell itself unmounts.
  const [activeTab, setActiveTab] = useState("");
  const effectiveActiveTab = activeTab || categories[0]?.slug || "";

  // D-2d-2 — horizontal-scroll affordance for the icon bar (GoMechanic-style
  // left/right chevrons; the row also free-scrolls / touch-scrolls).
  const barScrollerRef = useRef<HTMLDivElement>(null);
  const scrollBar = (dx: number) =>
    barScrollerRef.current?.scrollBy({ left: dx, behavior: "smooth" });

  // Detail route: fetch the service so the sidebar can offer "Add to cart".
  // Same query key as ServiceDetail.tsx → React Query dedupes to one request.
  const carIds = useMemo(
    () => ({
      brand_id: booking.car?.brand_id ?? null,
      model_id: booking.car?.model_id ?? null,
      fuel_id: booking.car?.fuel_id ?? null,
    }),
    [booking.car],
  );
  const detailQuery = useApiQuery(
    ["service-detail", categorySlug, serviceSlug, carIds],
    (signal) => fetchServiceDetail(categorySlug, serviceSlug, carIds, signal),
    { enabled: isDetail },
  );
  const detailService = isDetail ? detailQuery.data?.service ?? null : null;
  const vehiclePrice =
    isDetail && typeof detailQuery.data?.vehicle_price === "number"
      ? detailQuery.data.vehicle_price
      : null;

  // Derived banner title + crumbs per layer.
  const categoryTitle =
    categories.find((c) => c.slug === categorySlug)?.title ||
    (categorySlug ? humanize(categorySlug) : "");
  let bannerTitle = "Our Services";
  let crumbs: Array<{ label: string; href?: string }> = [
    { label: "Home", href: "/" },
    { label: "All Services" },
  ];
  if (isDetail) {
    bannerTitle = detailService?.title || humanize(serviceSlug);
    crumbs = [
      { label: "Home", href: "/" },
      { label: categoryTitle || "Services", href: `/category/${categorySlug}` },
      { label: bannerTitle },
    ];
  } else if (categorySlug) {
    bannerTitle = categoryTitle;
    crumbs = [
      { label: "Home", href: "/" },
      { label: "Services", href: "/services" },
      { label: bannerTitle },
    ];
  }

  // CarSidebar props per route (D-2b-2).
  const sidebarProps =
    isDetail && detailService
      ? { currentService: detailService, vehiclePrice, categorySlug }
      : categorySlug
      ? { categorySlug }
      : {};

  return (
    <>
      {/* D-2b-7 — on the detail layer the banner hero is the service image,
          falling back to the dark gradient (NO Unsplash) when null. Other
          layers keep PageBanner's default cinematic background. */}
      <PageBanner
        title={bannerTitle}
        breadcrumbs={crumbs}
        {...(isDetail ? { backgroundImage: detailService?.image ?? null } : {})}
      />

      {/* ─────────── STICKY CROSS-CATEGORY BAR (D-2d-1/2) ───────────
          Renders BELOW the PageBanner (banner → bar → content) and sticks
          under the site header (112px) once scrolled past the banner. ONE
          bar; GoMechanic-style icon + Montserrat label per category, ACR
          blue active underline + tint. icon_image when present, else the
          shared lucide fallback glyph. Drives the /services active TAB
          in-place; navigates to /category/:slug on the other layers. */}
      <nav className="sticky top-[112px] z-30 bg-white border-b border-border">
        <div className="site-container">
          <div className="relative">
            {/* Left chevron (desktop) — fades the row under a white gradient. */}
            <button
              type="button"
              aria-label="Scroll categories left"
              onClick={() => scrollBar(-260)}
              className="hidden sm:flex absolute left-0 inset-y-0 z-10 items-center pr-8 bg-gradient-to-r from-white via-white/95 to-transparent text-neutral-600 hover:text-primary transition-colors"
            >
              <ChevronLeft className="w-5 h-5" />
            </button>

            <div
              ref={barScrollerRef}
              className="flex gap-1 sm:gap-2 overflow-x-auto sm:px-9"
              style={{ scrollbarWidth: "none" }}
            >
              {servicesQuery.isLoading
                ? Array.from({ length: 7 }).map((_, i) => (
                    <div key={i} className="flex flex-col items-center gap-2 py-3 px-5 shrink-0">
                      <div className="h-12 w-12 bg-neutral-200 animate-pulse rounded" />
                      <div className="h-2.5 w-16 bg-neutral-200 animate-pulse rounded" />
                    </div>
                  ))
                : categories.map((c) => {
                    // D-2c-2 — ONE bar, two behaviors: on /services it selects
                    // the active tab (in-place, no nav); elsewhere it navigates
                    // to the full category page.
                    const active = isServicesLayer
                      ? c.slug === effectiveActiveTab
                      : c.slug === categorySlug;
                    const Icon = categoryIcon(c.slug);
                    return (
                      <button
                        key={c.id}
                        data-cat-slug={c.slug}
                        aria-current={active ? "page" : undefined}
                        onClick={() =>
                          isServicesLayer
                            ? setActiveTab(c.slug)
                            : navigate(`/category/${c.slug}`)
                        }
                        className={`group flex flex-col items-center gap-1.5 py-3 px-4 sm:px-5 whitespace-nowrap border-b-2 rounded-t-lg transition-colors shrink-0 ${
                          active
                            ? "border-primary text-primary bg-primary/10"
                            : "border-transparent text-neutral-500 hover:text-primary hover:bg-primary/5"
                        }`}
                      >
                        {c.icon_image ? (
                          <img
                            src={c.icon_image}
                            alt=""
                            loading="lazy"
                            referrerPolicy="no-referrer"
                            className="w-12 h-12 object-contain"
                          />
                        ) : (
                          <Icon className="w-12 h-12" strokeWidth={1.5} />
                        )}
                        <span className="font-display text-[10px] sm:text-xs font-semibold uppercase tracking-wide">
                          {c.title}
                        </span>
                      </button>
                    );
                  })}
            </div>

            {/* Right chevron (desktop). */}
            <button
              type="button"
              aria-label="Scroll categories right"
              onClick={() => scrollBar(260)}
              className="hidden sm:flex absolute right-0 inset-y-0 z-10 items-center pl-8 bg-gradient-to-l from-white via-white/95 to-transparent text-neutral-600 hover:text-primary transition-colors"
            >
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </nav>

      {/* ─────────── GRID: swapping content + persistent sidebar ─────────── */}
      <div className="pb-14 pt-8">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12">
            <main className="lg:col-span-2 order-2 lg:order-1 min-w-0">
              {/* Crossfade scoped to the Outlet ONLY (D-2b-8). Shell chrome
                  (bar + sidebar) lives outside this and never animates. */}
              <AnimatePresence mode="wait">
                <motion.div
                  key={location.pathname}
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  exit={{ opacity: 0 }}
                  transition={{ duration: 0.18, ease: "easeOut" }}
                >
                  {/* Local Suspense so a child's lazy-chunk load (first
                      visit to a layer) suspends HERE — only the center
                      shows the fallback. The shell chrome (category bar +
                      sidebar) lives outside this boundary and stays
                      mounted, so the sidebar DOM node is never torn down
                      on catalog-internal navigation. */}
                  <Suspense fallback={<OutletFallback />}>
                    <Outlet
                      context={
                        {
                          activeTab: effectiveActiveTab,
                          setActiveTab,
                          categories,
                        } satisfies ServicesShellContext
                      }
                    />
                  </Suspense>
                </motion.div>
              </AnimatePresence>
            </main>

            {/* Mounted ONCE — never unmounts on category↔detail nav. */}
            <CarSidebar
              {...sidebarProps}
              stickyTopPx={180}
              className="order-1 lg:order-2"
            />
          </div>
        </div>
      </div>
    </>
  );
}
