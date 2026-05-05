import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { ChevronDown, ArrowRight } from "lucide-react";

interface HomeFAQProps {
  setCurrentPage: (page: string) => void;
}

interface HomeFAQItem {
  q: string;
  a: string;
}

/**
 * Home page FAQ section — premium variant v2.
 *
 * v1 (commit eff2212) shipped a light card-on-gray treatment that
 * read as flat / empty in operator review. v2 swaps the chrome
 * for a full-bleed automotive workshop image + dark gradient
 * overlay; FAQ cards land on top as frosted-glass while closed
 * and snap to solid-white with a primary border when open.
 *
 * Constraints preserved from v1:
 *   - Same six FAQ entries, verbatim, in the same order.
 *   - Same accordion contract: openIndex starts null (all
 *     closed), single-open-at-a-time, motion height + opacity
 *     transition, chevron 180° rotation, aria-expanded /
 *     aria-controls. Operator's behavior expectations don't shift
 *     between v1 and v2 — only the surface treatment.
 */
const HOME_FAQS: HomeFAQItem[] = [
  {
    q: "Is my manufacturer warranty valid if I service here?",
    a: "Absolutely. We use 100% Genuine OEM parts and manufacturer-approved synthetic oils, keeping your factory warranty fully intact under the 'Right to Repair' guidelines. Detailed service records are added to your vehicle's warranty book on every visit.",
  },
  {
    q: "Do you offer pickup and drop-off service?",
    a: "Yes — complimentary pickup and drop-off across Delhi NCR. Our team collects your car from your home or office, services it at one of our four centers, and returns it sanitized. Routine services are typically same-day.",
  },
  {
    q: "How do you handle insurance claims?",
    a: "We coordinate cashless claims directly with all major insurance providers. Our team handles the paperwork, surveyor coordination, and approvals end-to-end. Most claims are processed within 4 to 7 working days.",
  },
  {
    q: "Are your prices transparent?",
    a: "Every estimate is itemized — labour, parts, and taxes shown separately. You approve before any work begins. No hidden charges, no surprise bills. The final invoice matches the quoted estimate exactly.",
  },
  {
    q: "What brands do you service?",
    a: "All major brands — Maruti Suzuki, Hyundai, Honda, Toyota, Tata, Mahindra, Kia — plus premium marques including BMW, Mercedes-Benz, Audi, Volvo, Jeep, and Land Rover. Our technicians are certified for multi-brand expertise.",
  },
  {
    q: "How long does a typical service take?",
    a: "Routine work like an oil change or battery replacement: 2 to 3 hours. A general service: same day. Major repairs or full detailing: 1 to 3 days depending on scope. We share an accurate timeline with the estimate.",
  },
];

const BG_IMAGE_URL =
  "https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&w=1920&q=80";

