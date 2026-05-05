/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState, useEffect, useCallback } from "react";
import Header from "./components/Header";
import Footer from "./components/Footer";
import Home from "./pages/Home";
import Services from "./pages/Services";
import Insurance from "./pages/Insurance";
import Gallery from "./pages/Gallery";
import About from "./pages/About";
import Contact from "./pages/Contact";
import Corporate from "./pages/Corporate";
import ServiceCategory from "./pages/ServiceCategory";
import ServiceDetail from "./pages/ServiceDetail";
import ServiceCenters from "./pages/ServiceCenters";
import ServiceCenterDetail from "./pages/ServiceCenterDetail";
import Offers from "./pages/Offers";
import Coupons from "./pages/Coupons";
import CmsPage from "./pages/CmsPage";
import Sitemap from "./pages/Sitemap";
import Cart from "./pages/Cart";
import Checkout from "./pages/Checkout";
import MyBookings from "./pages/MyBookings";
import OrderDetail from "./pages/OrderDetail";
import BookingConfirmation from "./pages/BookingConfirmation";
import EstimateProcess from "./components/EstimateProcess";
import AuthModal from "./components/AuthModal";
import SessionExpiredToast from "./components/SessionExpiredToast";
import RouteResolutionLoader from "./components/RouteResolutionLoader";
import { motion, AnimatePresence } from "motion/react";
import { BUSINESS_INFO } from "./data/businessData";
import { MessageCircle } from "lucide-react";

/**
 * Phase 2.5.1 + 2.5.2 — pathname ↔ currentPage mapping.
 *
 * The codebase still uses string-based pseudo-routing (a
 * `currentPage` state value); the full react-router migration
 * remains a Phase 3 deliverable. Phase 2.5.2 makes the pseudo-
 * router URL-aware in three ways:
 *
 *   1. parsePageFromUrl(loc): URL → currentPage. Used on mount
 *      AND in the popstate listener so back/forward work.
 *   2. pageToUrl(page): currentPage → URL. Used by navigateTo to
 *      push history on click navigation.
 *   3. SPA fallback: dist/.htaccess (production) + Vite dev server
 *      default behaviour ensures unknown routes serve index.html.
 *
 * Both helpers are aware of the Vite `base` (`/app/` on production,
 * `/` in dev) via `import.meta.env.BASE_URL`. Hard-refresh on
 * /app/checkout in production therefore parses to "checkout".
 */
const BASE_URL = (import.meta.env.BASE_URL || "/").replace(/\/+$/, "") || "/";

function stripBase(pathname: string): string {
  if (BASE_URL === "/") return pathname;
  if (pathname === BASE_URL) return "/";
  if (pathname.startsWith(BASE_URL + "/")) return pathname.slice(BASE_URL.length);
  return pathname;
}

