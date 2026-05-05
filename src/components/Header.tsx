import { motion, AnimatePresence } from "motion/react";
import {
  Menu, X, Phone, MessageCircle, MapPin,
  Facebook, Instagram, Youtube, Linkedin,
  CreditCard, Smartphone, ChevronDown, ChevronRight, Zap, ArrowRight,
  ShoppingCart, User, LogOut, Package, Car
} from "lucide-react";
import { useState, useRef, useLayoutEffect } from "react";
import { BUSINESS_INFO, LOCATIONS } from "../data/businessData";
import { useCart } from "../hooks/useCart";
import { useAuth } from "../hooks/useAuth";
import {
  fetchHome,
  type ServiceCategory as ApiCategory,
  type CategorySubService,
} from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";
import { FEATURES } from "../config/features";

interface SubMenuProps {
  subServices: CategorySubService[];
  categorySlug: string;
  setCurrentPage: (page: string) => void;
  setActiveDropdown: (d: string | null) => void;
  setActiveSubDropdown: (d: string | null) => void;
}

function SubMenu({ subServices, categorySlug, setCurrentPage, setActiveDropdown, setActiveSubDropdown }: SubMenuProps) {
  const menuRef = useRef<HTMLDivElement>(null);

  useLayoutEffect(() => {
    if (menuRef.current && menuRef.current.parentElement) {
      const parentRect = menuRef.current.parentElement.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      
      // Calculate horizontal position (right of the parent if possible, otherwise left)
      let left = parentRect.right;
      if (left + 240 > window.innerWidth) { // 240 is min-w
        left = parentRect.left - 240;
      }
      
      // Calculate starting top position aligned with parent top
      let initialTop = parentRect.top - 1;

      menuRef.current.style.left = `${left}px`;
      menuRef.current.style.top = `${initialTop}px`;

      // Read dimensions after applying initial position
      const rect = menuRef.current.getBoundingClientRect();
      const menuHeight = rect.height;
      
      const headerHeight = 120; // approximate safe zone for header
      const safeTop = headerHeight + 20;
      const safeBottom = viewportHeight - 20;

      let top = initialTop;

      if (top + menuHeight > safeBottom) {
        const requiredShift = (top + menuHeight) - safeBottom;
        const maxPossibleShift = top - safeTop;
        
        if (requiredShift > maxPossibleShift) {
          // If menu is very large (shift exceeds available space above), center it vertically
          top = safeTop + (safeBottom - safeTop - menuHeight) / 2;
          // Ensure it never goes above the safe top
          top = Math.max(safeTop, top);
        } else {
          // Shift submenu upward automatically so it stays inside viewport
          top -= requiredShift;
        }
      }
      
      menuRef.current.style.top = `${top}px`;
    }
  }, [subServices]);

  return (
    <motion.div 
      ref={menuRef}
      initial={{ opacity: 0, x: -5 }}
      animate={{ opacity: 1, x: 0 }}
      exit={{ opacity: 0, x: -5 }}
      transition={{ duration: 0.15 }}
      className="fixed bg-white border border-border shadow-2xl py-2 min-w-[240px] flex flex-col max-h-[80vh] overflow-y-auto scroll-smooth [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-neutral-200 [&::-webkit-scrollbar-thumb]:rounded-full shadow-[inset_0_-15px_15px_-15px_rgba(0,0,0,0.1),inset_0_15px_15px_-15px_rgba(0,0,0,0.1)] z-[10000]"
      style={{ top: '-9999px', left: '-9999px' }}
    >
      {subServices.map((sub) => (
            <button
              key={sub.id}
              onClick={(e) => {
                e.stopPropagation();
                setCurrentPage(`service-${categorySlug}/${sub.slug}`);
                setActiveDropdown(null);
                setActiveSubDropdown(null);
              }}
              className="w-full text-left px-6 py-2.5 text-[11px] font-bold uppercase text-neutral-600 hover:bg-neutral-50 hover:text-primary transition-all border-l-2 border-transparent hover:border-primary block"
            >
              {sub.title}
            </button>
          ))}
    </motion.div>
  );
}

