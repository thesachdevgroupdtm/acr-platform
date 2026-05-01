import { 
  Shield, 
  Zap, 
  Paintbrush, 
  Wrench, 
  FileText, 
  Sparkles, 
  Battery, 
  LifeBuoy, 
  Thermometer, 
  CircleDot, 
  Settings, 
  Search, 
  AlertTriangle, 
  GlassWater,
  ShieldCheck
} from "lucide-react";

export const LOCATIONS = [
  {
    id: "moti-nagar",
    name: "Moti Nagar",
    city: "Delhi",
    address: "63, Rama Rd, Block B, Najafgarh Road Industrial Area, New Delhi, Delhi 110015",
    phone: "9870400861",
    rating: "4.9",
    reviews: "1,250",
    features: ["Collision Repair", "Mechanical Service", "Cashless Insurance"],
    image: "https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&q=80&w=1200",
    url: "https://maps.app.goo.gl/moti-nagar"
  },
  {
    id: "gurugram",
    name: "Gurugram",
    city: "Gurugram",
    address: "Plot No. 123, Sector 14, Gurugram, Haryana 122001",
    phone: "9870400861",
    rating: "4.8",
    reviews: "980",
    features: ["Luxury Car Service", "Detailing", "Paint Protection"],
    image: "https://images.unsplash.com/photo-1590674899484-d5640e854abe?auto=format&fit=crop&q=80&w=1200",
    url: "https://maps.app.goo.gl/gurugram"
  },
  {
    id: "noida",
    name: "Noida",
    city: "Noida",
    address: "C-45, Sector 63, Noida, Uttar Pradesh 201301",
    phone: "9870400861",
    rating: "4.9",
    reviews: "1,100",
    features: ["Body Shop", "AC Repair", "Wheel Alignment"],
    image: "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=1200",
    url: "https://maps.app.goo.gl/noida"
  },
  {
    id: "okhla",
    name: "Okhla",
    city: "Delhi",
    address: "Phase III, Okhla Industrial Estate, New Delhi, Delhi 110020",
    phone: "9870400861",
    rating: "4.7",
    reviews: "850",
    features: ["Express Service", "Genuine Parts", "Fleet Maintenance"],
    image: "https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?auto=format&fit=crop&q=80&w=1200",
    url: "https://maps.app.goo.gl/okhla"
  }
];

export const BUSINESS_INFO = {
  name: "Auto Car Repair",
  tagline: "The Fastest Growing Self-Owned Multi-Brand Collision Repair & Service Center in India",
  about: "Auto Car Repair is India's fastest-growing, fully self-owned network of premium multi-brand workshops. We specialize in advanced collision repair, precision paintwork, and comprehensive mechanical servicing. By operating our own centers and refusing to outsource, we guarantee unmatched speed, sheer transparency, and true dealership-level quality for every vehicle.",
  phone: "9870400861",
  email: "info@autocarrepair.in",
  whatsapp: "https://wa.me/9870400861",
  social: {
    facebook: "https://www.facebook.com/ACRautocarrepair",
    twitter: "https://twitter.com/Auto_carrepair",
    instagram: "https://www.instagram.com/autocarrepair_/",
    linkedin: "https://www.linkedin.com/company/autocarrepairacr/",
    youtube: "https://www.youtube.com/@Auto_carrepair/featured",
    whatsapp: "https://wa.me/9870400861"
  },
  trustPoints: [
    "100% Self-Owned Network",
    "Certified Multi-Brand Experts",
    "Advanced Collision Repair",
    "Dealership Quality, Faster & Transparent"
  ]
};

