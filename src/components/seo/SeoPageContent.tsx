import { useMemo } from "react";
import { motion } from "motion/react";

/**
 * Phase 4.5b-fix — typography wrapper for the body HTML.
 *
 * Phase 4.7.3 V-D — every H2 emitted by the SEO content renderer
 * must use `.section-heading` + the dual-colour accent span on its
 * last word (CR-3 / CR-4 from the brand manual). The server saves
 * sanitized HTML; we don't have a markdown→JSX hook, so the
 * transform runs client-side: parse, walk H2s, split the trailing
 * word into `<span class="section-heading-accent">…</span>`, and
 * collapse the manual's chapter-style period terminator (manual
 * pp. 5/7/22) so headings read like "BRAND <span>STORY.</span>".
 *
 * Sanitization happens server-side (SeoPage::sanitizeHtml) on
 * save, so dangerouslySetInnerHTML is safe — the operator can
 * only inject the whitelisted tag set.
 */

/**
 * Walk the parsed body and rewrite every H2 so its last word
 * becomes the brand-blue accent. Pure DOM mutation; the parser is
 * scoped to a detached document so we never touch the live DOM.
 */
function brandifyH2s(html: string): string {
  if (typeof window === "undefined" || !html) return html;
  const doc = new DOMParser().parseFromString(`<div>${html}</div>`, "text/html");
  const root = doc.body.firstElementChild;
  if (!root) return html;

  for (const h2 of Array.from(root.querySelectorAll("h2"))) {
    // Use existing classes + the canonical utility. Inline keeps the
    // operator's per-page overrides (rare) intact.
    h2.classList.add("section-heading");
    const text = (h2.textContent ?? "").trim();
    if (!text) continue;
    const lastSpace = text.lastIndexOf(" ");
    const head = lastSpace === -1 ? "" : text.slice(0, lastSpace);
    let tail = lastSpace === -1 ? text : text.slice(lastSpace + 1);
    // Drop existing terminator before re-appending the canonical ".".
    tail = tail.replace(/[.?!]$/, "") + ".";
    h2.innerHTML = head
      ? `${head} <span class="section-heading-accent">${tail}</span>`
      : `<span class="section-heading-accent">${tail}</span>`;
  }

  return root.innerHTML;
}

export default function SeoPageContent({ html }: { html: string }) {
  const transformed = useMemo(() => brandifyH2s(html), [html]);

  return (
    <motion.div
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.35, delay: 0.05 }}
      className="seo-page-body max-w-3xl
        [&>p]:text-[17px] [&>p]:leading-[1.75] [&>p]:text-neutral-700 [&>p]:font-medium [&>p]:mb-5
        [&>h2]:mt-12 [&>h2]:mb-4
        [&>h3]:text-xl [&>h3]:font-display [&>h3]:font-semibold [&>h3]:uppercase [&>h3]:tracking-tight [&>h3]:text-neutral-900 [&>h3]:mt-10 [&>h3]:mb-3
        [&>h4]:text-lg [&>h4]:font-display [&>h4]:font-semibold [&>h4]:text-neutral-900 [&>h4]:mt-8 [&>h4]:mb-2
        [&_strong]:text-neutral-900 [&_strong]:font-bold
        [&_em]:not-italic [&_em]:text-neutral-700 [&_em]:font-semibold
        [&_a]:text-primary [&_a]:font-semibold [&_a]:underline [&_a]:underline-offset-2 [&_a]:decoration-primary/40 hover:[&_a]:decoration-primary
        [&>blockquote]:border-l-4 [&>blockquote]:border-primary [&>blockquote]:bg-neutral-50 [&>blockquote]:px-6 [&>blockquote]:py-4 [&>blockquote]:my-6 [&>blockquote]:text-neutral-700
        [&>ul]:my-5 [&>ul]:pl-6 [&>ul]:space-y-2 [&>ul>li]:text-[17px] [&>ul>li]:text-neutral-700 [&>ul>li]:leading-relaxed [&>ul>li]:relative [&>ul>li]:pl-2 marker:[&>ul>li]:text-primary
        [&>ol]:my-5 [&>ol]:pl-6 [&>ol]:space-y-2 [&>ol>li]:text-[17px] [&>ol>li]:text-neutral-700 [&>ol>li]:leading-relaxed marker:[&>ol>li]:text-primary marker:[&>ol>li]:font-bold
        [&>img]:w-full [&>img]:h-auto [&>img]:border [&>img]:border-border [&>img]:my-6
      "
      dangerouslySetInnerHTML={{ __html: transformed }}
    />
  );
}
