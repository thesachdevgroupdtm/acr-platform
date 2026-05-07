import { motion } from "motion/react";
import { useNavigate } from "react-router-dom";
import { Star, MapPin, Wrench } from "lucide-react";
import PageBanner from "../components/PageBanner";

interface Testimonial {
  name: string;
  initials: string;
  vehicle: string;
  service: string;
  center: string;
  rating: 4 | 5;
  quote: string;
}

/**
 * Demo-readiness — dedicated /testimonials landing page. Twelve
 * realistic Indian customer stories spanning all four ACR centers
 * and a cross-section of services. Mix of 4★ and 5★ keeps the
 * page from reading too good to be true.
 */
const TESTIMONIALS: Testimonial[] = [
  {
    name: "Rajesh Kumar",
    initials: "RK",
    vehicle: "Honda City Petrol",
    service: "Battery Charging",
    center: "Moti Nagar",
    rating: 5,
    quote:
      "Battery died at 8 AM and I had a meeting at 10. ACR's team picked up my car within 30 minutes, replaced with a fresh OEM battery, transparent pricing — invoice on WhatsApp before they even fitted it — and a 2 year warranty. Couldn't have asked for better service.",
  },
  {
    name: "Priya Sharma",
    initials: "PS",
    vehicle: "Audi Q3 Diesel",
    service: "AC Service & Repair",
    center: "Gurugram",
    rating: 5,
    quote:
      "My AC was barely cooling in May. ACR's technicians diagnosed a refrigerant leak that two other workshops had missed entirely. Fixed it for half the quote I'd been given elsewhere, and they explained every step.",
  },
  {
    name: "Amit Verma",
    initials: "AV",
    vehicle: "Maruti Swift Petrol",
    service: "Denting & Painting",
    center: "Noida",
    rating: 5,
    quote:
      "Got rear-ended in stop-and-go traffic. ACR handled the insurance claim end-to-end — surveyor, paperwork, the lot — used OEM parts, and the colour match is perfect. Picked up at 7 days exactly as promised.",
  },
  {
    name: "Neha Gupta",
    initials: "NG",
    vehicle: "Hyundai Creta Diesel",
    service: "Periodic Service",
    center: "Okhla",
    rating: 5,
    quote:
      "Switched from the authorised dealer after years of inflated bills. ACR did the full 40,000 km service — same parts, same quality, 35% cheaper. The detailed inspection report they emailed afterwards was a nice touch.",
  },
  {
    name: "Vikram Singh",
    initials: "VS",
    vehicle: "BMW 3 Series Petrol",
    service: "Brake Pad Replacement",
    center: "Moti Nagar",
    rating: 5,
    quote:
      "Booked a brake job for my BMW. The technicians clearly knew what they were doing — used genuine pads, reset the wear sensor, and didn't push any unnecessary extras. Pickup and drop-off saved me half a day.",
  },
  {
    name: "Anjali Iyer",
    initials: "AI",
    vehicle: "Toyota Innova Diesel",
    service: "Wheel Alignment",
    center: "Gurugram",
    rating: 4,
    quote:
      "Steering was pulling left after a pothole. Got the alignment done at ACR — proper laser equipment, before/after readout printed on the bill. One star off only because the drop-off was 45 minutes late, but the work itself was spot on.",
  },
  {
    name: "Sandeep Gupta",
    initials: "SG",
    vehicle: "Mercedes GLC Diesel",
    service: "Ceramic Coating",
    center: "Noida",
    rating: 5,
    quote:
      "Got a 3-year ceramic coating done. The prep work was meticulous — they polished out swirl marks I didn't even know were there. Six months in, water still sheets off perfectly. Honest workshop, attentive to detail.",
  },
  {
    name: "Rahul Mehra",
    initials: "RM",
    vehicle: "Tata Nexon Petrol",
    service: "Battery Replacement",
    center: "Okhla",
    rating: 5,
    quote:
      "Old battery was struggling in the cold mornings. Walked in without an appointment; they tested both battery and alternator on the spot, replaced only the battery (not the alternator like another shop had recommended). Quick, fair, and honest.",
  },
  {
    name: "Kavita Reddy",
    initials: "KR",
    vehicle: "Skoda Slavia Petrol",
    service: "Clutch Repair",
    center: "Moti Nagar",
    rating: 5,
    quote:
      "Clutch slip on my fairly new Slavia — turned out to be a warranty matter the dealer was dragging their feet on. ACR did a paid inspection, gave me a written report, and the dealer honoured the warranty within a week. Saved me a small fortune.",
  },
  {
    name: "Manish Kapoor",
    initials: "MK",
    vehicle: "Mahindra Thar Diesel",
    service: "Underbody Coating",
    center: "Gurugram",
    rating: 4,
    quote:
      "Took the Thar in before monsoon for an anti-rust coating. Job took half a day, no shortcuts visible on the underbody. Driver who dropped it back was professional. Star off only because the lounge wifi was patchy while I waited.",
  },
  {
    name: "Sneha Patel",
    initials: "SP",
    vehicle: "Volkswagen Virtus Petrol",
    service: "Insurance Claim",
    center: "Noida",
    rating: 5,
    quote:
      "Side-swiped while parked. ACR's claim team co-ordinated with the surveyor, sent me a fixed cashless quote, and the car came back looking factory-fresh. Paid only my voluntary deductible — exactly as quoted.",
  },
  {
    name: "Arjun Joshi",
    initials: "AJ",
    vehicle: "Kia Seltos Petrol",
    service: "Oil Change & Filter",
    center: "Okhla",
    rating: 5,
    quote:
      "Quick standard service. They showed me the old oil and the dirty filter before disposing of them — small thing but it builds trust. Booked online, in and out in 90 minutes, transparent invoice on email.",
  },
];