export default function HomeFAQ({ setCurrentPage }: HomeFAQProps) {
  const [openIndex, setOpenIndex] = useState<number | null>(null);

  const toggle = (i: number) => {
    setOpenIndex((prev) => (prev === i ? null : i));
  };

  return (
    <section className="relative py-20 sm:py-24 lg:py-28 overflow-hidden">
      {/* Background image — sharp, no blur; overlay handles legibility. */}
      <div className="absolute inset-0 z-0">
        <img
          src={BG_IMAGE_URL}
          alt=""
          aria-hidden="true"
          className="w-full h-full object-cover object-center"
          loading="lazy"
          referrerPolicy="no-referrer"
        />
      </div>

      {/* Dark gradient overlay — black-heavy with a subtle primary tint
          in the bottom-right so the section reads as branded, not just
          dark. */}
      <div className="absolute inset-0 z-0 bg-gradient-to-br from-black/90 via-black/80 to-primary/30" />

      {/* Accent vignette — radial primary tint in the top-right gives
          the section depth without competing with the FAQ cards. */}
      <div
        className="absolute inset-0 z-0"
        style={{
          backgroundImage:
            "radial-gradient(circle at top right, rgba(31,79,163,0.18), transparent 55%)",
        }}
      />

      {/* Content */}
      <div className="relative z-10 max-w-5xl mx-auto px-6">
        {/* Header */}
        <div className="text-center mb-12 sm:mb-16">
          <div className="text-primary text-xs uppercase tracking-widest font-bold mb-4 flex items-center justify-center gap-3">
            <span className="h-px w-8 bg-primary" />
            Frequently Asked
            <span className="h-px w-8 bg-primary" />
          </div>
          <h2 className="text-3xl sm:text-5xl lg:text-6xl font-black uppercase tracking-tighter text-white mb-4">
            Questions We Get{" "}
            <span className="text-primary italic">Asked.</span>
          </h2>
          <p className="text-base sm:text-lg text-neutral-300 max-w-2xl mx-auto leading-relaxed">
            Quick answers to what most customers want to know before booking.
          </p>
        </div>

        {/* FAQ list */}
        <div className="max-w-3xl mx-auto space-y-3 sm:space-y-4">
          {HOME_FAQS.map((faq, i) => {
            const isOpen = openIndex === i;
            const numLabel = `Q${String(i + 1).padStart(2, "0")}`;
            return (
              <div
                key={i}
                className={`transition-all duration-300 ${
                  isOpen
                    ? "bg-white border-2 border-primary shadow-2xl"
                    : "bg-white/5 backdrop-blur-md border border-white/10 hover:bg-white/10 hover:border-primary"
                }`}
              >
                <button
                  onClick={() => toggle(i)}
                  aria-expanded={isOpen}
                  aria-controls={`home-faq-panel-${i}`}
                  className="w-full flex items-center justify-between gap-4 p-5 sm:p-6 text-left"
                >
                  <div className="flex items-center gap-4 sm:gap-5 flex-1 min-w-0">
                    <span
                      className={`text-sm font-black uppercase tracking-widest shrink-0 transition-colors ${
                        isOpen ? "text-primary" : "text-primary/70"
                      }`}
                    >
                      {numLabel}
                    </span>
                    <span
                      className={`text-base sm:text-lg font-black uppercase tracking-tighter leading-snug transition-colors ${
                        isOpen ? "text-neutral-900" : "text-white"
                      }`}
                    >
                      {faq.q}
                    </span>
                  </div>
                  <ChevronDown
                    className={`w-5 h-5 shrink-0 transition-all duration-300 ${
                      isOpen ? "text-primary rotate-180" : "text-white/60"
                    }`}
                  />
                </button>

                <AnimatePresence initial={false}>
                  {isOpen && (
                    <motion.div
                      id={`home-faq-panel-${i}`}
                      key="content"
                      initial={{ height: 0, opacity: 0 }}
                      animate={{ height: "auto", opacity: 1 }}
                      exit={{ height: 0, opacity: 0 }}
                      transition={{ duration: 0.3, ease: "easeOut" }}
                      className="overflow-hidden"
                    >
                      <div className="px-5 sm:px-6 pb-5 sm:pb-6">
                        <div className="pt-4 sm:pt-5 border-t border-neutral-200">
                          <p className="text-sm sm:text-base text-neutral-600 leading-relaxed pl-0 sm:pl-12">
                            {faq.a}
                          </p>
                        </div>
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>
            );
          })}
        </div>

        {/* Bottom CTA — sits on the dark backdrop. White ink-style
            button reads as the primary action without competing with
            the open card's primary-blue border. */}
        <div className="text-center mt-12 sm:mt-16">
          <p className="text-neutral-400 text-sm mb-4">
            Still have questions?
          </p>
          <button
            onClick={() => setCurrentPage("contact")}
            className="btn-ink btn-ink-white inline-flex items-center gap-2 px-8 py-4 text-xs font-black uppercase tracking-widest"
          >
            Contact our advisors
            <ArrowRight className="w-3.5 h-3.5 btn-arrow" />
          </button>
        </div>
      </div>
    </section>
  );
}
