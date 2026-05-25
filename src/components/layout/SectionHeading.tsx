import type * as React from "react";

interface Props {
  /**
   * Full heading text (e.g., "Trending Now" or "EXPLORE ARTICLES").
   * Phase 4.7 canonical pattern: last word becomes the
   * primary-blue accent. Use `accentWord` to override which word
   * the accent applies to (e.g., for "BIG GRID DUAL" you might
   * want "DUAL" as the accent rather than auto-splitting).
   */
  children: string;
  /** Override which word(s) become the accent. Defaults to the
   *  last whitespace-separated token of `children`. */
  accentWord?: string;
  /** Append a period to the accent? Defaults to true. */
  withPeriod?: boolean;
  /** Render as h2 (default) or h3. Pages typically use h2; SEO
   *  article body uses h3 inside content. */
  as?: "h2" | "h3";
  /** Extra Tailwind classes (e.g., margin overrides). */
  className?: string;
  /** Optional align — left default. Pass `center` to center. */
  align?: "left" | "center";
  /**
   * Phase 4.7.2 — surface contrast.
   *
   * `light` (default) renders head in `text-neutral-900` per the
   * manual's typography spec (p. 22 Workshop Black). `dark` flips
   * the head to `text-white` so the heading reads on Deep Navy or
   * black hero panels (manual p. 28 "Stories & Reels" — white-out
   * type on navy ground). Accent remains ACR Blue in both modes.
   */
  background?: "light" | "dark";
  /**
   * Phase 4.7.2 — terminator override.
   *
   * Default is `.` (the manual's chapter-title style, e.g.
   * "Essence.", "Hierarchy."). Pass `?` for question-form headings
   * such as "NEED A CUSTOM SOLUTION?" (Offers page). Pass `null` to
   * suppress the terminator entirely (rare — used only when the
   * heading is genuinely list-style and a trailing dot would read
   * as broken). `withPeriod=false` is honoured as an alias for
   * `terminator: null` for backwards compat with Phase 4.7.
   */
  terminator?: "." | "?" | null;
  /**
   * Phase 4.7.5 — size variant.
   *
   * `default` (omit) uses `.section-heading` — clamp(1.5rem, 3vw,
   * 2.25rem). `sm` uses `.section-heading-sm` — clamp(1.125rem,
   * 1.8vw, 1.375rem) — for long-string footer / divider H2s that
   * must fit on one line at desktop ≥1280px (e.g. "India's
   * Fastest-Growing Self-Owned Multi-Brand Network.").
   *
   * Weight stays SemiBold (600), dual-colour accent + period
   * preserved. CR-2 compliance unchanged across both sizes.
   */
  size?: "default" | "sm";
}

/**
 * Phase 4.7 canonical section heading. Phase 4.7.2 extends to cover
 * dark backgrounds, question-form terminators, and the 1-word case.
 *
 *   <SectionHeading>Trending Now</SectionHeading>
 *   → <h2 class="section-heading">TRENDING <span class="section-heading-accent">NOW.</span></h2>
 *
 *   <SectionHeading background="dark" terminator="?">
 *     Need a custom solution
 *   </SectionHeading>
 *   → <h2 class="section-heading text-white">NEED A CUSTOM <span class="section-heading-accent">SOLUTION?</span></h2>
 *
 *   <SectionHeading>Amenities</SectionHeading>   (1-word case)
 *   → <h2 class="section-heading"><span class="section-heading-accent">AMENITIES.</span></h2>
 *
 * 1-word special case (V-7 in Phase 4.7.2): when the input has no
 * whitespace, the *entire* word becomes the accent — there is no
 * neutral head. This preserves the dual-colour discipline rather
 * than degrading to a single-colour heading.
 *
 * Input is auto-uppercased via the CSS utility (`text-transform:
 * uppercase` lives on `.section-heading`), so callers can pass
 * Title-Case strings and trust the visual transform.
 *
 * D-4.7-9: every section's leading heading is an H2 in this style.
 * D-4.7-10: SEO article body uses `as="h3"` style via the
 *           `.article-heading-h2` class — wire that one inline
 *           rather than through this component (different context).
 */
const SectionHeading: React.FC<Props> = ({
  children,
  accentWord,
  withPeriod = true,
  as = "h2",
  className = "",
  align = "left",
  background = "light",
  terminator,
  size = "default",
}) => {
  const text = children.trim();
  let head = text;
  let tail = accentWord ?? "";

  if (!accentWord) {
    const lastSpace = text.lastIndexOf(" ");
    if (lastSpace === -1) {
      // 1-word case (V-7): whole word becomes accent.
      head = "";
      tail = text;
    } else {
      head = text.slice(0, lastSpace);
      tail = text.slice(lastSpace + 1);
    }
  } else {
    const idx = text.toLowerCase().lastIndexOf(accentWord.toLowerCase());
    if (idx !== -1 && idx + accentWord.length === text.length) {
      head = text.slice(0, idx).trim();
    }
  }

  // Resolve terminator. Explicit `terminator` prop wins; otherwise
  // fall back to `withPeriod` (legacy Phase 4.7 boolean → "." | null).
  const term = terminator !== undefined ? terminator : withPeriod ? "." : null;
  const stripped = tail.replace(/[.?!]$/, "");
  const accent = term ? `${stripped}${term}` : stripped;

  const alignCls = align === "center" ? "text-center" : "";
  const bgCls = background === "dark" ? "!text-white" : "";
  const sizeCls = size === "sm" ? "section-heading-sm" : "section-heading";
  const Tag = as;

  return (
    <Tag className={`${sizeCls} ${alignCls} ${bgCls} ${className}`.trim().replace(/\s+/g, " ")}>
      {head && <>{head} </>}
      <span className="section-heading-accent">{accent}</span>
    </Tag>
  );
};

export default SectionHeading;
