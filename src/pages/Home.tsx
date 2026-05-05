import { useState, FormEvent, useRef, useEffect, useCallback } from "react";
import { motion, AnimatePresence } from "motion/react";
import {
  ArrowRight, ShieldCheck, Zap, Award, Clock, ChevronRight, ChevronLeft, Car,
  MessageCircle, Star, CheckCircle2, Play, Shield, Loader2,
  Users, Wrench, IndianRupee, FileText, Truck, Phone, MapPin, Search, Quote
} from "lucide-react";
import { BUSINESS_INFO, LOCATIONS, TESTIMONIALS } from "../data/businessData";
import {
  fetchHome,
  type ServiceCategory as ApiCategory,
  type CategorySubService,
} from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";
import HomeFAQ from "../components/HomeFAQ";

interface HomeProps {
  setCurrentPage: (page: string) => void;
  openEstimate: (isCorporate?: boolean, initialService?: string) => void;
}

export default function Home({ setCurrentPage, openEstimate }: HomeProps) {
  // ─── API-only data loading. No static fallback. Skeletons during load. ──
  const home = useApiQuery(["home"], (signal) => fetchHome(signal));

  // Categories list (string ids preserved for downstream filtering).
  const serviceCategories: Array<{
    id: string;
    slug: string;
    title: string;
    description: string;
  }> = (home.data?.service_categories ?? []).map((c: ApiCategory) => ({
    id: String(c.id),
    slug: c.slug,
    title: c.title,
    description: c.description ?? "",
  }));

  // Sub-services come nested under each category in the /home payload
  // (Phase 1.6 — eliminates the previous N+1 of 12 /services/{slug}
  // requests). Flattened here for the all-services carousel; each entry
  // remembers its parent category's slug for navigation.
  const allSubServices: Array<CategorySubService & { _categorySlug: string }> = [];
  for (const c of home.data?.service_categories ?? []) {
    for (const s of c.services ?? []) {
      allSubServices.push({ ...s, _categorySlug: c.slug });
    }
  }
  // The /home query owns loading + error state — no separate query needed.
  const subsLoading = home.isLoading;
  const subsError   = home.error;

  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    email: "",
    brand: "",
    model: "",
    service: "",
    location: ""
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState("All Services");
  const [searchQuery, setSearchQuery] = useState("");
  const scrollRef = useRef<HTMLDivElement>(null);
  const transformScrollRef = useRef<HTMLDivElement>(null);
  const testimonialScrollRef = useRef<HTMLDivElement>(null);
  const categoryScrollRef = useRef<HTMLDivElement>(null);

  const [isExpertiseHovered, setIsExpertiseHovered] = useState(false);
  const [isTransformHovered, setIsTransformHovered] = useState(false);
  const [isTestimonialHovered, setIsTestimonialHovered] = useState(false);
  const [isLocationHovered, setIsLocationHovered] = useState(false);
  const [activeLocationIndex, setActiveLocationIndex] = useState(0);
  const [expandedLocationIndex, setExpandedLocationIndex] = useState<number | null>(null);

  const categories = ["All Services", ...serviceCategories.map(c => c.title)];

  const transformations = [
    {
      title: "Front Impact Restoration",
      before: "https://images.unsplash.com/photo-1590674899484-d5640e854abe?auto=format&fit=crop&q=80&w=800",
      after: "https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80&w=800"
    },
    {
      title: "Door Dent & Paint Correction",
      before: "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=800",
      after: "https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&q=80&w=800"
    },
    {
      title: "Full Body Respray",
      before: "https://images.unsplash.com/photo-1530046339160-ce3e5b0c7a2f?auto=format&fit=crop&q=80&w=800",
      after: "https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?auto=format&fit=crop&q=80&w=800"
    },
    {
      title: "Luxury Sedan Restoration",
      before: "https://images.unsplash.com/photo-1590674899484-d5640e854abe?auto=format&fit=crop&q=80&w=800",
      after: "https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80&w=800"
    },
    {
      title: "Quarter Panel Repair",
      before: "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=800",
      after: "https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&q=80&w=800"
    },
    {
      title: "Alloy Wheel Refurbishment",
      before: "https://images.unsplash.com/photo-1530046339160-ce3e5b0c7a2f?auto=format&fit=crop&q=80&w=800",
      after: "https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?auto=format&fit=crop&q=80&w=800"
    }
  ];

  const filteredServices = allSubServices.filter((service) => {
    const parentCat = serviceCategories.find((c) => c.slug === service._categorySlug);
    const matchesCategory =
      selectedCategory === "All Services" || parentCat?.title === selectedCategory;
    const matchesSearch = service.title
      .toLowerCase()
      .includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  const scroll = useCallback((ref: { current: HTMLDivElement | null }, direction: 'left' | 'right') => {
    if (ref.current) {
      const { scrollLeft, clientWidth, scrollWidth } = ref.current;
      let scrollTo = direction === 'left' ? scrollLeft - clientWidth : scrollLeft + clientWidth;

      // Loop back to start or end
      if (scrollTo < 0) scrollTo = scrollWidth - clientWidth;
      if (scrollTo >= scrollWidth - 10) scrollTo = 0;

      ref.current.scrollTo({ left: scrollTo, behavior: 'smooth' });
    }
  }, []);

  // Auto-scroll logic
  useEffect(() => {
    const expertiseInterval = setInterval(() => {
      if (!isExpertiseHovered) scroll(scrollRef, 'right');
    }, 4000);

    const transformInterval = setInterval(() => {
      if (!isTransformHovered) scroll(transformScrollRef, 'right');
    }, 5000);

    const testimonialInterval = setInterval(() => {
      if (!isTestimonialHovered) scroll(testimonialScrollRef, 'right');
    }, 3500);

    const locationInterval = setInterval(() => {
      if (!isLocationHovered) {
        setActiveLocationIndex((prev) => (prev + 1) % LOCATIONS.length);
      }
    }, 4000);

    return () => {
      clearInterval(expertiseInterval);
      clearInterval(transformInterval);
      clearInterval(testimonialInterval);
      clearInterval(locationInterval);
    };
  }, [isExpertiseHovered, isTransformHovered, isTestimonialHovered, isLocationHovered, scroll]);

  const validate = () => {
    const newErrors: Record<string, string> = {};
    // Name: required, min 2 chars, alphabets only
    if (!formData.name.trim()) newErrors.name = "Full name is required";
    else if (formData.name.trim().length < 2) newErrors.name = "Enter at least 2 characters";
    else if (!/^[A-Za-z][A-Za-z\s.'-]*$/.test(formData.name.trim())) newErrors.name = "Only alphabets are allowed";
    // Phone: required, exactly 10 digits
    if (!formData.phone) newErrors.phone = "Phone number is required";
    else if (!/^\d{10}$/.test(formData.phone)) newErrors.phone = "Enter exactly 10 digits";
    // Email: required, valid format
    if (!formData.email) newErrors.email = "Email is required";
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) newErrors.email = "Enter a valid email";
    // Location required
    if (!formData.location) newErrors.location = "Please select a location";
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (validate()) {
      openEstimate(false, formData.service);
    }
  };

  return (
    <div className="overflow-hidden bg-white">
      {home.error && (
        <div className="fixed bottom-4 right-4 z-[9999] max-w-xs bg-accent-dark text-white text-xs font-bold uppercase tracking-widest px-4 py-3 shadow-lg">
          API: {home.error}
        </div>
      )}
      {/* Hero Section */}
      <section className="relative min-h-[85vh] lg:min-h-[600px] flex items-center bg-white">
        <div className="absolute inset-0 z-0">
          <div className="absolute inset-0 bg-gradient-to-r from-white via-white/80 to-transparent z-10" />
          <img
            src="https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&q=80&w=2000"
            alt="Car Repair Workshop"
            className="w-full h-full object-cover opacity-40"
            referrerPolicy="no-referrer"
          />
        </div>

        <div className="site-container relative z-20">
          <div className="grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-12 items-center">
            <motion.div
              initial={{ opacity: 0, x: -30 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8 }}
            >
              <div className="flex items-center gap-3 mb-6">
                <span className="text-accent font-bold uppercase tracking-widest text-[10px]">
                  India's Fastest-Growing Self-Owned Network
                </span>
                <div className="w-12 h-px bg-accent" />
              </div>

              <h1 className="text-5xl md:text-6xl lg:text-7xl font-black uppercase tracking-tighter leading-[1.1] mb-6 text-primary-dark">
                Flawless <br />
                <span className="text-primary italic font-black">Restoration.</span>
              </h1>

              <p className="text-base text-muted max-w-lg mb-8 leading-relaxed font-normal">
                {BUSINESS_INFO.tagline}
              </p>

              <div className="flex flex-wrap gap-4 mb-12">
                <button
                  onClick={() => openEstimate()}
                  className="btn-ink btn-ink-primary px-8 py-4 font-bold text-sm"
                >
                  Book Now <ArrowRight className="w-4 h-4 btn-arrow" />
                </button>
              </div>

              <div className="flex items-center gap-10">
                <div>
                  <div className="text-2xl font-bold text-primary-dark">50,000+</div>
                  <div className="text-[10px] uppercase tracking-widest font-medium text-muted">Cars Served</div>
                </div>
                <div className="w-px h-8 bg-border" />
                <div>
                  <div className="text-2xl font-bold text-primary-dark">{LOCATIONS.length}</div>
                  <div className="text-[10px] uppercase tracking-widest font-medium text-muted">Centres in NCR</div>
                </div>
                <div className="w-px h-8 bg-border" />
                <div className="flex items-center gap-2">
                  <div className="flex text-accent">
                    {[...Array(5)].map((_, i) => <Star key={i} className="w-3 h-3 fill-current" />)}
                  </div>
                  <div className="text-[10px] uppercase tracking-widest font-medium text-muted">4.9 Rating</div>
                </div>
              </div>
            </motion.div>

            {/* Quick Booking Widget */}
            <motion.div
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.2 }}
              className="bg-white p-8 border border-border shadow-sm relative hidden lg:block w-full max-w-md ml-auto"
            >
              <h3 className="text-xl font-black uppercase tracking-tight mb-6 text-primary-dark">Quick Estimate</h3>

              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                      <label className="block text-[10px] uppercase tracking-widest text-muted font-medium">Customer Name</label>
                      <input
                        type="text"
                        placeholder="Full Name"
                        value={formData.name}
                        onChange={(e) => {
                          // Allow only alphabets, spaces, dots, hyphens, apostrophes
                          const cleaned = e.target.value.replace(/[^A-Za-z\s.'-]/g, '');
                          setFormData({ ...formData, name: cleaned });
                          if (errors.name) setErrors(er => ({ ...er, name: '' }));
                        }}
                        className={`w-full bg-surface border ${errors.name ? 'border-accent-dark' : 'border-border'} p-3 text-sm text-primary-dark focus:border-primary outline-none transition-all placeholder:text-muted/50`}
                      />
                      {errors.name && <p className="text-[10px] font-bold text-accent-dark mt-1">{errors.name}</p>}
                    </div>
                    <div className="space-y-1.5">
                      <label className="block text-[10px] uppercase tracking-widest text-muted font-medium">Email Address</label>
                      <input
                        type="email"
                        placeholder="Email"
                        value={formData.email}
                        onChange={(e) => {
                          setFormData({ ...formData, email: e.target.value });
                          if (errors.email) setErrors(er => ({ ...er, email: '' }));
                        }}
                        className={`w-full bg-surface border ${errors.email ? 'border-accent-dark' : 'border-border'} p-3 text-sm text-primary-dark focus:border-primary outline-none transition-all placeholder:text-muted/50`}
                      />
                      {errors.email && <p className="text-[10px] font-bold text-accent-dark mt-1">{errors.email}</p>}
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1.5 col-span-2">
                      <label className="block text-[10px] uppercase tracking-widest text-muted font-medium">Service Type</label>
                      <select
                        value={formData.service}
                        onChange={(e) => setFormData({ ...formData, service: e.target.value })}
                        className={`w-full bg-surface border border-border p-3 text-sm text-primary-dark focus:border-primary outline-none transition-all`}
                      >
                        <option value="">Select Service Needed</option>
                        {serviceCategories.map(cat => (
                          <option key={cat.id} value={cat.title}>{cat.title}</option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                      <label className="block text-[10px] uppercase tracking-widest text-muted font-medium">Phone</label>
                      <input
                        type="tel"
                        inputMode="numeric"
                        placeholder="10 Digits"
                        maxLength={10}
                        value={formData.phone}
                        onChange={(e) => {
                          setFormData({ ...formData, phone: e.target.value.replace(/\D/g, '').slice(0, 10) });
                          if (errors.phone) setErrors(er => ({ ...er, phone: '' }));
                        }}
                        className={`w-full bg-surface border ${errors.phone ? 'border-accent-dark' : 'border-border'} p-3 text-sm text-primary-dark focus:border-primary outline-none transition-all placeholder:text-muted/50`}
                      />
                      {errors.phone && <p className="text-[10px] font-bold text-accent-dark mt-1">{errors.phone}</p>}
                    </div>
                    <div className="space-y-1.5">
                      <label className="block text-[10px] uppercase tracking-widest text-muted font-medium">Location</label>
                      <select
                        value={formData.location}
                        onChange={(e) => {
                          setFormData({ ...formData, location: e.target.value });
                          if (errors.location) setErrors(er => ({ ...er, location: '' }));
                        }}
                        className={`w-full bg-surface border ${errors.location ? 'border-accent-dark' : 'border-border'} p-3 text-sm text-primary-dark focus:border-primary outline-none transition-all`}
                      >
                        <option value="">Select Location</option>
                        {LOCATIONS.map(loc => (
                          <option key={loc.id} value={loc.name}>{loc.name}</option>
                        ))}
                      </select>
                      {errors.location && <p className="text-[10px] font-bold text-accent-dark mt-1">{errors.location}</p>}
                    </div>
                  </div>

                  <div className="flex items-start gap-2 pt-2 pb-1">
                    <input type="checkbox" id="consent" className="mt-1 accent-primary" required />
                    <label htmlFor="consent" className="text-xs text-muted leading-tight cursor-pointer">
                      I agree to receive communications regarding my estimate and service requests.
                    </label>
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="btn-ink btn-ink-primary w-full py-4 font-black uppercase tracking-tight text-sm disabled:opacity-70 mt-2"
                >
                  <span className="relative z-10 flex items-center justify-center gap-2">
                    {isSubmitting ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin" /> Processing...
                      </>
                    ) : (
                      <>Get Estimate <ArrowRight className="w-4 h-4 btn-arrow" /></>
                    )}
                  </span>
                </button>
              </form>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Marquee - Brand Partnerships */}
      <section className="bg-white py-8 overflow-hidden border-y border-border">
        <div className="site-container mb-6 text-center">
          <span className="text-[10px] font-bold uppercase tracking-widest text-muted">Trusted By / Brands We Work With</span>
        </div>
        <div className="flex whitespace-nowrap animate-marquee items-center mb-1">
          {[...Array(10)].map((_, i) => (
            <div key={i} className="flex items-center gap-20 mx-10 grayscale opacity-40 hover:grayscale-0 hover:opacity-100 transition-all cursor-default">
              <span className="text-primary-dark text-xl font-bold uppercase">HDFC ERGO</span>
              <span className="text-primary-dark text-xl font-bold uppercase">ICICI LOMBARD</span>
              <span className="text-primary-dark text-xl font-bold uppercase">BAJAJ ALLIANZ</span>
              <span className="text-primary-dark text-xl font-bold uppercase">TATA AIG</span>
              <span className="text-primary-dark text-xl font-bold uppercase">NEW INDIA</span>
            </div>
          ))}
        </div>
      </section>

      {/* About Section - Compact & High Impact */}
      <section className="bg-surface py-10 md:py-24">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-[1fr_1fr] gap-16 items-center">
            {/* Left Side: Clean Image */}
            <div className="relative">
              <div className="absolute -top-4 -left-4 w-24 h-24 border-t border-l border-primary/20 z-0" />
              <img
                src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80&w=1200"
                alt="Auto Car Repair Workshop"
                className="relative z-10 w-full aspect-video lg:aspect-square object-cover shadow-sm transition-all duration-700"
                referrerPolicy="no-referrer"
              />
              <div className="absolute -bottom-4 -right-4 w-24 h-24 border-b border-r border-primary/20 z-0" />
            </div>

            {/* Right Side: Focused Content */}
            <div className="space-y-8">
              <div>
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-8 h-0.5 bg-accent" />
                  <span className="text-xs uppercase tracking-widest text-muted font-bold">The ACR Standard</span>
                </div>
                <h2 className="text-3xl md:text-4xl leading-tight mb-4 text-primary-dark font-black tracking-tighter uppercase">
                  More Than Repairs. <br />
                  <span className="text-primary italic">Absolute Trust.</span>
                </h2>
              </div>

              <p className="text-sm md:text-base text-muted leading-relaxed max-w-xl">
                We are India's fastest-growing self-owned multibrand service and collision repair network.
                By keeping all centers strictly self-owned—never outsourced—we guarantee highly transparent, consistent dealership-level quality at unmatched speeds for every make and model.
              </p>

              <div className="grid grid-cols-2 gap-6">
                {[
                  { icon: Users, title: "100% Self-Owned", desc: "No outsourcing. Complete control." },
                  { icon: ShieldCheck, title: "Certified Masters", desc: "Multi-brand specialist engineers." },
                  { icon: Zap, title: "Lightning Fast", desc: "Rapid processing and delivery." },
                  { icon: Star, title: "Unbeatable Scale", desc: "India's fastest growing network." }
                ].map((item, i) => (
                  <div key={i} className="flex items-start gap-4">
                    <div className="shrink-0 w-8 h-8 flex items-center text-primary">
                      <item.icon className="w-6 h-6" />
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-primary-dark mb-1">{item.title}</h4>
                      <p className="text-muted text-xs leading-tight">{item.desc}</p>
                    </div>
                  </div>
                ))}
              </div>

              <button
                onClick={() => setCurrentPage("services")}
                className="btn-ink btn-ink-primary px-8 py-3.5 font-bold text-sm"
              >
                Explore Services <ArrowRight className="w-4 h-4 btn-arrow" />
              </button>
            </div>
          </div>

          {/* Compact Stats Row */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mt-20 pt-10 border-t border-border">
            {[
              { label: "50,000+", sub: "Cars Serviced" },
              { label: "4+", sub: "Service Centers" },
              { label: "15+", sub: "Years Experience" },
              { label: "98%", sub: "Customer Satisfaction" }
            ].map((stat, i) => (
              <div key={i} className="text-center">
                <div className="text-3xl font-bold text-primary-dark tracking-normal mb-2">{stat.label}</div>
                <div className="text-xs font-bold text-muted uppercase tracking-widest">{stat.sub}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Categorized Services Section - Smart Service Discovery */}
      <section className="py-20 bg-surface border-b border-border overflow-hidden">
        <div className="site-container">
          <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
            <div>
              <div className="flex items-center gap-3 mb-4">
                <div className="w-8 h-0.5 bg-accent" />
                <span className="text-xs uppercase tracking-widest text-muted font-bold">Our Expertise</span>
              </div>
              <h2 className="text-3xl md:text-4xl font-black uppercase tracking-tighter text-primary-dark mb-4">
                Specialized <span className="text-primary italic font-black">Care.</span>
              </h2>
              <p className="text-sm text-muted max-w-md leading-relaxed">
                Discover specialized care for every part of your vehicle, from structural repair to aesthetic detailing.
              </p>
            </div>

            {/* Search Input */}
            <div className="relative w-full md:w-64 group">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted group-focus-within:text-primary transition-colors" />
              <input
                type="text"
                placeholder="Search service..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full bg-white border border-border pl-11 pr-4 py-3 text-sm text-primary-dark font-medium focus:border-primary outline-none transition-all placeholder:text-muted/60"
              />
            </div>
          </div>

          {/* Category Filters */}
          <div className="flex items-center gap-2 md:gap-4 mb-4 w-full overflow-hidden">
            <button
              onClick={() => scroll(categoryScrollRef, 'left')}
              className="hidden md:flex shrink-0 w-10 h-10 rounded-full bg-white border border-border shadow-[0_2px_10px_rgba(0,0,0,0.04)] items-center justify-center text-primary transition-all duration-300 hover:scale-105 hover:bg-primary hover:border-primary hover:text-white"
            >
              <ChevronLeft className="w-5 h-5" />
            </button>

            <div
              ref={categoryScrollRef}
              className="flex-1 flex items-center gap-8 overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory border-b border-border pb-[14px]"
            >
              {home.isLoading
                ? Array.from({ length: 6 }).map((_, i) => (
                    <div
                      key={`cat-sk-${i}`}
                      className="h-4 w-28 bg-neutral-200 animate-pulse rounded shrink-0"
                    />
                  ))
                : categories.map((cat) => (
                    <button
                      key={cat}
                      onClick={() => setSelectedCategory(cat)}
                      className={`whitespace-nowrap snap-start text-sm font-bold uppercase tracking-widest transition-all relative ${selectedCategory === cat
                        ? "text-primary"
                        : "text-muted hover:text-primary-dark"
                        }`}
                    >
                      {cat}
                      {selectedCategory === cat && (
                        <div className="absolute -bottom-[15px] left-0 w-full h-[2px] bg-primary z-10" />
                      )}
                    </button>
                  ))}
            </div>

            <button
              onClick={() => scroll(categoryScrollRef, 'right')}
              className="hidden md:flex shrink-0 w-10 h-10 rounded-full bg-white border border-border shadow-[0_2px_10px_rgba(0,0,0,0.04)] items-center justify-center text-primary transition-all duration-300 hover:scale-105 hover:bg-primary hover:border-primary hover:text-white"
            >
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>

          {/* Carousel Container */}
          <div className="flex items-center gap-2 md:gap-4 w-full overflow-hidden">
            {/* Navigation Arrows */}
            <button
              onClick={() => scroll(scrollRef, 'left')}
              className="hidden md:flex shrink-0 w-10 h-10 rounded-full bg-white border border-border shadow-[0_2px_10px_rgba(0,0,0,0.04)] items-center justify-center text-primary transition-all duration-300 hover:scale-105 hover:bg-primary hover:border-primary hover:text-white"
            >
              <ChevronLeft className="w-5 h-5" />
            </button>

            {/* Scrollable Area */}
            <div
              ref={scrollRef}
              className="flex-1 flex gap-6 overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory pt-2 pb-6"
            >
              {(home.isLoading || subsLoading) && Array.from({ length: 4 }).map((_, i) => (
                <div
                  key={`svc-sk-${i}`}
                  className="min-w-[280px] md:min-w-[320px] lg:min-w-[calc(25%-18px)] snap-start h-[360px] bg-neutral-200 animate-pulse border border-border"
                />
              ))}
              {!home.isLoading && !subsLoading && subsError && (
                <div className="min-w-[280px] md:min-w-[320px] lg:min-w-[calc(25%-18px)] snap-start h-[360px] border border-border flex items-center justify-center text-xs font-bold uppercase tracking-widest text-accent-dark p-6 text-center">
                  Could not load services: {subsError}
                </div>
              )}
              {!home.isLoading && !subsLoading && !subsError && filteredServices.length === 0 && (
                <div className="min-w-[280px] md:min-w-[320px] lg:min-w-[calc(25%-18px)] snap-start h-[360px] border border-border flex items-center justify-center text-xs font-bold uppercase tracking-widest text-muted p-6 text-center">
                  No services match your filters.
                </div>
              )}
              <AnimatePresence mode="popLayout">
                {!home.isLoading && !subsLoading && filteredServices.map((service) => {
                  const parentCat = serviceCategories.find(c => c.slug === service._categorySlug);
                  return (
                    <motion.div
                      key={`${service._categorySlug}-${service.id}`}
                      layout
                      initial={{ opacity: 0, scale: 0.95 }}
                      animate={{ opacity: 1, scale: 1 }}
                      exit={{ opacity: 0, scale: 0.95 }}
                      transition={{ duration: 0.3 }}
                      className="min-w-[280px] md:min-w-[320px] lg:min-w-[calc(25%-18px)] snap-start"
                    >
                      <div
                        onClick={() => setCurrentPage(`service-${service._categorySlug}/${service.slug}`)}
                        className="relative h-[360px] group cursor-pointer overflow-hidden border border-border shadow-sm hover:shadow-md transition-all duration-300 bg-primary-dark"
                      >
                        {/* Image */}
                        <img
                          src="https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=800"
                          className="absolute inset-0 w-full h-full object-cover opacity-30 group-hover:opacity-40 transition-all duration-500"
                          alt={service.title}
                          referrerPolicy="no-referrer"
                        />

                        {/* Gradient Overlay */}
                        <div className="absolute inset-0 bg-gradient-to-t from-primary-dark/90 via-primary-dark/40 to-transparent" />

                        {/* Content */}
                        <div className="absolute inset-0 p-6 flex flex-col justify-end">
                          <div className="transform transition-transform duration-300 flex flex-col items-start">
                            <div className="text-[10px] font-bold text-accent uppercase tracking-widest mb-3">{parentCat?.title}</div>
                            <h3 className="text-xl font-bold text-white mb-2 leading-tight">{service.title}</h3>
                            <p className="text-xs text-white/80 font-normal leading-relaxed mb-6 opacity-0 group-hover:opacity-100 transition-opacity duration-300 line-clamp-3">
                              {(service as any).description || 'Professional automotive service ensuring perfect condition.'}
                            </p>
                            <button className="bg-primary text-white px-5 py-2.5 text-xs font-bold transition-colors">
                              Details
                            </button>
                          </div>
                        </div>
                      </div>
                    </motion.div>
                  )
                })}
              </AnimatePresence>
            </div>

            <button
              onClick={() => scroll(scrollRef, 'right')}
              className="hidden md:flex shrink-0 w-10 h-10 rounded-full bg-white border border-border shadow-[0_2px_10px_rgba(0,0,0,0.04)] items-center justify-center text-primary transition-all duration-300 hover:scale-105 hover:bg-primary hover:border-primary hover:text-white"
            >
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </section>

      {/* Offers Section */}
      <section className="py-20 bg-white border-b border-border">
        <div className="site-container">
          <div className="text-center max-w-3xl mx-auto mb-16">
            <div className="flex items-center justify-center gap-3 mb-4">
              <div className="w-8 h-0.5 bg-accent" />
              <span className="text-xs uppercase tracking-widest text-muted font-bold">Exclusive Deals</span>
              <div className="w-8 h-0.5 bg-accent" />
            </div>
            <h2 className="text-3xl md:text-5xl font-black uppercase tracking-tighter text-primary-dark mb-4">
              Current <span className="text-primary italic font-black">Offers.</span>
            </h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[
              { title: "20% Off on Full Exterior Polish", code: "ACRPOLISH20" },
              { title: "Free AC Checkup with Any Repair", code: "FREEAC" },
              { title: "Flat ₹500 Off on Ceramic Coating", code: "CERAMIC500" }
            ].map((offer, i) => (
              <div key={i} className="bg-neutral-50 p-8 border border-border flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                  <div className="inline-block px-3 py-1 bg-primary/10 text-primary uppercase text-[10px] font-black tracking-widest mb-4">Limited Time</div>
                  <h3 className="text-xl font-black text-primary-dark uppercase mb-4 leading-tight">{offer.title}</h3>
                </div>
                <div>
                  <div className="text-xs text-muted mb-2 font-medium">Use Code:</div>
                  <div className="text-lg font-mono font-bold text-primary mb-6">{offer.code}</div>
                  <button onClick={() => setCurrentPage("offers")} className="btn-ink btn-ink-primary w-full py-3.5 text-sm font-bold">
                    Claim Offer
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Short Content SEO Block */}
      <section className="py-12 bg-surface border-b border-border">
        <div className="site-container">
          <div className="max-w-4xl mx-auto text-center space-y-4">
            <h3 className="text-lg font-black uppercase text-primary-dark">Comprehensive Auto Care Solutions in Delhi NCR</h3>
            <p className="text-sm text-muted leading-relaxed">
              Auto Car Repair provides an elite tier of automobile servicing, executing high-precision denting and painting, advanced electrical diagnostics, and instantaneous cashless insurance claims. With strictly self-owned and company-operated centers strategically spanning Delhi NCR, we refuse to compromise. We install exclusively genuine OEM parts so your vehicle runs flawlessly for the long haul.
            </p>
          </div>
        </div>
      </section>

      {/* Why Choose Us Section - Trust Building Highlight */}
      <section className="py-20 relative overflow-hidden bg-white">
        <div className="absolute top-0 right-0 w-1/4 h-full bg-surface z-0" />

        <div className="site-container relative z-10">
          <div className="text-center max-w-3xl mx-auto mb-16">
            <h2 className="text-3xl md:text-4xl font-black uppercase tracking-tighter text-primary-dark mb-4">
              Why <span className="text-primary italic font-black">Choose Us?</span>
            </h2>
            <p className="text-sm md:text-base text-muted font-medium uppercase tracking-widest">
              Unmatched precision. Unshakable trust.
            </p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-12 items-start pt-4">
            {/* Left Column Features */}
            <div className="space-y-8 order-2 lg:order-1">
              {[
                {
                  title: "Master Technicians",
                  desc: "Factory-certified specialists meticulously handling every intricate repair.",
                  icon: Award
                },
                {
                  title: "100% Genuine OEM",
                  desc: "Authentic manufacturer parts only. We never sacrifice on build quality.",
                  icon: ShieldCheck
                },
                {
                  title: "Advanced Scanners",
                  desc: "Dealership-level diagnostic tools predicting and solving complex issues securely.",
                  icon: Zap
                }
              ].map((feature, i) => (
                <div
                  key={i}
                  className="bg-white p-6 border border-border shadow-sm hover:shadow-md transition-shadow"
                >
                  <div className="flex items-center gap-4 mb-3">
                    <div className="w-10 h-10 flex items-center justify-center text-primary border border-border rounded-none">
                      <feature.icon className="w-5 h-5" />
                    </div>
                    <h3 className="text-sm font-bold text-primary-dark">{feature.title}</h3>
                  </div>
                  <p className="text-xs text-muted leading-relaxed">{feature.desc}</p>
                </div>
              ))}
            </div>

            {/* Center Visual */}
            <div className="order-1 lg:order-2 flex flex-col justify-center h-full">
              <motion.div
                animate={{ y: [0, -10, 0] }}
                transition={{ duration: 5, repeat: Infinity, ease: "easeInOut" }}
                className="relative"
              >
                <div className="absolute -inset-4 bg-primary/5 blur-3xl" />
                <img
                  src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&q=80&w=1000"
                  alt="Premium Car"
                  className="relative z-10 w-full max-w-[500px] object-contain drop-shadow-xl"
                  referrerPolicy="no-referrer"
                />
                <div className="text-center mt-12 bg-surface p-6 border border-border relative z-20">
                  <div className="text-lg font-black uppercase tracking-tight text-primary-dark leading-tight">Multibrand Capability</div>
                  <div className="text-sm font-medium text-muted mt-2">Any make. Any model. One standard.</div>
                </div>
              </motion.div>
            </div>

            {/* Right Column Features */}
            <div className="space-y-8 order-3">
              {[
                {
                  title: "Zero Hidden Costs",
                  desc: "Absolute transparency. Get an explicit estimate before we unscrew a single bolt.",
                  icon: IndianRupee
                },
                {
                  title: "Cashless Insurance",
                  desc: "Seamless, hassle-free cashless claims with every major provider in India.",
                  icon: FileText
                },
                {
                  title: "Secure Pickup & Drop",
                  desc: "Premium at-door service handled by our verified logistical drivers.",
                  icon: Truck
                }
              ].map((feature, i) => (
                <div
                  key={i}
                  className="bg-white p-6 border border-border shadow-sm hover:shadow-md transition-shadow"
                >
                  <div className="flex items-center gap-4 mb-3">
                    <div className="w-10 h-10 flex items-center justify-center text-primary border border-border rounded-none">
                      <feature.icon className="w-5 h-5" />
                    </div>
                    <h3 className="text-sm font-bold text-primary-dark">{feature.title}</h3>
                  </div>
                  <p className="text-xs text-muted leading-relaxed">{feature.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Before vs After Section - Transformation Carousel */}
      <section className="py-20 bg-surface overflow-hidden border-t border-border">
        <div className="site-container">
          <div className="flex flex-col md:flex-row justify-between items-end gap-8 mb-12">
            <div className="max-w-2xl">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-8 h-0.5 bg-accent" />
                <span className="text-xs uppercase tracking-widest text-muted font-bold">Visual Proof</span>
              </div>
              <h2 className="text-3xl md:text-4xl font-black uppercase tracking-tighter text-primary-dark mb-4">
                The <span className="text-primary italic font-black">Transformation.</span>
              </h2>
              <p className="text-sm text-muted leading-relaxed">
                Witness absolute precision. Our advanced collision repair leaves your car indistinguishable from its factory fresh finish.
              </p>
            </div>
          </div>

          <div
            className="relative group"
            onMouseEnter={() => setIsTransformHovered(true)}
            onMouseLeave={() => setIsTransformHovered(false)}
          >
            {/* Navigation Arrows */}
            <button
              onClick={() => scroll(transformScrollRef, 'left')}
              className="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-20 w-10 h-10 bg-white border border-border flex items-center justify-center text-primary-dark shadow-sm opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-opacity hidden md:flex"
            >
              <ChevronLeft className="w-5 h-5" />
            </button>
            <button
              onClick={() => scroll(transformScrollRef, 'right')}
              className="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-20 w-10 h-10 bg-white border border-border flex items-center justify-center text-primary-dark shadow-sm opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-opacity hidden md:flex"
            >
              <ChevronRight className="w-5 h-5" />
            </button>

            {/* Scrollable Area */}
            <div
              ref={transformScrollRef}
              className="flex gap-8 overflow-x-auto no-scrollbar snap-x snap-mandatory pb-4"
            >
              {transformations.map((item, i) => (
                <div
                  key={i}
                  className="min-w-full md:min-w-[calc(50%-16px)] snap-start space-y-4"
                >
                  <div className="grid grid-cols-2 gap-0.5 bg-border shadow-sm border border-border">
                    <div className="relative group/img overflow-hidden">
                      <img
                        src={item.before}
                        alt="Before"
                        className="h-48 md:h-64 w-full object-cover grayscale transition-all duration-500 group-hover/img:grayscale-0"
                        referrerPolicy="no-referrer"
                      />
                      <div className="absolute top-4 left-4 bg-primary-dark text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest">Before</div>
                    </div>
                    <div className="relative group/img overflow-hidden">
                      <img
                        src={item.after}
                        alt="After"
                        className="h-48 md:h-64 w-full object-cover transition-all duration-500 group-hover/img:scale-105"
                        referrerPolicy="no-referrer"
                      />
                      <div className="absolute top-4 right-4 bg-primary text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest">After</div>
                    </div>
                  </div>
                  <h3 className="text-sm font-black uppercase tracking-tight text-primary-dark px-2">{item.title}</h3>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Reviews & Video Testimonials */}
      <section className="py-20 bg-surface overflow-hidden border-y border-border">
        <div className="site-container">
          <div className="text-center max-w-3xl mx-auto mb-16">
            <div className="flex items-center justify-center gap-3 mb-4">
              <div className="w-8 h-0.5 bg-accent" />
              <span className="text-xs uppercase tracking-widest text-muted font-bold">Customer Stories</span>
              <div className="w-8 h-0.5 bg-accent" />
            </div>
            <h2 className="text-3xl md:text-5xl font-black uppercase tracking-tighter text-primary-dark mb-4">
              Absolute <span className="text-primary italic font-black">Trust.</span>
            </h2>
            <p className="text-sm text-muted leading-relaxed">Unfiltered reviews and genuine testimonials from India's most demanding vehicle owners.</p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Testimonial Carousel */}
            <div
              className="lg:col-span-1 relative group"
              onMouseEnter={() => setIsTestimonialHovered(true)}
              onMouseLeave={() => setIsTestimonialHovered(false)}
            >
              {/* Navigation Arrows */}
              <button
                onClick={() => scroll(testimonialScrollRef, 'left')}
                className="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-20 w-8 h-8 bg-white border border-border flex items-center justify-center text-primary-dark shadow-sm opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
              <button
                onClick={() => scroll(testimonialScrollRef, 'right')}
                className="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-20 w-8 h-8 bg-white border border-border flex items-center justify-center text-primary-dark shadow-sm opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all"
              >
                <ChevronRight className="w-4 h-4" />
              </button>

              <div
                ref={testimonialScrollRef}
                className="flex overflow-x-auto no-scrollbar snap-x snap-mandatory h-full"
              >
                {TESTIMONIALS.map((t, i) => (
                  <div key={i} className="min-w-full snap-start h-full">
                    <div className="bg-white p-8 border border-border h-full flex flex-col justify-between relative shadow-sm">
                      <div>
                        <div className="flex items-center justify-between mb-4">
                          <div className="flex text-[#FBBC05]">
                            {[...Array(t.rating)].map((_, i) => <Star key={i} className="w-5 h-5 fill-current" />)}
                          </div>
                          <div className="flex items-center gap-1.5 opacity-90">
                            <svg className="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" /><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" /><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" /><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" /></svg>
                            <span className="text-[10px] font-black tracking-widest uppercase text-muted">Google Review</span>
                          </div>
                        </div>
                        {/* Pull-quote style */}
                        <div className="border-l-[2px] border-accent pl-6 py-2 mb-8">
                          <p className="text-sm font-medium text-primary-dark leading-relaxed">
                            "{t.text}"
                          </p>
                        </div>
                      </div>
                      <div className="flex items-center gap-4">
                        <div className="w-10 h-10 bg-surface flex items-center justify-center font-black uppercase text-primary-dark text-sm border border-border">{t.initials}</div>
                        <div>
                          <div className="font-black uppercase tracking-tight text-sm text-primary-dark">{t.name}</div>
                          <div className="flex text-[#FBBC05] md:hidden">
                            <Star className="w-3 h-3 fill-current" />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {/* Navigation Dots */}
              <div className="flex justify-center gap-2 mt-6">
                {TESTIMONIALS.map((_, i) => (
                  <div key={i} className="w-1.5 h-1.5 bg-border hover:bg-muted cursor-pointer transition-colors" />
                ))}
              </div>
            </div>

            {/* Video Testimonial */}
            <div className="lg:col-span-2 relative h-[400px] group cursor-pointer overflow-hidden border border-border shadow-sm">
              <img
                src="https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?auto=format&fit=crop&q=80&w=1200"
                alt="Video Testimonial"
                className="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity duration-700"
                referrerPolicy="no-referrer"
              />
              <div className="absolute inset-0 bg-primary-dark/40 group-hover:bg-primary-dark/30 transition-all" />

              <div className="absolute inset-0 flex items-center justify-center">
                <motion.div
                  initial={false}
                  whileHover={{ scale: 1.05 }}
                  className="relative"
                >
                  <div className="relative w-16 h-16 bg-primary flex items-center justify-center shadow-lg">
                    <Play className="w-6 h-6 text-white fill-current ml-1" />
                  </div>
                </motion.div>
              </div>

              <div className="absolute bottom-0 left-0 p-8">
                <div className="bg-primary text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest mb-3 inline-block">Featured Story</div>
                <h3 className="text-2xl font-black uppercase tracking-tighter text-white mb-1">Customer Experience</h3>
                <p className="text-white/80 font-bold tracking-widest text-[10px] uppercase">Watch Video Testimonial</p>
              </div>
            </div>
          </div>

          {/* Demo-readiness — link to dedicated /testimonials page. */}
          <div className="mt-10 flex justify-center">
            <button
              onClick={() => setCurrentPage("testimonials")}
              className="text-xs font-bold uppercase tracking-widest text-primary hover:underline inline-flex items-center gap-1.5"
            >
              Read more customer stories
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </section>

      {/* Our Service Centers Section - Rhombus Masked Accordion */}
      <section className=" py-10 min-h-[650px] bg-black overflow-hidden relative flex flex-col justify-center">
        {/* Subtle Texture/Gradient */}
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-neutral-900 via-neutral-950 to-black opacity-50" />

        <div className="site-container relative z-10">
          <div className="text-center max-w-3xl mx-auto mb-8">
            <div className="text-primary font-bold uppercase tracking-[0.3em] text-[9px] mb-3">Our Network</div>
            <h2 className="text-3xl md:text-5xl font-black uppercase tracking-tighter text-white mb-4">
              Our <span className="text-primary">Service Centers.</span>
            </h2>
            <p className="text-sm md:text-base text-neutral-400 font-medium uppercase tracking-widest">
              India's fastest-growing self-owned multi-brand network
            </p>
          </div>

          {/* Rhombus Accordion - Pure Hover Driven */}
          <div
            className="hidden lg:flex h-[70vh] w-full gap-0 items-stretch px-12 skew-x-[-12deg] overflow-hidden group/accordion pb-10"
            onMouseLeave={() => setExpandedLocationIndex(null)}
          >
            {LOCATIONS.map((loc, index) => {
              const isExpanded = expandedLocationIndex === index;

              return (
                <motion.div
                  key={loc.id}
                  onMouseEnter={() => setExpandedLocationIndex(index)}
                  animate={{
                    flex: isExpanded ? 8 : 1,
                  }}
                  transition={{
                    duration: 0.5,
                    ease: [0.22, 1, 0.36, 1]
                  }}
                  className="relative overflow-hidden cursor-pointer border-r border-white/10 bg-neutral-900 first:border-l"
                >
                  {/* Un-skewed Black Strip */}


                  {/* Location Name - Always Visible, Rotating in place */}
                  <motion.div
                    animate={{
                      rotate: isExpanded ? 0 : -90,
                    }}
                    transition={{
                      duration: 0.6,
                      ease: [0.22, 1, 0.36, 1]
                    }}
                    style={{ transformOrigin: "left bottom" }}
                    className="absolute bottom-[20px] left-[42px] z-50 pointer-events-none w-fit skew-x-[12deg]"
                  >
                    <h3 className="text-4xl font-black uppercase text-white whitespace-nowrap tracking-tighter m-0 leading-none">
                      {loc.name}
                    </h3>
                  </motion.div>

                  {/* Background Image - Fixed to cover container correctly */}
                  <motion.img
                    initial={false}
                    animate={{
                      scale: isExpanded ? 1.05 : 1.1,
                      filter: isExpanded ? "grayscale(0%)" : "grayscale(100%)",
                      opacity: isExpanded ? 1 : 0.4,
                    }}
                    transition={{
                      duration: 0.6,
                      ease: [0.22, 1, 0.36, 1]
                    }}
                    src={loc.image}
                    alt={loc.name}
                    className="absolute inset-0 w-full h-full object-cover skew-x-[12deg] scale-110"
                    referrerPolicy="no-referrer"
                  />

                  {/* Dynamic Overlay Fade */}
                  <motion.div
                    animate={{
                      opacity: isExpanded ? 0.8 : 0.7
                    }}
                    className="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent z-20"
                  />

                  {/* Expanded Content Overlay (Address & Buttons) */}
                  <AnimatePresence>
                    {isExpanded && (
                      <motion.div
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: 10 }}
                        transition={{ duration: 0.4 }}
                        className="absolute inset-0 flex flex-col justify-end p-16 pl-20 pb-[80px] z-40 pointer-events-none skew-x-[12deg]"
                      >
                        <div className="flex flex-col items-start">
                          <p className="text-[11px] text-neutral-400 font-bold uppercase tracking-[0.3em] mb-6 max-w-md leading-relaxed">
                            {loc.address}
                          </p>
                          <div className="flex gap-4 pointer-events-auto">
                            <a
                              href={loc.url}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="bg-primary text-white px-10 py-4 text-[11px] font-black uppercase tracking-widest hover:bg-white hover:text-primary transition-all shadow-2xl shadow-primary/40"
                            >
                              View Center
                            </a>
                            <button className="bg-white/10 backdrop-blur-md text-white border border-white/20 px-10 py-4 text-[11px] font-black uppercase tracking-widest hover:bg-white hover:text-neutral-900 transition-all">
                              Directions
                            </button>
                          </div>
                        </div>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </motion.div>
              );
            })}
          </div>

          {/* Mobile Slider Fallback */}
          <div className="lg:hidden">
            <div
              className="relative group"
              onMouseEnter={() => setIsLocationHovered(true)}
              onMouseLeave={() => setIsLocationHovered(false)}
            >
              <div className="flex gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory pb-4">
                {LOCATIONS.map((loc, index) => (
                  <div
                    key={loc.id}
                    className="min-w-full snap-start relative h-[400px] overflow-hidden border border-white/10"
                    onClick={() => setActiveLocationIndex(index)}
                  >
                    <img
                      src={loc.image}
                      alt={loc.name}
                      className="absolute inset-0 w-full h-full object-cover"
                      referrerPolicy="no-referrer"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent" />
                    <div className="absolute inset-0 flex flex-col justify-end p-6">
                      <h3 className="text-2xl font-black uppercase text-white mb-1">{loc.name}</h3>
                      <p className="text-[10px] text-neutral-400 font-bold uppercase tracking-widest mb-4">{loc.address}</p>
                      <div className="flex gap-2">
                        <a
                          href={loc.url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="bg-primary text-white px-4 py-2 text-[9px] font-black uppercase tracking-widest"
                        >
                          View Center
                        </a>
                        <button className="bg-white/10 backdrop-blur-md text-white border border-white/20 px-4 py-2 text-[9px] font-black uppercase tracking-widest">
                          Directions
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {/* Mobile Navigation Dots */}
              <div className="flex justify-center gap-2 mt-6">
                {LOCATIONS.map((_, i) => (
                  <div
                    key={i}
                    className={`w-1.5 h-1.5 rounded-full transition-all duration-300 ${activeLocationIndex === i ? 'bg-primary w-4' : 'bg-neutral-800'}`}
                  />
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* B2B / Fleet Section */}
      <section className="py-20 bg-white border-y border-border">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-16 items-center">
            <div className="max-w-2xl">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-8 h-0.5 bg-accent" />
                <span className="text-xs uppercase tracking-widest text-muted font-bold">Corporate Solutions</span>
              </div>
              <h2 className="text-4xl md:text-5xl font-black uppercase tracking-tighter mb-6 leading-tight text-primary-dark">Fleet <br /><span className="text-primary italic font-black">Maintenance.</span></h2>
              <p className="text-muted text-base mb-8 leading-relaxed">
                Customized solutions for corporate fleets, car rentals, and government organizations.
                Priority service and bulk pricing models with dedicated account management.
              </p>
              <button
                onClick={() => setCurrentPage("corporate")}
                className="btn-ink btn-ink-primary px-8 py-3.5 font-bold text-sm"
              >
                Explore Corporate Plans <ArrowRight className="w-4 h-4 btn-arrow" />
              </button>
            </div>
            <div className="grid grid-cols-2 gap-4">
              {[
                { label: "50+", sub: "Corporate Partners" },
                { label: "24/7", sub: "Priority Support" },
                { label: "Cashless", sub: "Fleet Repairs" },
                { label: "Custom", sub: "SLA Agreements" }
              ].map((item, i) => (
                <div key={i} className="bg-surface p-8 border border-border shadow-sm">
                  <div className="text-3xl font-bold text-primary mb-2 uppercase">{item.label}</div>
                  <div className="text-xs font-bold text-primary-dark uppercase tracking-widest">{item.sub}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* FAQ Section — premium card design (HomeFAQ component). */}
      <HomeFAQ setCurrentPage={setCurrentPage} />

      {/* Blog Highlights */}
      <section className="py-24 bg-white border-b border-border">
        <div className="site-container">
          <div className="text-center mb-16">
            <div className="flex items-center justify-center gap-3 mb-4">
              <div className="w-8 h-0.5 bg-accent" />
              <span className="text-xs uppercase tracking-widest text-muted font-bold">Latest Insights</span>
              <div className="w-8 h-0.5 bg-accent" />
            </div>
            <h2 className="text-4xl md:text-5xl font-black uppercase tracking-tighter mb-6 leading-tight text-primary-dark">
              Car Care <span className="text-primary italic font-black">Blog.</span>
            </h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {[
              { title: "Definitive Guide to Ceramic Coating", desc: "Understand everything about 10H ceramic coatings, process, and durability.", date: "Oct 24, 2026", img: "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=600" },
              { title: "When to Replace Your Brake Pads", desc: "Key warning signs that indicate your vehicle's braking system needs immediate attention.", date: "Oct 18, 2026", img: "https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80&w=600" },
              { title: "Monsoon Car Maintenance Checklist", desc: "Protect your vehicle from rust, electrical issues, and waterlogging this rainy season.", date: "Sep 30, 2026", img: "https://images.unsplash.com/photo-1530046339160-ce3e5b0c7a2f?auto=format&fit=crop&q=80&w=600" }
            ].map((blog, i) => (
              <div key={i} className="group cursor-pointer">
                <div className="relative h-64 mb-6 overflow-hidden border border-border">
                  <img src={blog.img} className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Blog" referrerPolicy="no-referrer" />
                </div>
                <div className="text-[10px] text-muted font-bold uppercase tracking-widest mb-3">{blog.date}</div>
                <h3 className="text-xl font-black uppercase text-primary-dark mb-3 group-hover:text-primary transition-colors">{blog.title}</h3>
                <p className="text-sm text-muted leading-relaxed mb-4">{blog.desc}</p>
                <div className="text-[11px] font-bold text-primary uppercase tracking-widest flex items-center gap-2">
                  Read Article <ArrowRight className="w-3 h-3 group-hover:translate-x-1 transition-transform" />
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Final CTA */}
      <section className="py-24 relative bg-primary-dark overflow-hidden">
        <div className="absolute inset-0 opacity-10">
          <div className="absolute inset-0 bg-gradient-to-br from-primary/20 to-transparent" />
          <img
            src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80&w=2000"
            alt="Background"
            className="w-full h-full object-cover"
            referrerPolicy="no-referrer"
          />
        </div>

        <div className="site-container relative z-10 text-center">
          <h2 className="text-4xl md:text-6xl mb-10 leading-tight text-white font-black uppercase tracking-tighter">REPAIR YOU <br /><span className="text-primary italic font-black">CAN TRUST.</span></h2>
          <div className="flex flex-wrap justify-center gap-4">
            <button
              onClick={() => openEstimate()}
              className="btn-ink btn-ink-primary px-10 py-4 font-bold text-sm shadow-md"
            >
              Get Estimate <ArrowRight className="w-4 h-4 btn-arrow" />
            </button>
          </div>
        </div>
      </section>
    </div>
  );
}

