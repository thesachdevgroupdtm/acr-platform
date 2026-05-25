import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Twitter, Linkedin, MessageCircle, Link as LinkIcon, ArrowRight, CheckCircle2 } from "lucide-react";

interface TocItem {
  id: string;
  text: string;
}

interface Props {
  body: string;
  pageTitle: string;
  ctaTitle?: string | null;
  ctaButtonText?: string | null;
  ctaButtonUrl?: string | null;
}

/**
 * Phase 4.5b-polish — desktop reading sidebar.
 *
 * Renders three blocks stacked:
 *   1. Primary CTA (defaults to "Book Service Now" → /services
 *      when the page hasn't supplied its own).
 *   2. Auto-generated table of contents from H2s in the body.
 *      Each item scrolls smoothly to its anchor.
 *   3. Share buttons (Twitter / LinkedIn / WhatsApp / copy link).
 *
 * Hidden on mobile via `hidden lg:block` on the parent grid in
 * SeoPageView — the ReadingProgressBar + bottom CTA cover that
 * surface area on small screens without occluding content.
 */
export default function SeoPageStickyCta({
  body,
  pageTitle,
  ctaTitle,
  ctaButtonText,
  ctaButtonUrl,
}: Props) {
  const navigate = useNavigate();
  const [copied, setCopied] = useState(false);

  // Parse <h2> tags out of the body. The SeoPageContent renderer
  // doesn't add ids to them — assign them here based on heading
  // text and patch the live DOM after mount so the TOC anchors
  // resolve. Cheap operation (handful of headings).
  const tocItems = useMemo<TocItem[]>(() => {
    if (typeof window === "undefined") return [];
    const dom = new DOMParser().parseFromString(body, "text/html");
    return Array.from(dom.querySelectorAll("h2")).map((h, idx) => ({
      id: `seo-h-${idx}`,
      text: (h.textContent ?? "").trim(),
    }));
  }, [body]);

  // After SeoPageContent paints, walk the H2s and assign matching
  // ids so smooth-scroll anchors land on the correct element.
  useEffect(() => {
    if (tocItems.length === 0) return;
    const headings = document.querySelectorAll(".seo-page-body > h2");
    headings.forEach((el, idx) => {
      if (tocItems[idx]) el.id = tocItems[idx].id;
    });
  }, [tocItems]);

  const onCtaClick = () => {
    const url = ctaButtonUrl ?? "/services";
    if (url.startsWith("/")) {
      navigate(url);
    } else {
      window.location.href = url;
    }
  };

  const onShare = (channel: "twitter" | "linkedin" | "whatsapp") => {
    const url = typeof window !== "undefined" ? window.location.href : "";
    const text = encodeURIComponent(`${pageTitle} — ACR Mechanics`);
    const enc = encodeURIComponent(url);
    const target = {
      twitter:  `https://twitter.com/intent/tweet?text=${text}&url=${enc}`,
      linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${enc}`,
      whatsapp: `https://wa.me/?text=${text}%20${enc}`,
    }[channel];
    window.open(target, "_blank", "noopener,noreferrer");
  };

  const onCopyLink = async () => {
    if (typeof window === "undefined") return;
    try {
      await navigator.clipboard.writeText(window.location.href);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch {
      /* clipboard blocked — silent */
    }
  };

  // Sidebar heading is always the short generic prompt — the
  // operator's full CTA title (`ctaTitle`, when set) belongs in
  // the main SeoPageCta panel below the body. Echoing it here
  // creates a duplicate-text in the page that breaks strict-mode
  // text locators in tests.
  const heading    = "Need Service?";
  const buttonText = ctaButtonText ?? "Book Service Now";

  return (
    <aside
      data-testid="seo-page-sticky-cta"
      className="sticky top-6 space-y-5"
    >
      {/* CTA block */}
      <div className="bg-neutral-900 text-white p-5">
        <h3 className="text-base font-black uppercase tracking-tighter mb-3 leading-tight">
          {heading}
        </h3>
        <button
          type="button"
          onClick={onCtaClick}
          className="w-full inline-flex items-center justify-center gap-2 bg-primary text-white px-5 py-3 text-[10px] font-black uppercase tracking-widest hover:bg-amber-600 transition-colors group"
        >
          {buttonText}
          <ArrowRight className="w-3 h-3 group-hover:translate-x-1 transition-transform" />
        </button>
      </div>

      {/* Table of contents */}
      {tocItems.length > 1 && (
        <div className="bg-white border border-border p-5">
          <h4 className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-3">
            On this page
          </h4>
          <ul className="space-y-2 text-xs">
            {tocItems.map((item) => (
              <li key={item.id}>
                <a
                  href={`#${item.id}`}
                  onClick={(e) => {
                    e.preventDefault();
                    document
                      .getElementById(item.id)
                      ?.scrollIntoView({ behavior: "smooth", block: "start" });
                  }}
                  className="block text-neutral-700 hover:text-primary hover:underline transition-colors leading-snug"
                >
                  {item.text}
                </a>
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Share */}
      <div className="bg-white border border-border p-5">
        <h4 className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-3">
          Share
        </h4>
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => onShare("twitter")}
            aria-label="Share on Twitter"
            className="w-9 h-9 inline-flex items-center justify-center border border-border hover:border-primary hover:text-primary transition-colors"
          >
            <Twitter className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={() => onShare("linkedin")}
            aria-label="Share on LinkedIn"
            className="w-9 h-9 inline-flex items-center justify-center border border-border hover:border-primary hover:text-primary transition-colors"
          >
            <Linkedin className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={() => onShare("whatsapp")}
            aria-label="Share on WhatsApp"
            className="w-9 h-9 inline-flex items-center justify-center border border-border hover:border-primary hover:text-primary transition-colors"
          >
            <MessageCircle className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={onCopyLink}
            aria-label="Copy link"
            className="w-9 h-9 inline-flex items-center justify-center border border-border hover:border-primary hover:text-primary transition-colors"
          >
            {copied ? <CheckCircle2 className="w-4 h-4 text-primary" /> : <LinkIcon className="w-4 h-4" />}
          </button>
        </div>
      </div>
    </aside>
  );
}
