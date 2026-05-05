import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { ChevronDown, MessageSquare } from "lucide-react";

export interface FAQItem {
  q: string;
  a: string;
}

interface FAQAccordionProps {
  faqs: FAQItem[];
  /** Optional outer className (e.g. spacing override). Defaults to `space-y-3`. */
  className?: string;
}

/**
 * Demo-readiness — shared FAQ accordion.
 *
 * Replaces three pre-existing FAQ patterns site-wide:
 *  - ServiceCategory.tsx + ServiceDetail.tsx: always-visible cards
 *    (every Q + A always rendered) — no toggle at all.
 *  - CmsPage.tsx: chevron rendered for show but the answer was
 *    rendered unconditionally beneath the question (visual lie).
 *
 * Canonical behavior, applied uniformly:
 *  - All FAQs CLOSED on page load (initial openIndex = null).
 *  - Click a question → that one opens; any previously-open one
 *    closes. Single-open-at-a-time.
 *  - Click an open question → closes (toggle).
 *  - Chevron rotates 180° when open.
 *  - Smooth height + opacity transition via motion/react.
 *
 * Home.tsx keeps its own custom-styled accordion (different
 * visual treatment, no MessageSquare icon) — that file just got
 * its initial-state bug fixed (0 → null).
 */
export default function FAQAccordion({ faqs, className }: FAQAccordionProps) {
  const [openIndex, setOpenIndex] = useState<number | null>(null);

  const toggle = (i: number) => {
    setOpenIndex((prev) => (prev === i ? null : i));
  };

  return (
    <div className={className ?? "space-y-3"}>
      {faqs.map((faq, i) => {
        const isOpen = openIndex === i;
        return (
          <div
            key={i}
            className={`bg-white border transition-colors ${
              isOpen ? "border-primary/40" : "border-border"
            }`}
          >
            <button
              onClick={() => toggle(i)}
              aria-expanded={isOpen}
              aria-controls={`faq-panel-${i}`}
              className="w-full flex items-start gap-3 p-5 sm:p-6 text-left hover:bg-neutral-50 transition-colors"
            >
              <MessageSquare className="text-primary w-5 h-5 mt-0.5 shrink-0" />
              <span className="flex-1 text-base sm:text-lg font-black uppercase text-neutral-900 tracking-tighter">
                {faq.q}
              </span>
              <ChevronDown
                className={`w-5 h-5 text-neutral-400 shrink-0 mt-1 transition-transform duration-300 ${
                  isOpen ? "rotate-180 text-primary" : ""
                }`}
              />
            </button>

            <AnimatePresence initial={false}>
              {isOpen && (
                <motion.div
                  id={`faq-panel-${i}`}
                  key="content"
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: "auto", opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  transition={{ duration: 0.25, ease: "easeOut" }}
                  className="overflow-hidden"
                >
                  <p className="text-sm text-neutral-600 leading-relaxed pl-13 pr-5 sm:pr-6 pb-5 sm:pb-6 ml-8">
                    {faq.a}
                  </p>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        );
      })}
    </div>
  );
}
