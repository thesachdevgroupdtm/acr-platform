/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState, useEffect } from "react";
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
import Payment from "./pages/Payment";
import MyBookings from "./pages/MyBookings";
import OrderDetail from "./pages/OrderDetail";
import BookingConfirmation from "./pages/BookingConfirmation";
import EstimateProcess from "./components/EstimateProcess";
import AuthModal from "./components/AuthModal";
import RouteResolutionLoader from "./components/RouteResolutionLoader";
import { motion, AnimatePresence } from "motion/react";
import { BUSINESS_INFO } from "./data/businessData";
import { MessageCircle } from "lucide-react";

/**
 * Phase 2.5.1 — minimal pathname → currentPage mapping. Used only
 * on initial mount so that hard-refreshing /checkout, /order-12,
 * etc. lands directly on the right page instead of briefly
 * flashing Home (the default state). Click navigation continues to
 * use the existing `setCurrentPage` flow without URL sync — the
 * full router migration is a Phase 3 deliverable.
 */
function parsePageFromUrl(loc: Location): string {
  const raw = (loc.pathname || "/").replace(/\/+$/, "");
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

  return stripped;
}

export default function App() {
  const [currentPage, setCurrentPage] = useState("home");
  // Phase 2.5.1 — render gate. False until the URL has been parsed
  // on mount (one tick). Prevents the Home flash on hard-refresh
  // of any non-/ URL.
  const [isRouteResolved, setIsRouteResolved] = useState(false);

  useEffect(() => {
    if (typeof window === "undefined") {
      setIsRouteResolved(true);
      return;
    }
    const initial = parsePageFromUrl(window.location);
    if (initial !== "home") {
      setCurrentPage(initial);
    }
    setIsRouteResolved(true);
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
      return <ServiceCenterDetail centerId={centerId} setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
    }

    if (currentPage.startsWith("category-")) {
      const categorySlug = currentPage.replace("category-", "");
      // Lazy load or just import ServiceCategory
      return <ServiceCategory categorySlug={categorySlug} setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
    }

    if (currentPage.startsWith("service-") && currentPage !== "service-centers") {
      // expected format: service-{categorySlug}/{serviceSlug}
      const fullSlug = currentPage.replace("service-", "");
      const [categorySlug, serviceSlug] = fullSlug.split("/");
      return <ServiceDetail categorySlug={categorySlug} serviceSlug={serviceSlug} setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
    }

    if (currentPage === "cms-preview") {
      return <CmsPage setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
    }

    // Phase 2.5a — order detail (`order-{id}`).
    if (currentPage.startsWith("order-")) {
      const id = Number(currentPage.replace("order-", ""));
      if (Number.isFinite(id) && id > 0) {
        return <OrderDetail orderId={id} setCurrentPage={setCurrentPage} />;
      }
    }

    // Phase 2.5a — booking confirmation (`booking-confirmation-{id}`).
    if (currentPage.startsWith("booking-confirmation-")) {
      const id = Number(currentPage.replace("booking-confirmation-", ""));
      if (Number.isFinite(id) && id > 0) {
        return (
          <BookingConfirmation orderId={id} setCurrentPage={setCurrentPage} />
        );
      }
    }

    switch (currentPage) {
      case "home":
        return <Home setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "services":
        return <Services setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "service-centers":
        return <ServiceCenters setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "insurance":
        return <Insurance setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "corporate":
        return <Corporate setCurrentPage={setCurrentPage} openEstimate={() => openEstimate(true)} />;
      case "gallery":
        return <Gallery setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "about":
        return <About setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "contact":
        return <Contact setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "offers":
        return <Offers setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "coupons":
        return <Coupons setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "sitemap":
        return <Sitemap setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
      case "cart":
        return <Cart setCurrentPage={setCurrentPage} openAuth={openAuth} />;
      case "checkout":
        return <Checkout setCurrentPage={setCurrentPage} openAuth={openAuth} />;
      case "payment":
        return <Payment setCurrentPage={setCurrentPage} />;
      case "my-bookings":
        return <MyBookings setCurrentPage={setCurrentPage} openAuth={openAuth} />;
      default:
        return <Home setCurrentPage={setCurrentPage} openEstimate={openEstimate} />;
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
        setCurrentPage={setCurrentPage} 
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
        setCurrentPage={setCurrentPage}
        onClose={() => setAuthModal((prev) => ({ ...prev, isOpen: false }))}
      />

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


