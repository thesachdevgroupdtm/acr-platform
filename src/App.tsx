/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState, useEffect, useCallback, lazy, Suspense } from "react";
import { Routes, Route, useLocation, useNavigate, useParams } from "react-router-dom";
import Header from "./components/Header";
import Footer from "./components/Footer";
// Phase 2.6b — Home stays eager. It is the most-visited entry and
// shipping it inside the initial bundle avoids one round-trip on
// the most common landing path. All other routes are lazy-loaded
// (D-2.6b-1: one chunk per route via React.lazy()). Vite auto-
// generates per-route chunks named after the source file.
import Home from "./pages/Home";
const Services = lazy(() => import("./pages/Services"));
const Insurance = lazy(() => import("./pages/Insurance"));
const Gallery = lazy(() => import("./pages/Gallery"));
const About = lazy(() => import("./pages/About"));
const Contact = lazy(() => import("./pages/Contact"));
const Corporate = lazy(() => import("./pages/Corporate"));
const ServiceCategory = lazy(() => import("./pages/ServiceCategory"));
const ServiceDetail = lazy(() => import("./pages/ServiceDetail"));
const ServiceCenters = lazy(() => import("./pages/ServiceCenters"));
const ServiceCenterDetail = lazy(() => import("./pages/ServiceCenterDetail"));
const Offers = lazy(() => import("./pages/Offers"));
const Coupons = lazy(() => import("./pages/Coupons"));
const CmsPage = lazy(() => import("./pages/CmsPage"));
const Sitemap = lazy(() => import("./pages/Sitemap"));
const Cart = lazy(() => import("./pages/Cart"));
const Checkout = lazy(() => import("./pages/Checkout"));
const MyBookings = lazy(() => import("./pages/MyBookings"));
const OrderDetail = lazy(() => import("./pages/OrderDetail"));
const BookingConfirmation = lazy(() => import("./pages/BookingConfirmation"));
const NotFound = lazy(() => import("./pages/NotFound"));
const Testimonials = lazy(() => import("./pages/Testimonials"));
import EstimateProcess from "./components/EstimateProcess";
import AuthModal from "./components/AuthModal";
import SessionExpiredToast from "./components/SessionExpiredToast";
import GlobalLoadingFallback from "./components/GlobalLoadingFallback";
import ChunkErrorBoundary from "./components/ChunkErrorBoundary";
import { motion, AnimatePresence } from "motion/react";
import { BUSINESS_INFO } from "./data/businessData";
import { MessageCircle } from "lucide-react";

/**
 * Phase 3A — react-router migration (Commit A: shim).
 *
 * The legacy string-keyed `currentPage` machine + parsePageFromUrl /
 * pageToUrl helpers + popstate listener are replaced by
 * BrowserRouter + <Routes>. URL is now the single source of truth.
 *
 * Compatibility shim: page components still receive `setCurrentPage`
 * and Header still receives a string-keyed `currentPage`. Both are
 * derived from the router via the helpers below. Pages do not
 * change in this commit (Phase 3B will drop the shim and have each
 * page call useNavigate / useParams directly).
 *
 * Aliases preserved verbatim from the old parsePageFromUrl:
 *   /booking-history             → MyBookings   (alias)
 *   /my-bookings                 → MyBookings   (canonical)
 *   /order/:id                   → OrderDetail
 *   /booking-confirmation/:id    → BookingConfirmation
 *   /services/:category/:service → ServiceDetail
 *   /category/:slug              → ServiceCategory
 *   /center/:id                  → ServiceCenterDetail
 *   /payment                     → NotFound (Phase 2.6a deletion regression — falls through catch-all *)
 *   *                            → NotFound
 */

/**
 * URL pathname → string-keyed page id Header still consumes.
 * BrowserRouter strips the basename for us, so `pathname` here is
 * always relative ("/", "/services", "/category/foo", …).
 */
