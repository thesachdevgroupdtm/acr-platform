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

export const DB_SERVICE_CATEGORIES = [
  { id: "1", slug: "car-battery", title: "Car Battery", description: "Auto Car Repair provides high-quality car battery replacement and maintenance services." },
  { id: "2", slug: "car-emergency-services", title: "Car Emergency Services", description: "Need urgent car help? We offer 24/7 emergency car services." },
  { id: "3", slug: "car-insurance-claim", title: "Car Insurance Claim", description: "Quick & Easy Car Insurance Claims at Auto Car Repair." },
  { id: "5", slug: "car-repairs-inspection", title: "Car Repairs & Inspection", description: "Searching for a car repair shop near me? Our garage offers top-notch car repair services." },
  { id: "6", slug: "car-suspension-work", title: "Car Suspension Work", description: "Expert Car Suspension Repair & Replacement Services." },
  { id: "7", slug: "car-clutch-work", title: "Car Clutch Work", description: "Looking for expert clutch repair and replacement?" },
  { id: "8", slug: "car-lights-and-glass-work", title: "Car Lights and Glass Work", description: "Get professional car lights and glass repair or replacement services." },
  { id: "9", slug: "car-care-detailing", title: "Car Care & Detailing", description: "Transform your car with Auto Car Repair’s expert detailing services in Delhi NCR." },
  { id: "10", slug: "car-denting-painting", title: "Car Denting & Painting", description: "Looking for quality car denting & painting? Get expert dent repair & painting." },
  { id: "11", slug: "car-brake-wheel-maintenance", title: "Car Brake & Wheel Maintenance", description: "Get expert car brake and wheel maintenance at Auto Car Repair." },
  { id: "12", slug: "car-ac-service-repair", title: "Car AC Service & Repair", description: "Stay cool with expert car AC repair near you." },
  { id: "13", slug: "regular-car-service", title: "Regular Car Service", description: "Get expert regular car service at Auto Car Repair in Delhi." }
];

