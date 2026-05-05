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

const ROOT_MARGIN = "-15% 0px -55% 0px"; // 30%-tall band, upper-middle viewport

export function useSubNavSync({
  stickyOffsetPx,
  autoScrollNav = true,
  rebindKey = null,
}: UseSubNavSyncOptions): UseSubNavSyncResult {
  const [activeSlug, setActiveSlug] = useState<string>("");
  const navRef = useRef<HTMLElement | null>(null);

  /* ─────────── Scroll-spy ─────────── */
  useEffect(() => {
    const sections = document.querySelectorAll<HTMLElement>(
      "[data-subnav-section]",
    );
    if (sections.length === 0) return;

    // Initial-active fallback — first registered section wins until
    // the observer's first callback overrides. Runs inside the IO
    // effect (not a separate effect) so it only applies once
    // sections actually exist in the DOM.
    setActiveSlug((current) => {
      if (current) return current;
      const firstSlug = sections[0].getAttribute("data-subnav-section");
      return firstSlug ?? "";
    });

    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort(
            (a, b) => a.boundingClientRect.top - b.boundingClientRect.top,
          );
        if (visible[0]) {
          const slug = visible[0].target.getAttribute("data-subnav-section");
          if (slug) setActiveSlug(slug);
        }
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
