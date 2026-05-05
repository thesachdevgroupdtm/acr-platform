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

// Phase 2.6a — `CAR_DATA` removed (was a static brand→models map; the
// vehicle picker now reads `/car-brands` + `/car-models` from the API).

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

// Phase 2.6a — `OfferCoupon` interface, `OFFERS` const,
// `computeCouponDiscount`, and `pickBestOffer` removed.
// Coupons are now backend-managed: list via `useCoupons('marketing')`,
// apply via `POST /v1/cart/coupon`, server computes the discount.
