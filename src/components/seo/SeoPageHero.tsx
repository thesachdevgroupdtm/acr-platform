import { motion } from "motion/react";
import { ChevronRight } from "lucide-react";

/**
 * Phase 4.5b-fix — premium hero header for /:slug.
 *
 * Visual language matches /cms-preview:
 *   - oversized uppercase title with `tracking-tighter`
 *   - primary-color accent on a single emphasized word
 *   - generous breathing room and breadcrumb trail
 *   - dark textured backdrop for depth
 *
 * Operator can highlight one word in the title by wrapping it
 * in `*emphasis*`; the renderer splits on the marker. If no
 * marker present, the whole title renders flat.
 */
interface Crumb {
  label: string;
  onClick?: () => void;
}

interface SeoPageHeroProps {
  title: string;
  category?: string | null;
  excerpt?: string | null;
  breadcrumbs?: Crumb[];
}

/**
 * Highlight the LAST word of the title in primary color so each
 * page gets the same visual cadence as /cms-preview without
 * requiring the operator to mark up emphasis manually.
 */
function splitTitle(title: string): { lead: string; accent: string } {
  const trimmed = title.trim();
  const idx = trimmed.lastIndexOf(" ");
  if (idx <= 0) {
    return { lead: "", accent: trimmed };
  }
  return {
    lead: trimmed.slice(0, idx),
    accent: trimmed.slice(idx + 1),
  };
}

export default function SeoPageHero({
  title,
  category,
  excerpt,
  breadcrumbs = [],
}: SeoPageHeroProps) {
  const { lead, accent } = splitTitle(title);

  return (
    <section className="bg-neutral-900 text-white relative overflow-hidden">
      {/* subtle vignette to lift the type off the dark backdrop */}
      <div className="absolute inset-0 bg-gradient-to-br from-neutral-900 via-neutral-900 to-neutral-950 pointer-events-none" />
      <div className="absolute inset-0 opacity-[0.035] pointer-events-none"
        style={{
          backgroundImage:
            "radial-gradient(circle at 20% 30%, white 1px, transparent 1px), radial-gradient(circle at 80% 60%, white 1px, transparent 1px)",
          backgroundSize: "32px 32px",
        }}
      />

      <div className="relative site-container py-16 md:py-24">
        {breadcrumbs.length > 0 && (
          <nav
            aria-label="Breadcrumb"
            className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-6"
          >
            {breadcrumbs.map((c, i) => {
              const isLast = i === breadcrumbs.length - 1;
              return (
                <span key={`${c.label}-${i}`} className="flex items-center gap-2">
                  {c.onClick && !isLast ? (
                    <button
                      type="button"
                      onClick={c.onClick}
                      className="hover:text-primary transition-colors"
                    >
                      {c.label}
                    </button>
                  ) : (
                    <span className={isLast ? "text-white" : ""}>{c.label}</span>
                  )}
                  {!isLast && <ChevronRight className="w-3 h-3 text-neutral-600" />}
                </span>
              );
            })}
          </nav>
        )}

        {category && (
          <motion.span
            initial={{ opacity: 0, y: 6 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
            className="inline-block bg-primary text-white px-3 py-1 text-[10px] font-bold uppercase tracking-widest mb-4"
          >
            {category}
          </motion.span>
        )}

        <motion.h1
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.45, ease: "easeOut" }}
          className="text-4xl md:text-5xl lg:text-6xl font-black uppercase tracking-tighter text-white leading-none max-w-4xl"
        >
          {lead && <>{lead} </>}
          <span className="text-primary">{accent}</span>
        </motion.h1>

        {excerpt && (
          <motion.p
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4, delay: 0.1 }}
            className="text-base md:text-lg text-neutral-300 font-medium leading-relaxed mt-6 max-w-3xl"
          >
            {excerpt}
          </motion.p>
        )}
      </div>
    </section>
  );
}
