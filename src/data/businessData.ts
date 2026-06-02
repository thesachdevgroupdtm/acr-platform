// B5-partial — LOCATIONS migrated to the backend service_centers table.
// Read via useServiceCenters() hook (src/hooks/useServiceCenters.ts).
// TESTIMONIALS and BUSINESS_INFO remain v1 — see B5-followup for the
// post-launch migration of those to backend tables.

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