function parsePageFromUrl(loc: Location): string {
  const rebased = stripBase(loc.pathname || "/");
  const raw = rebased.replace(/\/+$/, "");
  if (raw === "" || raw === "/") return "home";

  // Strip the leading slash and accept the same string vocabulary
  // the rest of the app uses for its `currentPage` state. Pages
  // like "service-{cat}/{sub}" stay as one token.
  const stripped = raw.replace(/^\//, "");

  // Aliases — operators sometimes link to URLs the app doesn't
  // emit but which clearly map to a known page.
  if (stripped === "booking-history") return "my-bookings";

  // /order/123 → order-123  (and similar /booking-confirmation/123)
  const orderMatch = stripped.match(/^order\/(\d+)$/);
  if (orderMatch) return `order-${orderMatch[1]}`;
  const confMatch = stripped.match(/^booking-confirmation\/(\d+)$/);
  if (confMatch) return `booking-confirmation-${confMatch[1]}`;

  // /services/{cat}/{sub} → service-{cat}/{sub}
  const svcMatch = stripped.match(/^services\/([^/]+)\/([^/]+)$/);
  if (svcMatch) return `service-${svcMatch[1]}/${svcMatch[2]}`;

  // /category/{slug} → category-{slug}
  const catMatch = stripped.match(/^category\/([^/]+)$/);
  if (catMatch) return `category-${catMatch[1]}`;

  // /center/{id} → center-{id}
  const centerMatch = stripped.match(/^center\/([^/]+)$/);
  if (centerMatch) return `center-${centerMatch[1]}`;

  return stripped;
}

/**
 * Inverse of parsePageFromUrl. The mapping is intentionally not
 * 1:1 — pretty URLs like /order/12 map back to the internal key
 * "order-12" so existing currentPage-based logic doesn't change.
 */
function pageToUrl(page: string): string {
  const rel = (() => {
    if (page === "home") return "/";
    if (page === "my-bookings") return "/booking-history";

    const orderMatch = page.match(/^order-(\d+)$/);
    if (orderMatch) return `/order/${orderMatch[1]}`;
    const confMatch = page.match(/^booking-confirmation-(\d+)$/);
    if (confMatch) return `/booking-confirmation/${confMatch[1]}`;

    // service-{cat}/{sub}
    const svcMatch = page.match(/^service-(.+)\/(.+)$/);
    if (svcMatch) return `/services/${svcMatch[1]}/${svcMatch[2]}`;
    const catMatch = page.match(/^category-(.+)$/);
    if (catMatch) return `/category/${catMatch[1]}`;
    const centerMatch = page.match(/^center-(.+)$/);
    if (centerMatch) return `/center/${centerMatch[1]}`;

    return `/${page}`;
  })();

  // Re-apply the Vite base prefix in production (e.g. /app + /checkout
  // → /app/checkout). Dev's BASE_URL is "/" so this is a no-op.
  if (BASE_URL === "/") return rel;
  return rel === "/" ? BASE_URL : `${BASE_URL}${rel}`;
}

export default function App() {
  const [currentPage, setCurrentPage] = useState("home");
  // Phase 2.5.1 — render gate. False until the URL has been parsed
  // on mount (one tick). Prevents the Home flash on hard-refresh
  // of any non-/ URL.
  const [isRouteResolved, setIsRouteResolved] = useState(false);

  /**
   * Phase 2.5.2 — navigation entry point used by every page.
   * Mutates currentPage AND pushes URL via history.pushState so
   * refresh/back/forward all work. The raw setCurrentPage is
   * reserved for the popstate handler and the initial mount parse
   * (cases where the URL is already correct).
   *
   * Pages still receive this under the legacy prop name
   * `setCurrentPage` so existing call sites compile without churn.
   */
  const navigateTo = useCallback((page: string) => {
    setCurrentPage(page);
    if (typeof window === "undefined") return;
    const target = pageToUrl(page);
    const current = window.location.pathname + window.location.search;
    if (target !== current) {
      window.history.pushState({ page }, "", target);
    }
  }, []);

  useEffect(() => {
    if (typeof window === "undefined") {
      setIsRouteResolved(true);
      return;
    }
    const initial = parsePageFromUrl(window.location);
    if (initial !== "home") {
      setCurrentPage(initial);
    }
    // Replace the current history entry so a back-button from the
    // first navigation lands on the parsed page rather than a
    // stale ./. Idempotent: no-op when target === current.
    const target = pageToUrl(initial);
    const current = window.location.pathname + window.location.search;
    if (target !== current) {
      window.history.replaceState({ page: initial }, "", target);
    }
    setIsRouteResolved(true);

    const handlePop = () => {
      const next = parsePageFromUrl(window.location);
      // Use the raw setter — popstate already changed the URL.
      // Calling navigateTo here would push a duplicate history entry.
      setCurrentPage(next);
    };
    window.addEventListener("popstate", handlePop);
    return () => window.removeEventListener("popstate", handlePop);
  }, []);
  const [estimateModal, setEstimateModal] = useState<{isOpen: boolean, isCorporate: boolean, initialService: string}>({
    isOpen: false,
    isCorporate: false,
    initialService: ""
  });
  const [authModal, setAuthModal] = useState<{isOpen: boolean, defaultTab: "login" | "signup", redirectTo?: string}>({
    isOpen: false,
    defaultTab: "login",
    redirectTo: undefined
  });

  // Scroll to top on page change
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [currentPage]);

  const openEstimate = (isCorporate = false, initialService = "") => {
    setEstimateModal({ isOpen: true, isCorporate, initialService });
  };

  const openAuth = (tab: "login" | "signup" = "login", redirectTo?: string) => {
    setAuthModal({ isOpen: true, defaultTab: tab, redirectTo });
  };


  const renderPage = () => {
    if (currentPage.startsWith("center-")) {
      const centerId = currentPage.replace("center-", "");
      return <ServiceCenterDetail centerId={centerId} setCurrentPage={navigateTo} openEstimate={openEstimate} />;
    }

    if (currentPage.startsWith("category-")) {
      const categorySlug = currentPage.replace("category-", "");
      // Lazy load or just import ServiceCategory
      return <ServiceCategory categorySlug={categorySlug} setCurrentPage={navigateTo} openEstimate={openEstimate} />;
    }

    if (currentPage.startsWith("service-") && currentPage !== "service-centers") {
      // expected format: service-{categorySlug}/{serviceSlug}
      const fullSlug = currentPage.replace("service-", "");
      const [categorySlug, serviceSlug] = fullSlug.split("/");
      return <ServiceDetail categorySlug={categorySlug} serviceSlug={serviceSlug} setCurrentPage={navigateTo} openEstimate={openEstimate} />;
    }

    if (currentPage === "cms-preview") {
      return <CmsPage setCurrentPage={navigateTo} openEstimate={openEstimate} />;
    }

    // Phase 2.5a — order detail (`order-{id}`).
    if (currentPage.startsWith("order-")) {
      const id = Number(currentPage.replace("order-", ""));
      if (Number.isFinite(id) && id > 0) {
        return <OrderDetail orderId={id} setCurrentPage={navigateTo} />;
      }
    }

    // Phase 2.5a — booking confirmation (`booking-confirmation-{id}`).
    if (currentPage.startsWith("booking-confirmation-")) {
      const id = Number(currentPage.replace("booking-confirmation-", ""));
      if (Number.isFinite(id) && id > 0) {
        return (
          <BookingConfirmation orderId={id} setCurrentPage={navigateTo} />
        );
      }
    }

    switch (currentPage) {
      case "home":
        return <Home setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "services":
        return <Services setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "service-centers":
        return <ServiceCenters setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "insurance":
        return <Insurance setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "corporate":
        return <Corporate setCurrentPage={navigateTo} openEstimate={() => openEstimate(true)} />;
      case "gallery":
        return <Gallery setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "about":
        return <About setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "contact":
        return <Contact setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "offers":
        return <Offers setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "coupons":
        return <Coupons setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "sitemap":
        return <Sitemap setCurrentPage={navigateTo} openEstimate={openEstimate} />;
      case "cart":
        return <Cart setCurrentPage={navigateTo} openAuth={openAuth} />;
      case "checkout":
        return <Checkout setCurrentPage={navigateTo} openAuth={openAuth} />;
      // Phase 2.6a — `payment` route removed. Phase 2.5a's real
      // checkout flow goes Cart → Checkout → BookingConfirmation
      // directly; the legacy Payment.tsx fake-gateway page was
      // unreachable since 2.5a and got deleted in 2.6a. Direct
      // hits on /payment fall through to the default Home route.
      case "my-bookings":
        return <MyBookings setCurrentPage={navigateTo} openAuth={openAuth} />;
      default:
        return <Home setCurrentPage={navigateTo} openEstimate={openEstimate} />;
    }
  };

  // Phase 2.5.1 — hold render until the initial URL parse has run.
  // See parsePageFromUrl + the mount effect above for context.
  if (!isRouteResolved) {
    return <RouteResolutionLoader />;
  }

  return (
    <div className="min-h-screen flex flex-col bg-white selection:bg-primary selection:text-white">
      <Header
        currentPage={currentPage} 
        setCurrentPage={navigateTo} 
        openEstimate={() => openEstimate(false)}
        openAuth={openAuth}
      />
      
      <main className="flex-grow">
        <AnimatePresence mode="wait">
          <motion.div
            key={currentPage}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            transition={{ duration: 0.4, ease: "easeOut" }}
          >
            {renderPage()}
          </motion.div>
        </AnimatePresence>
      </main>

      <Footer />

      {/* Estimate Modal */}
      <AnimatePresence>
        {estimateModal.isOpen && (
          <EstimateProcess 
            onClose={() => setEstimateModal(prev => ({ ...prev, isOpen: false }))} 
            isCorporate={estimateModal.isCorporate}
            initialService={estimateModal.initialService}
          />
        )}
      </AnimatePresence>

      {/* Auth Modal — Login & Signup with anti-fraud */}
      <AuthModal
        isOpen={authModal.isOpen}
        defaultTab={authModal.defaultTab}
        redirectTo={authModal.redirectTo}
        setCurrentPage={navigateTo}
        onClose={() => setAuthModal((prev) => ({ ...prev, isOpen: false }))}
      />

      {/* Phase 2.6a — global 401 session-expired toast. */}
      <SessionExpiredToast />

      {/* Mobile Sticky CTA */}
      <div className="lg:hidden fixed bottom-6 left-6 right-6 z-40">
        <div className="bg-white/90 backdrop-blur-xl border border-border p-2 flex shadow-lg">
          <button 
            onClick={() => openEstimate(false)}
            className="w-full bg-primary text-white py-3.5 font-bold uppercase tracking-widest border border-primary text-sm shadow-sm hover:bg-primary-dark transition-colors"
          >
            Get Estimate
          </button>
        </div>
      </div>

      {/* Floating WhatsApp Button */}
      <div className="fixed bottom-24 lg:bottom-6 right-6 z-50">
        <a 
          href={`https://wa.me/91${BUSINESS_INFO.phone.replace(/\D/g, '')}`}
          target="_blank" 
          rel="noopener noreferrer" 
          className="w-14 h-14 bg-[#25D366] text-white rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-transform cursor-pointer"
        >
          <MessageCircle className="w-7 h-7" />
        </a>
      </div>
    </div>
  );
}