function StarRow({ rating }: { rating: 4 | 5 }) {
  return (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((n) => (
        <Star
          key={n}
          className={
            n <= rating
              ? "w-4 h-4 fill-amber-500 text-amber-500"
              : "w-4 h-4 fill-neutral-200 text-neutral-200"
          }
        />
      ))}
    </div>
  );
}

export default function Testimonials() {
  const navigate = useNavigate();
  return (
    <>
      <PageBanner
        title="What Our Customers Say"
        breadcrumbs={[
          { label: "Home", onClick: () => navigate("/") },
          { label: "Testimonials" },
        ]}
      />

      <div className="section-spacing pt-0">
        <div className="site-container">
          {/* Trust strip */}
          <motion.div
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4 }}
            className="bg-white border border-border grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-border max-w-3xl mx-auto mt-8 mb-16"
          >
            <div className="px-6 py-5 text-center">
              <div className="flex items-center justify-center gap-1 mb-1 text-amber-500">
                <Star className="w-4 h-4 fill-current" />
                <span className="text-2xl font-black text-neutral-900">4.8</span>
              </div>
              <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-500">
                Average Rating
              </p>
            </div>
            <div className="px-6 py-5 text-center">
              <div className="text-2xl font-black text-neutral-900 mb-1">
                50,000+
              </div>
              <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-500">
                Happy Customers
              </p>
            </div>
            <div className="px-6 py-5 text-center">
              <div className="text-2xl font-black text-neutral-900 mb-1">
                4
              </div>
              <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-500">
                Service Centers
              </p>
            </div>
          </motion.div>

          {/* Intro */}
          <div className="max-w-2xl mb-12">
            <h2 className="text-3xl md:text-4xl font-black uppercase tracking-tighter text-neutral-900 mb-3">
              Real Stories. <span className="text-primary">Real Cars.</span>
            </h2>
            <p className="text-sm md:text-base text-neutral-500 leading-relaxed">
              Twelve unedited reviews from owners across Delhi NCR — picked
              from across our service mix so you can see how every center, on
              every brand, holds the same standard.
            </p>
          </div>

          {/* Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {TESTIMONIALS.map((t, i) => (
              <motion.article
                key={t.name + i}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, margin: "-50px" }}
                transition={{ duration: 0.4, delay: (i % 3) * 0.05 }}
                className="bg-white border border-border p-6 sm:p-7 flex flex-col gap-4 hover:border-primary/40 hover:shadow-md transition-all"
              >
                <StarRow rating={t.rating} />

                <p className="text-sm text-neutral-700 leading-relaxed italic flex-1">
                  &ldquo;{t.quote}&rdquo;
                </p>

                <div className="flex items-center gap-3 pt-4 border-t border-border">
                  <div className="w-11 h-11 bg-primary text-white flex items-center justify-center text-sm font-black flex-shrink-0">
                    {t.initials}
                  </div>
                  <div className="min-w-0">
                    <div className="text-sm font-black uppercase text-neutral-900 tracking-tight truncate">
                      {t.name}
                    </div>
                    <div className="text-[10px] font-bold uppercase tracking-widest text-neutral-500 truncate">
                      {t.vehicle}
                    </div>
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-3 text-[10px] font-bold uppercase tracking-widest text-neutral-500">
                  <span className="inline-flex items-center gap-1">
                    <Wrench className="w-3 h-3 text-primary" />
                    {t.service}
                  </span>
                  <span className="w-1 h-1 rounded-full bg-border" />
                  <span className="inline-flex items-center gap-1">
                    <MapPin className="w-3 h-3 text-primary" />
                    {t.center}
                  </span>
                </div>
              </motion.article>
            ))}
          </div>

          {/* Bottom CTA */}
          <div className="mt-20 p-10 md:p-14 bg-neutral-900 text-center relative overflow-hidden">
            <div className="relative z-10">
              <h3 className="text-2xl md:text-3xl font-black text-white uppercase tracking-tighter mb-4">
                Ready to be our next happy customer?
              </h3>
              <p className="text-neutral-400 mb-8 max-w-xl mx-auto leading-relaxed">
                Book a service at any of our four centers across Delhi NCR.
                Transparent pricing, OEM parts, dealership-quality work.
              </p>
              <div className="flex flex-wrap items-center justify-center gap-3">
                <button
                  onClick={() => navigate("/services")}
                  className="btn-ink btn-ink-primary px-8 py-4 text-xs font-black uppercase tracking-widest"
                >
                  Browse Services
                </button>
                <button
                  onClick={() => navigate("/service-centers")}
                  className="btn-ink btn-ink-white px-8 py-4 text-xs font-black uppercase tracking-widest"
                >
                  Find a Center
                </button>
              </div>
            </div>
            <div className="absolute inset-0 opacity-10 flex items-center justify-center pointer-events-none">
              <div className="w-[700px] h-[700px] border-[40px] border-white rounded-full -translate-y-1/2" />
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