export const CAR_DATA: Record<string, string[]> = {
  "Maruti Suzuki": ["Swift", "Baleno", "Dzire", "Brezza", "Ertiga", "WagonR", "Alto", "Grand Vitara", "Fronx"],
  "Hyundai": ["Creta", "Venue", "i20", "Verna", "Grand i10 Nios", "Alcazar", "Tucson", "Exter"],
  "Honda": ["City", "Amaze", "Elevate", "Civic", "CR-V"],
  "Toyota": ["Fortuner", "Innova Hycross", "Innova Crysta", "Glanza", "Urban Cruiser Hyryder", "Camry", "Hilux"],
  "Tata": ["Nexon", "Punch", "Harrier", "Safari", "Tiago", "Tigor", "Altroz", "Tiago EV"],
  "Mahindra": ["XUV700", "Scorpio-N", "Thar", "XUV300", "Bolero", "Scorpio Classic", "XUV400"],
  "Kia": ["Seltos", "Sonet", "Carens", "Carnival", "EV6"],
  "BMW": ["3 Series", "5 Series", "X1", "X3", "X5", "7 Series", "M3", "M5"],
  "Mercedes-Benz": ["C-Class", "E-Class", "GLC", "GLE", "S-Class", "GLA", "GLS"],
  "Audi": ["A4", "A6", "Q3", "Q5", "Q7", "A8", "e-tron"],
  "Skoda": ["Slavia", "Kushaq", "Octavia", "Superb", "Kodiaq"],
  "Volkswagen": ["Virtus", "Taigun", "Tiguan", "Polo", "Vento"]
};

export const TESTIMONIALS = [
  {
    name: "Atul Tiwari",
    text: "Best car service experience in Delhi. Very professional and transparent about the costs.",
    rating: 5,
    initials: "AT"
  },
  {
    name: "Harsh Sharma",
    text: "Got my car painted here. The finish is factory-like. Highly recommended for bodywork.",
    rating: 5,
    initials: "HS"
  },
  {
    name: "Vikash Pandey",
    text: "Quick insurance claim process. They handled everything with the surveyor smoothly.",
    rating: 5,
    initials: "VP"
  },
  {
    name: "Rahul Mehra",
    text: "Excellent service for my BMW. They use genuine parts and the staff is very knowledgeable.",
    rating: 5,
    initials: "RM"
  },
  {
    name: "Sandeep Gupta",
    text: "The ceramic coating work is outstanding. My car looks better than new. Great attention to detail.",
    rating: 5,
    initials: "SG"
  },
  {
    name: "Priya Singh",
    text: "Very reliable and honest workshop. I've been coming here for 3 years and never had an issue.",
    rating: 5,
    initials: "PS"
  }
];

// ─────────────────── OFFERS / COUPONS ───────────────────
// Coupons that the Cart/Checkout pages auto-fetch and apply.
// `applicableCategorySlugs: null` means works on all services.
// `type: "percent"` → value is a % (e.g. 10 = 10% off, capped at maxDiscount).
// `type: "flat"`    → value is a flat ₹ amount off.
// `firstTimeOnly: true` → restricted to users with zero past bookings.
// `priority: higher number applies first when multiple match.

export interface OfferCoupon {
  id: string;
  code: string; // uppercase, no spaces
  title: string; // shown in UI
  description: string;
  type: "percent" | "flat";
  value: number;
  maxDiscount?: number; // cap for percent coupons
  minOrder?: number; // minimum cart subtotal in ₹
  applicableCategorySlugs: string[] | null; // null = all
  firstTimeOnly?: boolean;
  validUntil?: string; // YYYY-MM-DD; omit for evergreen
  priority: number;
  badge?: "best" | "new" | "popular" | "limited";

  // ── Optional marketing/visual fields used by the /offers landing page.
  //    All optional — Cart/Checkout coupon math ignores them.
  urgencyText?: string;       // e.g. "ENDS TODAY" — small chip at top of card
  rating?: number;            // e.g. 4.8 — yellow-star chip
  customers?: number;         // e.g. 12500 — formatted as "12,500+" in UI
  image?: string;             // hero photo URL; falls back to gradient when absent
}

