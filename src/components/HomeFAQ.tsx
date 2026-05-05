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
 * Home page FAQ section — premium variant.
 *
 * Why this is its own component instead of a `variant` prop on the
 * shared `FAQAccordion` (used by ServiceCategory / ServiceDetail /
 * CmsPage):
 *
 *   - Home diverges enough (Q01/Q02… number badges instead of an
 *     icon, larger padding, primary-tinted hover and open states,
 *     section-level "Still have questions?" CTA strip) that a
 *     variant prop on FAQAccordion would clutter it with
 *     conditional branching.
 *   - FAQAccordion's contract is stable and consumed by three
 *     pages already; the demo-readiness brief explicitly forbade
 *     touching it.
 *
 * Behavior is identical to FAQAccordion — initial openIndex=null
 * (all closed on page load), single-open-at-a-time, motion height
 * + opacity transition, chevron rotation, aria-expanded /
 * aria-controls. Only the visual treatment differs.
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

export default function HomeFAQ({ setCurrentPage }: HomeFAQProps) {
  const [openIndex, setOpenIndex] = useState<number | null>(null);

  const toggle = (i: number) => {
    setOpenIndex((prev) => (prev === i ? null : i));
  };

  return (
    <section className="py-20 bg-neutral-50 border-y border-border">
      <div className="site-container">
        {/* Section header */}
        <div className="text-center mb-12 max-w-2xl mx-auto">
          <div className="flex items-center justify-center gap-3 mb-4">
            <div className="w-8 h-0.5 bg-primary" />
            <span className="text-xs uppercase tracking-widest text-primary font-bold">
              Frequently Asked
            </span>
            <div className="w-8 h-0.5 bg-primary" />
          </div>
          <h2 className="text-3xl md:text-5xl font-black uppercase tracking-tighter text-neutral-900 mb-4">
            Questions We Get{" "}
            <span className="text-primary italic font-black">Asked.</span>
          </h2>
          <p className="text-sm md:text-base text-muted leading-relaxed">
            Quick answers to what most customers want to know before booking.
          </p>
        </div>

        {/* FAQ list */}
        <div className="max-w-4xl mx-auto space-y-4">
          {HOME_FAQS.map((faq, i) => {
            const isOpen = openIndex === i;
            const numLabel = `Q${String(i + 1).padStart(2, "0")}`;
            return (
              <div
                key={i}
                className={`bg-white border transition-all duration-200 ${
                  isOpen
                    ? "border-primary shadow-md"
                    : "border-border hover:border-primary/60"
                }`}
              >
                <button
                  onClick={() => toggle(i)}
                  aria-expanded={isOpen}
                  aria-controls={`home-faq-panel-${i}`}
                  className="w-full flex items-center gap-4 sm:gap-6 p-6 sm:p-7 text-left"
                >
                  {/* Number badge */}
                  <span
                    className={`shrink-0 text-xs sm:text-sm font-black tracking-widest transition-colors ${
                      isOpen ? "text-primary" : "text-primary/70"
                    }`}
                  >
                    {numLabel}
                  </span>

                  {/* Question */}
                  <span className="flex-1 text-base sm:text-lg font-black uppercase text-neutral-900 tracking-tighter leading-snug">
                    {faq.q}
                  </span>

                  {/* Chevron */}
                  <ChevronDown
                    className={`w-5 h-5 shrink-0 transition-all duration-300 ${
                      isOpen
                        ? "rotate-180 text-primary"
                        : "text-neutral-400"
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
                      transition={{ duration: 0.25, ease: "easeOut" }}
                      className="overflow-hidden"
                    >
                      <div className="px-6 sm:px-7 pb-6 sm:pb-7">
                        <div className="border-t border-border pt-5 ml-0 sm:ml-14 sm:pl-2 text-sm md:text-base text-neutral-600 leading-relaxed">
                          {faq.a}
                        </div>
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>
            );
          })}
        </div>

        {/* Bottom CTA strip */}
        <div className="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4 text-center">
          <span className="text-sm text-neutral-600">
            Still have questions?
          </span>
          <button
            onClick={() => setCurrentPage("contact")}
            className="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-widest text-primary hover:underline"
          >
            Contact our advisors
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </section>
  );
}