function pathnameToPageKey(pathname: string): string {
  const raw = (pathname || "/").replace(/\/+$/, "");
  if (raw === "" || raw === "/") return "home";

  const stripped = raw.replace(/^\//, "");

  if (stripped === "booking-history") return "my-bookings";

  const orderMatch = stripped.match(/^order\/(\d+)$/);
  if (orderMatch) return `order-${orderMatch[1]}`;
  const confMatch = stripped.match(/^booking-confirmation\/(\d+)$/);
  if (confMatch) return `booking-confirmation-${confMatch[1]}`;

  const svcMatch = stripped.match(/^services\/([^/]+)\/([^/]+)$/);
  if (svcMatch) return `service-${svcMatch[1]}/${svcMatch[2]}`;

  const catMatch = stripped.match(/^category\/([^/]+)$/);
  if (catMatch) return `category-${catMatch[1]}`;

  const centerMatch = stripped.match(/^center\/([^/]+)$/);
  if (centerMatch) return `center-${centerMatch[1]}`;

  return stripped;
}

/**
 * Inverse: page-key → router-relative path. Used by the
 * `setCurrentPage` shim so legacy callers (Header, Footer, page
 * components) keep working without modification. BrowserRouter
 * re-prefixes its basename, so we return paths WITHOUT the Vite
 * base prefix here.
 */
function pageKeyToPath(page: string): string {
  if (page === "home") return "/";
  if (page === "my-bookings") return "/booking-history";

  const orderMatch = page.match(/^order-(\d+)$/);
  if (orderMatch) return `/order/${orderMatch[1]}`;
  const confMatch = page.match(/^booking-confirmation-(\d+)$/);
  if (confMatch) return `/booking-confirmation/${confMatch[1]}`;

  const svcMatch = page.match(/^service-(.+)\/(.+)$/);
  if (svcMatch) return `/services/${svcMatch[1]}/${svcMatch[2]}`;
  const catMatch = page.match(/^category-(.+)$/);
  if (catMatch) return `/category/${catMatch[1]}`;
  const centerMatch = page.match(/^center-(.+)$/);
  if (centerMatch) return `/center/${centerMatch[1]}`;

  return `/${page}`;
}

// ─────────────── Route element wrappers ───────────────
//
// These pull useParams off the router and forward them as props to
// each page in the same shape the legacy renderPage() switch used.
// They are the entire surface area of the shim — Phase 3B replaces
// them with direct useParams calls inside each page.

interface ShimProps {
  setCurrentPage: (page: string) => void;
  openEstimate: (isCorporate?: boolean, initialService?: string) => void;
  openAuth: (tab?: "login" | "signup", redirectTo?: string) => void;
}

function ServiceDetailRoute({ setCurrentPage, openEstimate }: ShimProps) {
  const { category, service } = useParams<{ category: string; service: string }>();
  return (
    <ServiceDetail
      categorySlug={category!}
      serviceSlug={service!}
      setCurrentPage={setCurrentPage}
      openEstimate={openEstimate}
    />
  );
}

function ServiceCategoryRoute({ setCurrentPage, openEstimate }: ShimProps) {
  const { slug } = useParams<{ slug: string }>();
  return (
    <ServiceCategory
      categorySlug={slug!}
      setCurrentPage={setCurrentPage}
      openEstimate={openEstimate}
    />
  );
}

function ServiceCenterDetailRoute({ setCurrentPage, openEstimate }: ShimProps) {
  const { id } = useParams<{ id: string }>();
  return (
    <ServiceCenterDetail
      centerId={id!}
      setCurrentPage={setCurrentPage}
      openEstimate={openEstimate}
    />
  );
}

function OrderDetailRoute({ setCurrentPage }: ShimProps) {
  const { id } = useParams<{ id: string }>();
  const numericId = Number(id);
  // Original behaviour: invalid id → NotFound (Phase 2.6a-fix).
  if (!Number.isFinite(numericId) || numericId <= 0) {
    return <NotFound setCurrentPage={setCurrentPage} />;
  }
  return <OrderDetail orderId={numericId} setCurrentPage={setCurrentPage} />;
}

function BookingConfirmationRoute({ setCurrentPage }: ShimProps) {
  const { id } = useParams<{ id: string }>();
  const numericId = Number(id);
  if (!Number.isFinite(numericId) || numericId <= 0) {
    return <NotFound setCurrentPage={setCurrentPage} />;
  }
  return (
    <BookingConfirmation orderId={numericId} setCurrentPage={setCurrentPage} />
  );
}

export default function App() {
  const location = useLocation();
  const navigate = useNavigate();

  // Header expects a string-keyed `currentPage` for its active-state
  // computation (services/category-foo/service-foo/center-foo/etc.).
  // We derive it from the URL on every render.
  const currentPage = pathnameToPageKey(location.pathname);

  // The shim: legacy callers do `setCurrentPage("services")` →
  // resolves to /services. Phase 3B will replace these calls with
  // direct useNavigate inside each page.
  const setCurrentPage = useCallback(
    (page: string) => {
      navigate(pageKeyToPath(page));
    },
    [navigate],
  );

  const [estimateModal, setEstimateModal] = useState<{ isOpen: boolean; isCorporate: boolean; initialService: string }>({
    isOpen: false,
    isCorporate: false,
    initialService: "",
  });
  const [authModal, setAuthModal] = useState<{ isOpen: boolean; defaultTab: "login" | "signup"; redirectTo?: string }>({
    isOpen: false,
    defaultTab: "login",
    redirectTo: undefined,
  });

  // Scroll to top on pathname change. Search-only changes (e.g.
  // ?coupon=FIRST10) preserve scroll position, which matches the
  // pre-router behaviour where currentPage hadn't changed.
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [location.pathname]);

  const openEstimate = (isCorporate = false, initialService = "") => {
    setEstimateModal({ isOpen: true, isCorporate, initialService });
  };

  const openAuth = (tab: "login" | "signup" = "login", redirectTo?: string) => {
    setAuthModal({ isOpen: true, defaultTab: tab, redirectTo });
  };

  // Pre-built shim prop bundles — three flavours so the route
  // wrappers don't have to spread three arguments each.
  const pageShim: ShimProps = { setCurrentPage, openEstimate, openAuth };

  return (
    <div className="min-h-screen flex flex-col bg-white selection:bg-primary selection:text-white">
      <Header
        currentPage={currentPage}
        setCurrentPage={setCurrentPage}
        openEstimate={() => openEstimate(false)}
        openAuth={openAuth}
      />

      <main className="flex-grow">
        {/*
          Phase 2.6b — code-splitting boundaries (preserved verbatim
          across the Phase 3A router migration).

          ChunkErrorBoundary catches the dynamic-import rejection
          path; Suspense lives INSIDE motion.div (per the Phase 2.6b
          architecture call) so each new route's chunk fetch can
          suspend independently of the previous route's exit
          animation. The motion.div key is now location.pathname
          (was: currentPage string). Routes is given an explicit
          `location` prop so the exiting motion.div retains the OLD
          route's children while the new motion.div mounts with the
          new route — same separation that mode="wait" already
          relied on.
        */}
        <ChunkErrorBoundary>
          <AnimatePresence mode="wait">
            <motion.div
              key={location.pathname}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              transition={{ duration: 0.4, ease: "easeOut" }}
            >
              <Suspense fallback={<GlobalLoadingFallback />}>
                <Routes location={location}>
                  <Route path="/" element={<Home setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/services" element={<Services setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/services/:category/:service" element={<ServiceDetailRoute {...pageShim} />} />
                  <Route path="/category/:slug" element={<ServiceCategoryRoute {...pageShim} />} />
                  <Route path="/service-centers" element={<ServiceCenters setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/center/:id" element={<ServiceCenterDetailRoute {...pageShim} />} />
                  <Route path="/insurance" element={<Insurance setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/corporate" element={<Corporate setCurrentPage={setCurrentPage} openEstimate={() => openEstimate(true)} />} />
                  <Route path="/gallery" element={<Gallery setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/about" element={<About setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/contact" element={<Contact setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/offers" element={<Offers setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/coupons" element={<Coupons setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/sitemap" element={<Sitemap setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/cms-preview" element={<CmsPage setCurrentPage={setCurrentPage} openEstimate={openEstimate} />} />
                  <Route path="/cart" element={<Cart setCurrentPage={setCurrentPage} openAuth={openAuth} />} />
                  <Route path="/checkout" element={<Checkout setCurrentPage={setCurrentPage} openAuth={openAuth} />} />
                  <Route path="/booking-history" element={<MyBookings setCurrentPage={setCurrentPage} openAuth={openAuth} />} />
                  <Route path="/my-bookings" element={<MyBookings setCurrentPage={setCurrentPage} openAuth={openAuth} />} />
                  <Route path="/testimonials" element={<Testimonials setCurrentPage={setCurrentPage} />} />
                  <Route path="/order/:id" element={<OrderDetailRoute {...pageShim} />} />
                  <Route path="/booking-confirmation/:id" element={<BookingConfirmationRoute {...pageShim} />} />
                  <Route path="/not-found" element={<NotFound setCurrentPage={setCurrentPage} />} />
                  {/*
                    Catch-all — preserves the Phase 2.6a-fix invariant
                    that unknown URLs land on NotFound at the original
                    URL (NOT a silent home redirect). /payment still
                    maps here per Phase 2.6a, locked by the smoke test.
                  */}
                  <Route path="*" element={<NotFound setCurrentPage={setCurrentPage} />} />
                </Routes>
              </Suspense>
            </motion.div>
          </AnimatePresence>
        </ChunkErrorBoundary>
      </main>

      <Footer setCurrentPage={setCurrentPage} />

      {/* Estimate Modal */}
      <AnimatePresence>
        {estimateModal.isOpen && (
          <EstimateProcess
            onClose={() => setEstimateModal((prev) => ({ ...prev, isOpen: false }))}
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
        setCurrentPage={setCurrentPage}
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
          href={`https://wa.me/91${BUSINESS_INFO.phone.replace(/\D/g, "")}`}
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