export const OFFERS: OfferCoupon[] = [
  {
    id: "off-first10",
    code: "FIRST10",
    title: "First booking — 10% off",
    description: "Flat 10% off on your first booking, max ₹500 discount.",
    type: "percent",
    value: 10,
    maxDiscount: 500,
    applicableCategorySlugs: null,
    firstTimeOnly: true,
    priority: 100,
    badge: "new",
  },
  {
    id: "off-accool20",
    code: "ACCOOL20",
    title: "Beat the heat",
    description: "Flat ₹500 off on AC service & gas top-up. Min order ₹1,500.",
    type: "flat",
    value: 500,
    minOrder: 1500,
    applicableCategorySlugs: ["car-ac-service-repair"],
    priority: 80,
    badge: "popular",
    // Visual fields restored from the pre-7e7eefc local "AC DEEP CLEANING" entry.
    urgencyText: "HIGH DEMAND",
    rating: 4.9,
    customers: 8500,
    image:
      "https://images.unsplash.com/photo-1563720223185-11003d516935?auto=format&fit=crop&q=80&w=800",
  },
  {
    id: "off-saver15",
    code: "SAVER15",
    title: "Cart saver",
    description: "15% off when your cart crosses ₹3,000. Max ₹750 off.",
    type: "percent",
    value: 15,
    maxDiscount: 750,
    minOrder: 3000,
    applicableCategorySlugs: null,
    priority: 90,
    badge: "best",
  },
  {
    id: "off-detail250",
    code: "SHINE250",
    title: "Detailing combo",
    description: "₹250 off on car wash, polishing & detailing services.",
    type: "flat",
    value: 250,
    applicableCategorySlugs: ["car-care-detailing"],
    priority: 70,
    // Visual fields restored from the pre-7e7eefc local "PREMIUM CERAMIC COATING"
    // entry — closest detailing-category match.
    urgencyText: "ONLY 3 SLOTS LEFT",
    rating: 4.9,
    customers: 12000,
    image:
      "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=800",
  },
  {
    id: "off-battery300",
    code: "POWER300",
    title: "Battery booster",
    description: "₹300 off on battery replacement. Min order ₹3,000.",
    type: "flat",
    value: 300,
    minOrder: 3000,
    applicableCategorySlugs: ["car-battery"],
    priority: 75,
    badge: "limited",
  },
];

/**
 * Compute the discount in ₹ for a coupon given a cart context.
 * Returns 0 if the coupon doesn't apply.
 */
export function computeCouponDiscount(
  coupon: OfferCoupon,
  ctx: {
    subtotal: number;
    cartCategorySlugs: string[];
    isFirstTime: boolean;
  }
): number {
  // Validity window
  if (coupon.validUntil) {
    const exp = new Date(coupon.validUntil + "T23:59:59");
    if (Date.now() > exp.getTime()) return 0;
  }
  // Min order
  if (coupon.minOrder && ctx.subtotal < coupon.minOrder) return 0;
  // First time only
  if (coupon.firstTimeOnly && !ctx.isFirstTime) return 0;
  // Category filter
  if (coupon.applicableCategorySlugs) {
    const overlap = coupon.applicableCategorySlugs.some((s) =>
      ctx.cartCategorySlugs.includes(s)
    );
    if (!overlap) return 0;
  }
  // Compute discount
  if (coupon.type === "flat") {
    return Math.min(coupon.value, ctx.subtotal);
  }
  // percent
  let off = Math.round((ctx.subtotal * coupon.value) / 100);
  if (coupon.maxDiscount) off = Math.min(off, coupon.maxDiscount);
  return Math.min(off, ctx.subtotal);
}

/**
 * Pick the best (highest discount) coupon for a cart context.
 * Returns null when no coupon applies.
 */
export function pickBestOffer(
  coupons: OfferCoupon[],
  ctx: { subtotal: number; cartCategorySlugs: string[]; isFirstTime: boolean }
): { coupon: OfferCoupon; discount: number } | null {
  let best: { coupon: OfferCoupon; discount: number } | null = null;
  for (const c of coupons) {
    const d = computeCouponDiscount(c, ctx);
    if (d > 0 && (!best || d > best.discount)) {
      best = { coupon: c, discount: d };
    }
  }
  return best;
}


