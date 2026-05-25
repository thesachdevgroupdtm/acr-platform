import type { MouseEvent } from "react";
import { motion } from "motion/react";
import { ArrowRight } from "lucide-react";
import { useNavigate } from "react-router-dom";

interface SeoPageCtaProps {
  title: string;
  buttonText?: string | null;
  buttonUrl?: string | null;
}

/**
 * Phase 4.5b-fix — premium gradient CTA panel.
 *
 * Internal URLs (starting with `/`) are routed through
 * react-router's navigate() to keep navigation in-SPA;
 * external URLs (https://wa.me/..., tel:..., mailto:...)
 * use the default anchor behavior so the OS handler fires.
 */
export default function SeoPageCta({
  title,
  buttonText,
  buttonUrl,
}: SeoPageCtaProps) {
  const navigate = useNavigate();

  const handleClick = (e: MouseEvent<HTMLAnchorElement>) => {
    if (buttonUrl?.startsWith("/")) {
      e.preventDefault();
      navigate(buttonUrl);
    }
  };

  return (
    <motion.section
      initial={{ opacity: 0, y: 16 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, amount: 0.4 }}
      transition={{ duration: 0.4 }}
      className="my-16 relative overflow-hidden bg-gradient-to-br from-primary via-primary to-amber-600 p-10 md:p-14 text-center text-white"
    >
      {/* corner accent for visual depth */}
      <div className="absolute -top-12 -right-12 w-40 h-40 bg-white/5 rounded-full pointer-events-none" />
      <div className="absolute -bottom-12 -left-12 w-32 h-32 bg-black/10 rounded-full pointer-events-none" />

      <div className="relative">
        <span className="inline-block bg-white/15 text-white px-3 py-1 text-[10px] font-bold uppercase tracking-widest mb-4">
          Ready when you are
        </span>
        <h3 className="text-2xl md:text-3xl font-black uppercase tracking-tighter text-white max-w-2xl mx-auto leading-tight mb-6">
          {title}
        </h3>
        {buttonText && buttonUrl && (
          <a
            href={buttonUrl}
            onClick={handleClick}
            className="inline-flex items-center gap-2 bg-white text-neutral-900 px-8 py-4 text-xs font-black uppercase tracking-widest hover:bg-neutral-100 transition-colors group"
          >
            {buttonText}
            <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
          </a>
        )}
      </div>
    </motion.section>
  );
}
