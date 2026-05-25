/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState, useEffect, lazy, Suspense } from "react";
import { Routes, Route, useLocation } from "react-router-dom";
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
// Phase 4.5 — premium editorial /explore (replaces ExplorePage).
// Single component pipeline; no conditional A/B render → no flicker.
const ExploreEditorial = lazy(() => import("./pages/ExploreEditorial"));
const SeoPageView = lazy(() => import("./pages/SeoPageView"));
import EstimateProcess from "./components/EstimateProcess";
import AuthModal from "./components/AuthModal";
import SessionExpiredToast from "./components/SessionExpiredToast";
import GlobalLoadingFallback from "./components/GlobalLoadingFallback";
import ChunkErrorBoundary from "./components/ChunkErrorBoundary";
import RuntimeErrorBoundary from "./components/RuntimeErrorBoundary";
import { motion, AnimatePresence } from "motion/react";
import { BUSINESS_INFO } from "./data/businessData";
import { MessageCircle } from "lucide-react";

/**
 * Phase 3B — pure react-router migration.
 *
 * The Phase 3A shim layer (pageKeyToPath / pathnameToPageKey /
 * setCurrentPage callback / 5 route-element wrappers) has been
 * removed. Each page now imports useNavigate / useParams directly
 * from react-router-dom. Header derives active state from
 * useLocation. Footer's QUICK_LINKS / USEFUL_LINKS reference URL
 * paths instead of legacy page keys.
 *
 * Routes definition is unchanged in shape from Phase 3A — every
 * alias (/booking-history → MyBookings, /payment → catch-all
 * NotFound, etc.) is still wired exactly the same way. The only
 * difference is each <Route element> now points at the page
 * component directly, no wrapper.
 */

export default function App() {
  const location = useLocation();

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
  // ?coupon=FIRST10) preserve scroll position.
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [location.pathname]);

  const openEstimate = (isCorporate = false, initialService = "") => {
    setEstimateModal({ isOpen: true, isCorporate, initialService });
  };

  const openAuth = (tab: "login" | "signup" = "login", redirectTo?: string) => {
    setAuthModal({ isOpen: true, defaultTab: tab, redirectTo });
  };

  return (
    <div className="min-h-screen flex flex-col bg-white selection:bg-primary selection:text-white">
      <Header openEstimate={() => openEstimate(false)} openAuth={openAuth} />

      <main className="flex-grow">
        {/*
          Phase 2.6b boundary stack (preserved verbatim through
          Phase 3A and 3B).

          ChunkErrorBoundary catches the dynamic-import rejection
          path (network blocked, stale chunk hashes after a deploy).
          Suspense lives INSIDE motion.div so each new route's chunk
          fetch can suspend independently of the previous route's
          exit animation. The motion.div key is location.pathname.
          Routes is given an explicit `location` prop so the exiting
          motion.div retains the OLD route's children while the new
          motion.div mounts with the new route — same separation
          that mode="wait" already relied on.
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
              {/* Pass the route pathname as a `resetKey` so a
                  navigation away from an errored page clears the
                  boundary's error state. Using a regular prop (not
                  JSX `key`) because this project ships React 19
                  without @types/react and the JSX key attribute
                  doesn't type-check on the class component. */}
              <RuntimeErrorBoundary resetKey={location.pathname}>
                <Suspense fallback={<GlobalLoadingFallback />}>
                  <Routes location={location}>
                  <Route path="/" element={<Home openEstimate={openEstimate} />} />
                  <Route path="/services" element={<Services openEstimate={openEstimate} />} />
                  <Route path="/services/:category/:service" element={<ServiceDetail openEstimate={openEstimate} />} />
                  <Route path="/category/:slug" element={<ServiceCategory openEstimate={openEstimate} />} />
                  <Route path="/service-centers" element={<ServiceCenters openEstimate={openEstimate} />} />
                  <Route path="/center/:id" element={<ServiceCenterDetail openEstimate={openEstimate} />} />
                  <Route path="/insurance" element={<Insurance openEstimate={openEstimate} />} />
                  <Route path="/corporate" element={<Corporate openEstimate={() => openEstimate(true)} />} />
                  <Route path="/gallery" element={<Gallery openEstimate={openEstimate} />} />
                  <Route path="/about" element={<About openEstimate={openEstimate} />} />
                  <Route path="/contact" element={<Contact openEstimate={openEstimate} />} />
                  <Route path="/offers" element={<Offers openEstimate={openEstimate} />} />
                  <Route path="/coupons" element={<Coupons openEstimate={openEstimate} />} />
                  <Route path="/sitemap" element={<Sitemap openEstimate={openEstimate} />} />
                  <Route path="/cms-preview" element={<CmsPage openEstimate={openEstimate} />} />
                  <Route path="/cart" element={<Cart openAuth={openAuth} />} />
                  <Route path="/checkout" element={<Checkout openAuth={openAuth} />} />
                  <Route path="/booking-history" element={<MyBookings openAuth={openAuth} />} />
                  <Route path="/my-bookings" element={<MyBookings openAuth={openAuth} />} />
                  <Route path="/testimonials" element={<Testimonials />} />
                  <Route path="/order/:id" element={<OrderDetail />} />
                  <Route path="/booking-confirmation/:id" element={<BookingConfirmation />} />
                  <Route path="/not-found" element={<NotFound />} />
                  {/*
                    Phase 4.5b — operator-managed SEO content.
                    /explore is the hub; /:slug renders any
                    published SeoPage. The reserved-slug guard
                    inside SeoPageView preserves the Phase 2.6a-fix
                    invariant that /payment + other system paths
                    still land on NotFound rather than firing an
                    API round-trip.
                  */}
                  <Route path="/explore" element={<ExploreEditorial />} />
                  <Route path="/:slug" element={<SeoPageView />} />
                  {/*
                    Catch-all — multi-segment unknown URLs fall here
                    (e.g. /foo/bar). Single-segment unknowns are
                    already handled by SeoPageView above (it falls
                    through to NotFound on 404).
                  */}
                  <Route path="*" element={<NotFound />} />
                </Routes>
                </Suspense>
              </RuntimeErrorBoundary>
            </motion.div>
          </AnimatePresence>
        </ChunkErrorBoundary>
      </main>

      <Footer />

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
