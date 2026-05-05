/**
 * useSubNavSync — Phase 2.5.6 + 2.5.7.
 *
 * Two-way sync between a sticky horizontal sub-nav and the page-body
 * sections it anchors to. Three responsibilities, one hook:
 *
 *   1. Scroll-spy: an IntersectionObserver watches every element
 *      in the page body that carries `data-subnav-section="{slug}"`.
 *      When a section's heading enters the upper-middle band of the
 *      viewport (rootMargin '-15% 0px -55% 0px'), it becomes the
 *      active section. The sub-nav reads `activeSlug` to draw the
 *      blue underline on the matching link.
 *
 *   2. Auto-scroll: when `activeSlug` changes — whether from page
 *      scroll OR from the optimistic click setter — the active
 *      link in the sub-nav is scrolled into view via
 *      `scrollIntoView({ inline: 'center' })`. So if the user is
 *      reading section #10 of 12 and that link was off-screen in
 *      the horizontal nav, the nav scrolls itself to reveal it.
 *      Standard scrollspy-with-self-scroll pattern.
 *
 *   3. Click-driven page scroll: `scrollToSection(slug)` smooth-
 *      scrolls the page to a section, accounting for the sticky
 *      chrome (header + this sub-nav). Pre-sets `activeSlug`
 *      optimistically so the underline updates before the smooth
 *      scroll completes. Click handlers should call this AND
 *      preventDefault() on any anchor.
 *
 * Sub-nav links MUST carry `data-subnav-link={slug}` so the
 * auto-scroll can find them. Page-body sections MUST carry
 * `data-subnav-section={slug}` so the observer can find them.
 *
 * Phase 2.5.7 — `rebindKey` is the most important parameter on
 * routed pages that render a skeleton placeholder during data
 * load. The IntersectionObserver effect runs once on mount; if
 * the section nodes don't exist at that moment (skeleton-only
 * first render), the observer registers nothing and stays dead
 * for the lifetime of the component. The consumer must bump
 * `rebindKey` when data arrives — typically via a token like
 * `${slug}:${isLoading ? "loading" : "ready"}`.
 */
import {
  useCallback,
  useEffect,
  useRef,
  useState,
  type RefObject,
} from "react";

interface UseSubNavSyncOptions {
  /** Sticky-chrome offset for click-driven page scrolls. The hook
   *  scrolls to `top: section.top + scrollY - (stickyOffsetPx + 16)`
   *  so the heading lands just below the sticky chrome with a
   *  16px breathing-room. */
  stickyOffsetPx: number;
  /** Auto-scroll the sub-nav to centre the active link. Defaults
   *  to true; pages can opt out for testing. */
  autoScrollNav?: boolean;
  /** Opaque token that forces the observer to re-bind whenever it
   *  changes. Two cases need this:
   *    - Routed pages whose section DOM gets replaced on
   *      navigation (parent component reused, props changed).
   *    - Pages with skeleton-first loading where the sections
   *      only appear in the DOM after data arrives.
   *  Compose with both: `${slug}:${isLoading ? 0 : 1}`. */
  rebindKey?: string | number | null;
}

interface UseSubNavSyncResult {
  /** Currently active section slug; "" until the first observation. */
  activeSlug: string;
  /** Optimistic setter — call from click handlers so the underline
   *  moves before the smooth scroll lands. The observer will
   *  reconcile on the next intersection cycle. */
  setActiveSlugManual: (slug: string) => void;
  /** Click-driven page scroll. Use in onClick handlers in place of
   *  raw scrollIntoView so the sticky-chrome offset is honoured. */
  scrollToSection: (slug: string) => void;
  /** Attach to the horizontally-scrolling container that holds the
   *  sub-nav links (the element with `overflow-x-auto`). */
  navRef: RefObject<HTMLElement | null>;
}

// Phase 2.5.10 — activation timing tuning. The activation line —
// where a section becomes "active" as the user scrolls down —
// moved from 15% to 25% of viewport height so the heading
// activates while it's still comfortably in the user's upper-third
// reading zone, NOT after it has nearly scrolled off the top.
//
// Operator's pre-2.5.10 symptom: "I'm clearly reading the section
// but the sub-nav still says the previous one."
//
// The two constants are derived from each other: ROOT_MARGIN's
// top inset (in %) drives the activation line at runtime
// (`window.innerHeight * ACTIVATION_LINE_RATIO`). Always tune
// them together.
const ROOT_MARGIN_TOP_PCT = 25;       // 2.5.10: was 15
const ROOT_MARGIN_BOTTOM_PCT = 50;    // 2.5.10: was 55
const ROOT_MARGIN = `-${ROOT_MARGIN_TOP_PCT}% 0px -${ROOT_MARGIN_BOTTOM_PCT}% 0px`;
const ACTIVATION_LINE_RATIO = ROOT_MARGIN_TOP_PCT / 100;