interface HeaderProps {
  currentPage: string;
  setCurrentPage: (page: string) => void;
  openEstimate: () => void;
  openAuth: (tab?: "login" | "signup", redirectTo?: string) => void;
}

export default function Header({ currentPage, setCurrentPage, openEstimate, openAuth }: HeaderProps) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [activeDropdown, setActiveDropdown] = useState<string | null>(null);
  const [activeSubDropdown, setActiveSubDropdown] = useState<string | null>(null);
  const [userMenuOpen, setUserMenuOpen] = useState(false);

  // Cart and auth hooks - drive the header e-commerce icons
  const { count: cartCount } = useCart();
  const { user, isAuthenticated, bootstrapped, logout } = useAuth();

  // ── Service categories + their sub-services come from a single
  //    /home query. Sub-services arrive nested under each category in
  //    the response (Phase 1.6) — no lazy fetch on dropdown open. ──
  const home = useApiQuery(["home"], (signal) => fetchHome(signal));
  const apiCategories: ApiCategory[] = home.data?.service_categories ?? [];

  // O(1) lookup of "category slug → its sub-services". Recomputed on
  // re-render but inexpensive (≤ 12 categories × ≤ ~10 services each)
  // and the data backing it is React-Query-cached.
  const subsByCategorySlug: Record<string, CategorySubService[]> = {};
  for (const c of apiCategories) {
    subsByCategorySlug[c.slug] = c.services ?? [];
  }

  const navItems = [
    { name: "Home", id: "home" },
    { name: "Services", id: "services", hasDropdown: true },
    { name: "Service Centers", id: "service-centers", hasDropdown: true },
    { name: "Insurance", id: "insurance" },
    { name: "Corporate", id: "corporate" },
    { name: "Gallery", id: "gallery" },
    { 
      name: "More", 
      id: "more", 
      hasDropdown: true,
      subItems: [
        { name: "Offers & Deals", id: "offers" },
        { name: "Coupons", id: "coupons" },
        { name: "SEO Page Preview", id: "cms-preview" },
        { name: "Blog", id: "blog" },
        { name: "FAQ", id: "faq" },
        { name: "About Us", id: "about" },
        { name: "Contact", id: "contact" },
      ]
    },
  ];

  const isActiveMenu = (item: any) => {
    if (currentPage === item.id) return true;
    if (item.id === 'services' && currentPage.startsWith('category-')) return true;
    if (item.id === 'services' && currentPage.startsWith('service-') && currentPage !== 'service-centers') return true;
    if (item.id === 'service-centers' && currentPage.startsWith('center-')) return true;
    if (item.id === 'more' && item.subItems?.some((sub: any) => sub.id === currentPage)) return true;
    return false;
  };

  return (
    <header className="sticky top-0 z-[9999] bg-white border-b border-border overflow-visible">
      {/* Top Bar - Blue Background */}
      {/* Phase 2.3.1: removed overflow-hidden — was clipping user menu dropdown */}
      <div className="bg-primary py-2">
        <div className="site-container flex justify-between items-center text-[9px] font-bold uppercase tracking-[0.15em] text-white">
          {/* Left Side */}
          <div className="flex items-center gap-6">
            <span className="flex items-center gap-1.5">
              <MapPin className="w-3 h-3" /> {LOCATIONS.length} Centres Across Delhi NCR
            </span>
            <span className="hidden md:flex items-center gap-1.5">
              Expert Multi-brand Service
            </span>
          </div>

          {/* Right Side */}
          <div className="flex items-center gap-6">
            {/* Social Icons */}
            <div className="flex items-center gap-3 border-r border-white/20 pr-6">
              <a href={BUSINESS_INFO.social.facebook} target="_blank" rel="noopener noreferrer" className="hover:text-white/70 transition-colors">
                <Facebook className="w-3 h-3" />
              </a>
              <a href={BUSINESS_INFO.social.instagram} target="_blank" rel="noopener noreferrer" className="hover:text-white/70 transition-colors">
                <Instagram className="w-3 h-3" />
              </a>
              <a href={BUSINESS_INFO.social.youtube} target="_blank" rel="noopener noreferrer" className="hover:text-white/70 transition-colors">
                <Youtube className="w-3 h-3" />
              </a>
              <a href={BUSINESS_INFO.social.linkedin} target="_blank" rel="noopener noreferrer" className="hover:text-white/70 transition-colors">
                <Linkedin className="w-3 h-3" />
              </a>
            </div>

            {/* Phase 2.6a — "₹ Pay Online" link removed alongside
                the Payment.tsx page deletion. Real payment is
                "Pay at Service Center" (Phase 2.5a) until a real
                gateway lands in Phase 4+. */}

            {/* Auth: Login / Sign Up OR Logged-in user menu.
                When FEATURES.auth is off the entry buttons are hidden so
                we don't surface controls that lead to a "coming soon" modal.
                The user menu branch never shows because isAuthenticated is
                gated to false in the hook. */}
            {!FEATURES.auth ? null : !bootstrapped ? (
              // Phase 2.5.3 — small avatar pulse during the auth
              // hydration window so the header doesn't flicker
              // between "Login / Sign Up" buttons and the user menu
              // on hard-refresh.
              <div className="flex items-center gap-1.5" aria-busy="true">
                <div className="w-5 h-5 bg-white/30 animate-pulse" />
                <div className="hidden sm:block h-3 w-16 bg-white/30 animate-pulse" />
              </div>
            ) : !isAuthenticated ? (
              <div className="flex items-center gap-3">
                <button
                  onClick={() => openAuth("login")}
                  className="flex items-center gap-1.5 hover:opacity-80 transition-all group"
                >
                  <User className="w-3 h-3" />
                  <span className="group-hover:underline decoration-white/40 underline-offset-4 tracking-widest text-[11px] font-bold uppercase">Login</span>
                </button>
                <button
                  onClick={() => openAuth("signup")}
                  className="hidden sm:inline tracking-widest text-[11px] font-bold uppercase hover:opacity-80 transition-all hover:underline decoration-white/40 underline-offset-4"
                >
                  Sign Up
                </button>
              </div>
            ) : (
              <div className="relative">
                <button
                  onClick={() => setUserMenuOpen((o) => !o)}
                  className="flex items-center gap-1.5 hover:opacity-80 transition-all"
                >
                  <div className="w-5 h-5 bg-white text-primary flex items-center justify-center text-[10px] font-black">
                    {user?.name.charAt(0).toUpperCase()}
                  </div>
                  <span className="tracking-widest text-[11px] font-bold uppercase max-w-[100px] truncate">
                    {user?.name.split(" ")[0]}
                  </span>
                  <ChevronDown className={`w-3 h-3 transition-transform ${userMenuOpen ? "rotate-180" : ""}`} />
                </button>

                <AnimatePresence>
                  {userMenuOpen && (
                    <>
                      {/* Backdrop to close on outside click */}
                      <div
                        className="fixed inset-0 z-[9998]"
                        onClick={() => setUserMenuOpen(false)}
                      />
                      <motion.div
                        initial={{ opacity: 0, y: -8 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -8 }}
                        transition={{ duration: 0.15 }}
                        className="absolute right-0 top-full mt-2 bg-white border border-border shadow-2xl w-[300px] max-w-[calc(100vw-1rem)] z-[9999] overflow-hidden"
                      >
                        {/* Profile header — avatar + name + contact */}
                        <div className="px-4 py-4 bg-primary text-white">
                          <div className="flex items-center gap-3">
                            <div className="w-12 h-12 bg-white text-primary flex items-center justify-center text-xl font-black shrink-0">
                              {user?.name.charAt(0).toUpperCase()}
                            </div>
                            <div className="min-w-0">
                              <p className="text-sm font-black uppercase tracking-tighter truncate">
                                {user?.name}
                              </p>
                              <p className="text-[10px] text-white/70 truncate normal-case font-normal tracking-normal">
                                {user?.email}
                              </p>
                              <p className="text-[10px] text-white/70 normal-case font-normal tracking-normal">
                                +91 {user?.phone}
                              </p>
                            </div>
                          </div>
                        </div>

                        {/* Saved Car */}
                        <div className="px-4 py-3 border-b border-border">
                          <p className="text-[9px] font-bold text-neutral-400 uppercase tracking-widest mb-1 flex items-center gap-1">
                            <Car className="w-3 h-3" /> My Car
                          </p>
                          {user?.defaultCar ? (
                            <p className="text-xs font-bold text-neutral-900 normal-case tracking-normal truncate">
                              {user.defaultCar.brand}{" "}
                              {user.defaultCar.model}
                              <span className="text-neutral-400 ml-1">
                                · {user.defaultCar.fuel}
                              </span>
                            </p>
                          ) : (
                            <p className="text-[11px] text-neutral-500 normal-case tracking-normal">
                              Not added — choose one when booking.
                            </p>
                          )}
                        </div>

                        {/* Saved Address */}
                        <div className="px-4 py-3 border-b border-border">
                          <p className="text-[9px] font-bold text-neutral-400 uppercase tracking-widest mb-1 flex items-center gap-1">
                            <MapPin className="w-3 h-3" /> Default Address
                          </p>
                          {user?.addresses && user.addresses.length > 0 ? (
                            <p className="text-[11px] text-neutral-700 normal-case tracking-normal leading-relaxed line-clamp-2">
                              {user.addresses.find((a) => a.isDefault)?.address ||
                                user.addresses[0].address}
                            </p>
                          ) : (
                            <p className="text-[11px] text-neutral-500 normal-case tracking-normal">
                              Not added — fill once at checkout.
                            </p>
                          )}
                        </div>

                        {/* Action buttons */}
                        <button
                          onClick={() => {
                            setCurrentPage("my-bookings");
                            setUserMenuOpen(false);
                          }}
                          className="w-full text-left px-4 py-3 text-[11px] font-bold uppercase text-neutral-700 hover:bg-neutral-50 hover:text-primary transition-colors flex items-center gap-2 tracking-widest border-b border-border"
                        >
                          {/* Phase 2.6a — booking-count badge dropped.
                              user.bookings was the legacy local array
                              (always []); real orders live on the
                              server. The /booking-history page
                              renders the live count. */}
                          <Package className="w-3.5 h-3.5" /> My Bookings
                        </button>
                        <button
                          onClick={() => {
                            setCurrentPage("cart");
                            setUserMenuOpen(false);
                          }}
                          className="w-full text-left px-4 py-3 text-[11px] font-bold uppercase text-neutral-700 hover:bg-neutral-50 hover:text-primary transition-colors flex items-center gap-2 tracking-widest border-b border-border"
                        >
                          <ShoppingCart className="w-3.5 h-3.5" /> My Cart
                          {cartCount > 0 && (
                            <span className="ml-auto bg-primary text-white text-[9px] px-1.5 py-0.5 normal-case tracking-normal">
                              {cartCount}
                            </span>
                          )}
                        </button>
                        <button
                          onClick={() => {
                            if (confirm("Log out of your account?")) {
                              logout();
                              setUserMenuOpen(false);
                            }
                          }}
                          className="w-full text-left px-4 py-3 text-[11px] font-bold uppercase text-neutral-500 hover:bg-neutral-50 hover:text-accent-dark transition-colors flex items-center gap-2 tracking-widest"
                        >
                          <LogOut className="w-3.5 h-3.5" /> Logout
                        </button>
                      </motion.div>
                    </>
                  )}
                </AnimatePresence>
              </div>
            )}

            {/* Divider */}
            <span className="w-px h-3 bg-white/20" />

            {/* Cart icon with count badge */}
            <button
              onClick={() => setCurrentPage("cart")}
              aria-label="View cart"
              className="relative flex items-center hover:opacity-80 transition-all"
            >
              <ShoppingCart className="w-4 h-4" />
              {cartCount > 0 && (
                <span className="absolute -top-1.5 -right-2 bg-white text-primary text-[9px] font-black w-4 h-4 flex items-center justify-center leading-none">
                  {cartCount > 9 ? "9+" : cartCount}
                </span>
              )}
            </button>
          </div>
        </div>
      </div>

      <div className="site-container">
        <div className="flex items-center justify-between h-20">
          {/* Logo */}
          <button 
            onClick={() => setCurrentPage("home")}
            className="flex items-center gap-4 shrink-0"
          >
            <div className="flex flex-col items-start">
              <div className="flex items-baseline gap-1">
                <span className="text-3xl font-black tracking-tighter text-primary leading-none">ACR</span>
                <span className="text-[7px] font-bold text-neutral-900 uppercase tracking-tighter">TM</span>
              </div>
              <div className="h-px w-full bg-neutral-900 mt-0.5" />
              <span className="text-[7px] font-black text-neutral-900 uppercase tracking-tighter mt-0.5">
                All Cars. One Repair Stop
              </span>
            </div>
            <div className="w-px h-8 bg-border hidden md:block" />
            <div className="hidden md:flex flex-col items-start">
              <span className="text-base font-black text-neutral-900 uppercase leading-none tracking-tighter">Collision Repair</span>
              <span className="text-[9px] font-bold text-neutral-900 uppercase leading-none tracking-tighter mt-0.5">& Mechanical Centre</span>
            </div>
          </button>

          {/* Desktop Nav - All in one line */}
          <nav className="hidden lg:flex items-center gap-[20px] xl:gap-[28px] ml-auto whitespace-nowrap">
            {navItems.map((item) => {
              const active = isActiveMenu(item);

              return (
              <div 
                key={item.id} 
                className="relative group h-20 flex items-center"
                onMouseEnter={() => item.hasDropdown && setActiveDropdown(item.id)}
                onMouseLeave={() => {
                  setActiveDropdown(null);
                  setActiveSubDropdown(null);
                }}
              >
                <button
                  onClick={() => {
                    setCurrentPage(item.id);
                    setActiveDropdown(null);
                  }}
                  className={`text-[13px] xl:text-[14px] tracking-[0.3px] transition-all duration-300 flex items-center gap-1 py-2 relative hover:text-primary ${
                    active ? "text-primary font-bold scale-105 -translate-y-0.5" : "text-neutral-600 font-medium"
                  }`}
                >
                  {item.name}
                  {item.hasDropdown && <ChevronDown className={`w-3 h-3 transition-transform duration-300 ${activeDropdown === item.id ? 'rotate-180' : ''}`} />}
                </button>

                {/* Dropdown Menu */}
                <AnimatePresence>
                  {item.hasDropdown && activeDropdown === item.id && (
                    <motion.div
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: 10 }}
                      transition={{ duration: 0.2 }}
                      className="absolute top-full left-0 bg-white border border-border shadow-2xl py-4 z-[9999] min-w-[260px] whitespace-normal"
                    >
                      <div className="flex flex-col relative">
                        {item.id === "services" && (
                          home.isLoading ? (
                            Array.from({ length: 6 }).map((_, i) => (
                              <div
                                key={`catsk-${i}`}
                                className="mx-6 my-2 h-3 w-40 bg-neutral-200 animate-pulse rounded"
                              />
                            ))
                          ) : home.error ? (
                            <p className="px-6 py-3 text-[11px] font-bold uppercase tracking-widest text-accent-dark">
                              Could not load services.
                            </p>
                          ) : (
                          apiCategories.map((category) => {
                            const subServices = subsByCategorySlug[category.slug] ?? [];
                            const subActive = activeSubDropdown === String(category.id);
                            const hasSubs = subServices.length > 0;
                            return (
                              <div
                                key={category.id}
                                className="relative group/sub"
                                onMouseEnter={() => setActiveSubDropdown(String(category.id))}
                                onMouseLeave={() => setActiveSubDropdown(null)}
                              >
                                <button
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    setCurrentPage(`category-${category.slug}`);
                                    setActiveDropdown(null);
                                    setActiveSubDropdown(null);
                                  }}
                                  className="w-full flex items-center justify-between text-left px-6 py-2.5 text-[11px] font-bold uppercase text-neutral-600 hover:bg-neutral-50 hover:text-primary transition-all border-l-2 border-transparent hover:border-primary"
                                >
                                  {category.title}
                                  {hasSubs && <ArrowRight className="w-4 h-4 ml-2" />}
                                </button>

                                {/* Third Level Dropdown (Sub Services) */}
                                <AnimatePresence>
                                  {subActive && hasSubs && (
                                    <SubMenu
                                      subServices={subServices}
                                      categorySlug={category.slug}
                                      setCurrentPage={setCurrentPage}
                                      setActiveDropdown={setActiveDropdown}
                                      setActiveSubDropdown={setActiveSubDropdown}
                                    />
                                  )}
                                </AnimatePresence>
                              </div>
                            );
                          })
                          )
                        )}
                        {item.id === "service-centers" && (
                          LOCATIONS.map((loc) => (
                            <button
                              key={loc.id}
                              onClick={(e) => {
                                e.stopPropagation();
                                setCurrentPage(`center-${loc.id}`);
                                setActiveDropdown(null);
                              }}
                              className="w-full text-left px-6 py-2.5 text-[11px] font-bold uppercase text-neutral-600 hover:bg-neutral-50 hover:text-primary transition-all border-l-2 border-transparent hover:border-primary"
                            >
                              {loc.name}
                            </button>
                          ))
                        )}
                        {item.id === "more" && item.subItems && (
                          item.subItems.map((sub) => (
                            <button
                              key={sub.id}
                              onClick={(e) => {
                                e.stopPropagation();
                                setCurrentPage(sub.id);
                                setActiveDropdown(null);
                              }}
                              className="w-full text-left px-6 py-2.5 text-[11px] font-bold uppercase text-neutral-600 hover:bg-neutral-50 hover:text-primary transition-all border-l-2 border-transparent hover:border-primary"
                            >
                              {sub.name}
                            </button>
                          ))
                        )}
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>
            )})}
          </nav>

          {/* CTA Button */}
          <div className="hidden lg:block shrink-0 ml-8">
            <button 
              onClick={openEstimate}
              className="btn-ink btn-ink-primary px-6 py-2.5 text-[12px] font-bold uppercase tracking-widest shadow-lg hover:shadow-primary/20"
            >
              Get Estimate <ArrowRight className="w-4 h-4 btn-arrow" />
            </button>
          </div>

          {/* Mobile Menu Toggle */}
          <button 
            className="lg:hidden p-2 text-neutral-900"
            onClick={() => setIsMenuOpen(!isMenuOpen)}
          >
            {isMenuOpen ? <X /> : <Menu />}
          </button>
        </div>
      </div>

      {/* Mobile Menu */}
      <AnimatePresence>
        {isMenuOpen && (
          <motion.div 
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: "auto" }}
            exit={{ opacity: 0, height: 0 }}
            className="lg:hidden bg-white border-t border-border overflow-hidden"
          >
            <div className="site-container py-8 flex flex-col gap-4">
              {navItems.map((item) => (
                <div key={item.id} className="flex flex-col">
                  <button
                    onClick={() => {
                      if (!item.hasDropdown) {
                        setCurrentPage(item.id);
                        setIsMenuOpen(false);
                      } else {
                        setActiveDropdown(activeDropdown === item.id ? null : item.id);
                      }
                    }}
                    className={`text-left text-xl font-black uppercase tracking-tighter flex items-center justify-between py-2 ${
                      currentPage === item.id ? "text-primary" : "text-neutral-900"
                    }`}
                  >
                    {item.name}
                    {item.hasDropdown && <ChevronDown className={`w-5 h-5 transition-transform ${activeDropdown === item.id ? 'rotate-180' : ''}`} />}
                  </button>
                  
                  {item.hasDropdown && activeDropdown === item.id && (
                    <div className="flex flex-col pl-4 border-l-2 border-primary/20 mt-2 gap-2">
                      {item.id === "services" && (
                        home.isLoading ? (
                          Array.from({ length: 5 }).map((_, i) => (
                            <div
                              key={`mcatsk-${i}`}
                              className="my-2 h-4 w-44 bg-neutral-200 animate-pulse rounded"
                            />
                          ))
                        ) : home.error ? (
                          <p className="py-2 text-xs font-bold uppercase tracking-widest text-accent-dark">
                            Could not load services.
                          </p>
                        ) : (
                          apiCategories.map((category) => {
                            const subServices = subsByCategorySlug[category.slug] ?? [];
                            return (
                              <div key={category.id} className="flex flex-col">
                                <button
                                  onClick={() => {
                                    setCurrentPage(`category-${category.slug}`);
                                    setIsMenuOpen(false);
                                  }}
                                  className="text-left py-2 text-sm font-bold uppercase text-neutral-500 hover:text-primary transition-colors"
                                >
                                  {category.title}
                                </button>
                                {subServices.length > 0 && (
                                  <div className="flex flex-col pl-4 border-l-2 border-border/50 ml-2 mb-2 gap-1">
                                    {subServices.map((sub) => (
                                      <button
                                        key={sub.id}
                                        onClick={() => {
                                          setCurrentPage(`service-${category.slug}/${sub.slug}`);
                                          setIsMenuOpen(false);
                                        }}
                                        className="text-left py-1.5 text-xs font-bold uppercase text-neutral-400 hover:text-primary transition-colors"
                                      >
                                        {sub.title}
                                      </button>
                                    ))}
                                  </div>
                                )}
                              </div>
                            );
                          })
                        )
                      )}
                      {item.id === "service-centers" && (
                        LOCATIONS.map((loc) => (
                          <button
                            key={loc.id}
                            onClick={() => {
                              setCurrentPage(`center-${loc.id}`);
                              setIsMenuOpen(false);
                            }}
                            className="text-left py-2 text-sm font-bold uppercase text-neutral-500"
                          >
                            {loc.name}
                          </button>
                        ))
                      )}
                      {item.id === "more" && item.subItems && (
                        item.subItems.map((sub) => (
                          <button
                            key={sub.id}
                            onClick={() => {
                              setCurrentPage(sub.id);
                              setIsMenuOpen(false);
                            }}
                            className="text-left py-2 text-sm font-bold uppercase text-neutral-500"
                          >
                            {sub.name}
                          </button>
                        ))
                      )}
                    </div>
                  )}
                </div>
              ))}
              <div className="mt-6 pt-6 border-t border-border flex flex-col gap-4">
                <a href={`tel:+91${BUSINESS_INFO.phone}`} className="flex items-center gap-4 text-lg font-black uppercase text-neutral-900">
                  <Phone className="w-5 h-5 text-primary" />
                  +91 {BUSINESS_INFO.phone}
                </a>
                <button 
                  onClick={() => {
                    openEstimate();
                    setIsMenuOpen(false);
                  }}
                  className="btn-ink btn-ink-primary w-full py-4 font-black uppercase tracking-tighter text-lg flex items-center justify-center gap-2"
                >
                  Get Estimate <ArrowRight className="w-5 h-5 btn-arrow" />
                </button>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  );
}