export const DB_SUB_SERVICES = [
  // Car Battery (1)
  { id: "1", sc_id: "1", slug: "battery-charging", title: "Battery Charging", price: "1500.00", time_takes: "24", time_unit: "Hour", warrenty_info: "Car Does Not Starts", recommended_info: "Electrical System Does Not Work" },
  { id: "2", sc_id: "1", slug: "battery-replacement", title: "Battery Replacement", price: "4500.00", time_takes: "4", time_unit: "Hour", warrenty_info: "Car Does Not Starts", recommended_info: "Electrical System Does Not Work" },
  
  // Car Emergency (2)
  { id: "3", sc_id: "2", slug: "flat-bed-towing", title: "Flat Bed Towing", price: "800.00", time_takes: "3", time_unit: "Hour", warrenty_info: "Door-Step Service Available", recommended_info: "Upto 10 Km" },
  { id: "4", sc_id: "2", slug: "wheel-lift-towing-10-kms", title: "Wheel lift towing ( 10 Kms )", price: null, time_takes: "3", time_unit: "Hour", warrenty_info: "Doorstep Service Available", recommended_info: "For Upto 10 Kms" },
  { id: "5", sc_id: "2", slug: "battery-jump-start", title: "Battery jump start", price: "500.00", time_takes: "4", time_unit: "Hour", warrenty_info: "Doorstep Service Available", recommended_info: "After Jump-start keep vehicle ON at least 3 hours" },

  // Car AC (12)
  { id: "6", sc_id: "12", slug: "full-ac-service", title: "Full AC service", price: null, time_takes: "8", time_unit: "Hour", warrenty_info: "Warranty 1000 kms or 1 month", recommended_info: "After every 10,000 kms or 1 year" },
  { id: "8", sc_id: "12", slug: "periodic-ac-service", title: "Periodic AC Service", price: null, time_takes: "4", time_unit: "Hour", warrenty_info: "Warranty 500 kms or 1 month", recommended_info: "After every 5,000 kms or 3 Months" },

  // Regular Car Service (13)
  { id: "9", sc_id: "13", slug: "comprehensive-service", title: "Comprehensive Service", price: null, time_takes: "8", time_unit: "Hour", warrenty_info: "Warranty 1000 kms or 1 month", recommended_info: "After every 20,000 kms or 12 Months" },
  { id: "11", sc_id: "13", slug: "standard-service", title: "Standard Service", price: null, time_takes: "6", time_unit: "Hour", warrenty_info: "Warranty 1000 kms or 1 month", recommended_info: "After every 10,000 kms or 3 Months" },
  { id: "12", sc_id: "13", slug: "primary-service", title: "Primary Service", price: null, time_takes: "3", time_unit: "Hour", warrenty_info: "Warranty 1000 kms or 1 month", recommended_info: "After every 5,000 kms or 3 Months" },

  // Car Brake & Wheel (11)
  { id: "13", sc_id: "11", slug: "front-brake-disc-replacement", title: "Front Brake Disc Replacement" },
  { id: "14", sc_id: "11", slug: "front-brake-pad-replacement", title: "Front Brake Pad Replacement" },
  { id: "15", sc_id: "11", slug: "rear-brake-shoes-replacement", title: "Rear Brake Shoes Replacement" },
  { id: "16", sc_id: "11", slug: "disc-turning", title: "Disc Turning" },
  { id: "17", sc_id: "11", slug: "brake-drums-turning", title: "Brake Drums Turning" },
  { id: "18", sc_id: "11", slug: "tyre-rotation", title: "Tyre Rotation" },
  { id: "19", sc_id: "11", slug: "wheel-alignment", title: "Wheel Alignment" },
  { id: "20", sc_id: "11", slug: "wheel-balancing", title: "Wheel Balancing" },
  { id: "21", sc_id: "11", slug: "complete-wheel-care", title: "Complete Wheel Care" },

  // Car Denting & Painting (10)
  { id: "22", sc_id: "10", slug: "front-bumper-paint", title: "Front Bumper Paint", time_takes: "2", time_unit: "Day" },
  { id: "23", sc_id: "10", slug: "rear-bumper-paint", title: "Rear Bumper Paint", time_takes: "2", time_unit: "Day" },
  { id: "24", sc_id: "10", slug: "bonnet-paint", title: "Bonnet Paint", time_takes: "2", time_unit: "Day" },
  { id: "37", sc_id: "10", slug: "full-body-paint", title: "Full Body Paint", time_takes: "7", time_unit: "Day" },

  // Car Care & Detailing (9)
  { id: "38", sc_id: "9", slug: "car-wash", title: "Car Wash", time_takes: "3", time_unit: "Hour" },
  { id: "39", sc_id: "9", slug: "interior-dry-cleaning", title: "Interior Dry Cleaning", time_takes: "4", time_unit: "Hour" },
  { id: "40", sc_id: "9", slug: "exterior-rubbing-polishing", title: "Exterior Rubbing & Polishing", time_takes: "4", time_unit: "Hour" },
  { id: "41", sc_id: "9", slug: "complete-car-detailing", title: "Complete Car Detailing", time_takes: "5", time_unit: "Hour" },
  { id: "44", sc_id: "9", slug: "teflon-coating", title: "Teflon Coating", time_takes: "24", time_unit: "Hour" },
  { id: "45", sc_id: "9", slug: "ceramic-coating", title: "Ceramic Coating", time_takes: "6", time_unit: "Hour" },

  // Car Repairs & Inspection (5)
  { id: "49", sc_id: "5", slug: "alternator-new", title: "Alternator New" },
  { id: "53", sc_id: "5", slug: "cooling-coil-replacement", title: "Cooling Coil Replacement" },
  { id: "69", sc_id: "5", slug: "car-inspection", title: "Car Inspection" },

  // Car Lights & Glass (8)
  { id: "76", sc_id: "8", slug: "front-headlight-replacement", title: "Front Headlight Replacement" },
  { id: "79", sc_id: "8", slug: "front-windshield-replacement", title: "Front Windshield Replacement" },
  
  // Car Clutch Work (7)
  { id: "83", sc_id: "7", slug: "clutch-assembly", title: "Clutch Assembly" },
  { id: "86", sc_id: "7", slug: "clutch-overhaul", title: "Clutch Overhaul" },

  // Car Suspension Work (6)
  { id: "88", sc_id: "6", slug: "front-shock-absorber-replacement", title: "Front Shock Absorber Replacement" },
  { id: "96", sc_id: "6", slug: "suspension-overhaul", title: "Suspension Overhaul" },

  // Car Insurance Claim (3)
  { id: "97", sc_id: "3", slug: "windshield-replacement-claim", title: "Windshield Replacement Claim" },
  { id: "98", sc_id: "3", slug: "accidental-claim", title: "Accidental Claim" }
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

/* ===================================================================
 * API-backed hooks (additive — existing constants above remain as
 * fallbacks for non-API contexts and for fast first-paint).
 *
 * Each hook returns { data, loading, error, reload }.
 * =================================================================== */

import { useEffect, useState, useCallback } from "react";
import { apiGet } from "../lib/api";

interface ResourceState<T> {
  data: T | null;
  loading: boolean;
  error: string | null;
  reload: () => void;
}

function useResource<T>(path: string, query?: Record<string, string | number | undefined | null>, deps: unknown[] = []): ResourceState<T> {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tick, setTick] = useState(0);

  const reload = useCallback(() => setTick((t) => t + 1), []);

  useEffect(() => {
    let cancelled = false;
    const controller = new AbortController();
    setLoading(true);
    setError(null);
    apiGet<T>(path, query, controller.signal)
      .then((res) => { if (!cancelled) setData(res); })
      .catch((e) => { if (!cancelled) setError(e?.message || "Network error"); })
      .finally(() => { if (!cancelled) setLoading(false); });
    return () => { cancelled = true; controller.abort(); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [path, JSON.stringify(query || {}), tick, ...deps]);

  return { data, loading, error, reload };
}

/* ── Domain shapes returned by the API ── */
export interface ApiServiceCategory {
  id: number;
  slug: string;
  title: string;
  description?: string;
  image?: string;
  image_1?: string;
  icon_image?: string;
}
export interface ApiServiceCenter {
  id: number;
  name?: string;
  address?: string;
  image?: string;
  phone_number?: string;
}
export interface ApiCarBrand {
  id: number;
  slug: string;
  title: string;
  image?: string;
}
export interface ApiCarModel {
  id: number;
  slug: string;
  title: string;
  image?: string;
}
export interface ApiFuelType {
  id: number;
  slug: string;
  title: string;
  image?: string;
}
export interface ApiOffer {
  id: number;
  title1?: string;
  title2?: string;
  image?: string;
  image_url?: string;
  btn_link?: string;
  btn_title?: string;
}
export interface ApiFaq {
  id: number;
  service_category_id?: number | null;
  name: string;
  description: string;
}

/* ── Hooks ── */

export const useApiHome        = () => useResource<{
  service_categories: ApiServiceCategory[];
  service_centers: ApiServiceCenter[];
  car_brands: ApiCarBrand[];
  offer_slider: ApiOffer[];
  faqs: ApiFaq[];
  seo: import("../lib/SeoHead").SeoPayload;
}>("/home");

export const useApiServiceCategories = () =>
  useResource<{ categories: ApiServiceCategory[] }>("/service-categories");

export const useApiServiceCenters = () =>
  useResource<{ service_centers: ApiServiceCenter[] }>("/service-centers");

export const useApiServiceCenter = (id: number | string | null | undefined) =>
  useResource<{ service_center: ApiServiceCenter }>(id ? `/service-centers/${id}` : "/service-centers");

export const useApiCarBrands = () =>
  useResource<{ brands: ApiCarBrand[] }>("/search/brands");

export const useApiCarModels = (brandId: number | null) =>
  useResource<{ models: ApiCarModel[] }>("/search/models", brandId ? { brand_id: brandId } : undefined, [brandId]);

export const useApiCarFuels = (brandId: number | null, modelId: number | null) =>
  useResource<{ fuels: ApiFuelType[] }>(
    "/search/fuels",
    brandId && modelId ? { brand_id: brandId, model_id: modelId } : undefined,
    [brandId, modelId]
  );

export const useApiOffers = () =>
  useResource<{ offers: ApiOffer[]; tabular_offers: unknown[]; seo: import("../lib/SeoHead").SeoPayload }>("/offers");

export const useApiFaqs = (categoryId?: number | null) =>
  useResource<{ faqs: ApiFaq[]; seo: import("../lib/SeoHead").SeoPayload }>(
    "/faqs",
    categoryId ? { category_id: categoryId } : undefined,
    [categoryId]
  );

export const useApiServices = (brandId?: number | null, modelId?: number | null, fuelId?: number | null) =>
  useResource<{
    categories: ApiServiceCategory[];
    available_category_ids: number[];
    brand: ApiCarBrand | null;
    model: ApiCarModel | null;
    fuel: ApiFuelType | null;
    seo: import("../lib/SeoHead").SeoPayload;
  }>(
    "/services",
    brandId && modelId && fuelId ? { brand_id: brandId, model_id: modelId, fuel_id: fuelId } : undefined,
    [brandId, modelId, fuelId]
  );

export const useApiServiceCategory = (
  slug: string,
  vehicleSlugs?: { brand?: string; model?: string; fuel?: string }
) =>
  useResource<{
    category: ApiServiceCategory;
    services: { id: number; title: string; image?: string; vehicle_price?: number; vehicle_package_id?: number }[];
    price_show: 0 | 1;
    faqs: ApiFaq[];
    seo: import("../lib/SeoHead").SeoPayload;
  }>(`/services/${slug}`, vehicleSlugs, [slug, vehicleSlugs?.brand, vehicleSlugs?.model, vehicleSlugs?.fuel]);

export const useApiServiceDetail = (
  categorySlug: string,
  serviceSlug: string,
  vehicleIds?: { brand_id?: number; model_id?: number; fuel_id?: number }
) =>
  useResource<{
    category: ApiServiceCategory;
    service: { id: number; title: string; image?: string };
    vehicle_price: number | null;
    vehicle_package_id: number | null;
    seo: import("../lib/SeoHead").SeoPayload;
  }>(`/services/${categorySlug}/${serviceSlug}`, vehicleIds, [
    categorySlug, serviceSlug, vehicleIds?.brand_id, vehicleIds?.model_id, vehicleIds?.fuel_id,
  ]);