export function useSubNavSync({
  stickyOffsetPx,
  autoScrollNav = true,
  rebindKey = null,
}: UseSubNavSyncOptions): UseSubNavSyncResult {
  const [activeSlug, setActiveSlug] = useState<string>("");
  const navRef = useRef<HTMLElement | null>(null);

  /* ─────────── Scroll-spy ─────────── */
  //
  // Phase 2.5.8 — algorithm rewrite to fix the "snap back to OVERVIEW"
  // mid-page resync bug.
  //
  // The previous implementation read only the CHANGED entries from
  // each IntersectionObserver callback and sorted them ascending by
  // `boundingClientRect.top`. Two flaws compounded:
  //
  //   1. Entries-only view: when section A's state didn't change in
  //      a given fire (it's been intersecting for a while), it
  //      wasn't in `entries`. So the callback could pick a stale
  //      "topmost" from a fire that only contained section B
  //      exiting, and miss the still-active section A.
  //
  //   2. Wrong sort key: among multiple intersecting sections, the
  //      smallest `top` (most-negative, i.e. furthest above
  //      viewport) won. That's "first-entered" — but scrollspy UX
  //      wants "most-recently-passed-the-activation-line." The user
  //      reads the section whose heading just scrolled past the
  //      sticky chrome — that section has the LARGEST `top` ≤
  //      activation line, not the smallest.
  //
  // Fix: maintain a Set of currently-intersecting section elements
  // across IO fires (rather than reading only the changed entries),
  // re-measure each on every fire (boundingClientRect from a stale
  // entry can be wrong on fast scrolls), then pick the section
  // whose top is the largest value still ≤ the activation line at
  // 15% of viewport height. Falls back to the topmost intersecting
  // section if no section has crossed the activation line yet
  // (rare — happens at the very top of the page when only the
  // first section is visible).
  useEffect(() => {
    const sections = document.querySelectorAll<HTMLElement>(
      "[data-subnav-section]",
    );
    if (sections.length === 0) return;

    // Initial-active fallback — first registered section wins until
    // the observer's first callback overrides. Functional update
    // form keeps any pre-existing activeSlug intact.
    setActiveSlug((current) => {
      if (current) return current;
      const firstSlug = sections[0].getAttribute("data-subnav-section");
      return firstSlug ?? "";
    });

    const intersecting = new Set<HTMLElement>();

    const observer = new IntersectionObserver(
      (entries) => {
        // 1. Maintain the running "currently-intersecting" set.
        for (const entry of entries) {
          const target = entry.target as HTMLElement;
          if (entry.isIntersecting) intersecting.add(target);
          else intersecting.delete(target);
        }
        if (intersecting.size === 0) return;

        // 2. Re-measure each on this tick — IntersectionObserverEntry
        //    boundingClientRect can be stale on fast scrolls.
        const measured = Array.from(intersecting).map((el) => ({
          el,
          top: el.getBoundingClientRect().top,
        }));

        // 3. Activation line at 15% of viewport (matches rootMargin
        //    top inset). Sections whose top has crossed the line
        //    going up are "passed".
        const activationLine = window.innerHeight * ACTIVATION_LINE_RATIO;
        const passed = measured.filter((m) => m.top <= activationLine);

        // 4. Pick the most-recently-passed section (largest top
        //    among passed). If none have passed yet, pick the
        //    topmost intersecting section (smallest top).
        let chosen: HTMLElement;
        if (passed.length > 0) {
          chosen = passed.reduce((a, b) => (a.top > b.top ? a : b)).el;
        } else {
          chosen = measured.reduce((a, b) => (a.top < b.top ? a : b)).el;
        }

        const slug = chosen.getAttribute("data-subnav-section");
        if (slug) setActiveSlug(slug);
      },
      { rootMargin: ROOT_MARGIN, threshold: [0, 0.1] },
    );

    sections.forEach((s) => observer.observe(s));
    return () => observer.disconnect();
  }, [rebindKey]);

  /* ─────────── Optimistic click setter ─────────── */
  const setActiveSlugManual = useCallback((slug: string) => {
    setActiveSlug(slug);
  }, []);

  /* ─────────── Auto-scroll active link into nav ─────────── */
  useEffect(() => {
    if (!autoScrollNav) return;
    if (!activeSlug || !navRef.current) return;
    const link = navRef.current.querySelector<HTMLElement>(
      `[data-subnav-link="${CSS.escape(activeSlug)}"]`,
    );
    if (!link) return;
    // block: 'nearest' — never disturbs vertical page scroll.
    // inline: 'center' — centres the active link in the nav viewport.
    // behavior: 'smooth' — animated.
    link.scrollIntoView({
      behavior: "smooth",
      block: "nearest",
      inline: "center",
    });
  }, [activeSlug, autoScrollNav]);

  /* ─────────── Click-driven page scroll ─────────── */
  const scrollToSection = useCallback(
    (slug: string) => {
      const el = document.querySelector<HTMLElement>(
        `[data-subnav-section="${CSS.escape(slug)}"]`,
      );
      if (!el) return;
      // 16px breathing-room under the sticky chrome.
      const top =
        el.getBoundingClientRect().top + window.scrollY - (stickyOffsetPx + 16);
      window.scrollTo({ top, behavior: "smooth" });
      // Optimistic — observer will reconcile when smooth-scroll settles.
      setActiveSlug(slug);
    },
    [stickyOffsetPx],
  );

  return { activeSlug, setActiveSlugManual, scrollToSection, navRef };
}
