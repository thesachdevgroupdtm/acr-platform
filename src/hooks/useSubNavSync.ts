/**
 * useSubNavSync — Phase 2.5.6.
 *
 * Two-way sync between a sticky horizontal sub-nav and the page-body
 * sections it anchors to. Two responsibilities, one hook:
 *
 *   1. Scroll-spy: an IntersectionObserver watches each section in the
 *      page body. When a section's heading enters the upper-middle band
 *      of the viewport (rootMargin '-30% 0px -60% 0px'), it becomes the
 *      active section. The sub-nav reads `activeSection` to draw the
 *      blue underline on the current link.
 *
 *   2. Auto-scroll: when `activeSection` changes — whether from page
 *      scroll OR from a click handler — the active link in the sub-nav
 *      is scrolled into view via `scrollIntoView({ inline: 'center' })`.
 *      So if the user is reading section #10 of 12 and that link was
 *      off-screen in the horizontal nav, the nav scrolls itself to
 *      reveal the active link. Standard scrollspy-with-self-scroll
 *      pattern (Apple docs, Stripe docs, MDN sidebar).
 *
 * Sub-nav links MUST carry `data-subnav-link={sectionId}` for the
 * auto-scroll to find them. Sections in the page body are matched
 * via `document.getElementById(sectionId)` (existing convention on
 * Services.tsx and ServiceCategory.tsx).
 *
 * The exposed `scrollToSection(id)` helper handles click-driven page
 * navigation: it smooth-scrolls the page to the section, accounting
 * for the sticky-header offset (default 60px on top of the caller's
 * `stickyOffsetPx`), and proactively sets `activeSection` so the UI
 * doesn't lag the IntersectionObserver fire.
 */
import { useCallback, useEffect, useRef, useState, type RefObject } from "react";

interface UseSubNavSyncOptions {
  /** Slugs/ids of every page-body section the sub-nav anchors to.
   *  Order matters only for the initial-active fallback; the observer
   *  picks whichever is currently topmost-visible. */
  sectionIds: string[];
  /** Sticky-nav vertical offset for click-driven page scrolls. */
  stickyOffsetPx: number;
  /** When true, auto-scroll the sub-nav to centre the active link.
   *  Defaults to true; pages can opt out for testing. */
  autoScrollNav?: boolean;
}

interface UseSubNavSyncResult {
  activeSection: string;
  setActiveSection: (id: string) => void;
  scrollToSection: (id: string) => void;
  /** Attach to the <nav> element wrapping the sub-nav links. */
  navRef: RefObject<HTMLElement | null>;
}

const ROOT_MARGIN = "-30% 0px -60% 0px"; // upper-middle viewport band

export function useSubNavSync({
  sectionIds,
  stickyOffsetPx,
  autoScrollNav = true,
}: UseSubNavSyncOptions): UseSubNavSyncResult {
  const [activeSection, setActiveSection] = useState<string>("");
  const navRef = useRef<HTMLElement | null>(null);

  // Initial active fallback — first id wins when nothing has been
  // observed yet. Run on every change to sectionIds so async data
  // flows (e.g. Services.tsx loading categories from API) seed the
  // active state once the list arrives.
  useEffect(() => {
    if (!activeSection && sectionIds.length > 0) {
      setActiveSection(sectionIds[0]);
    }
  }, [sectionIds, activeSection]);

  // Scroll-spy.
  useEffect(() => {
    if (sectionIds.length === 0) return;
    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort(
            (a, b) => a.boundingClientRect.top - b.boundingClientRect.top,
          );
        if (visible[0]) setActiveSection(visible[0].target.id);
      },
      { rootMargin: ROOT_MARGIN, threshold: 0 },
    );
    sectionIds.forEach((id) => {
      const el = document.getElementById(id);
      if (el) observer.observe(el);
    });
    return () => observer.disconnect();
    // sectionIds is a stable list per page render; .join wards against
    // identity-only changes from upstream React Query that don't actually
    // alter the slugs.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [sectionIds.join("|")]);

  // Auto-scroll the sub-nav so the active link stays visible.
  useEffect(() => {
    if (!autoScrollNav) return;
    if (!activeSection || !navRef.current) return;
    const link = navRef.current.querySelector<HTMLElement>(
      `[data-subnav-link="${CSS.escape(activeSection)}"]`,
    );
    if (!link) return;
    // block: 'nearest' — never scrolls the page vertically (the nav is
    // sticky and we don't want to fight the user's vertical scroll).
    // inline: 'center' — centres the active link inside the nav's
    // horizontal scroll container; if the link is already visible the
    // browser typically no-ops. behavior: 'smooth' — animated.
    link.scrollIntoView({
      behavior: "smooth",
      block: "nearest",
      inline: "center",
    });
  }, [activeSection, autoScrollNav]);

  const scrollToSection = useCallback(
    (id: string) => {
      const el = document.getElementById(id);
      if (!el) return;
      const top =
        el.getBoundingClientRect().top + window.scrollY - (stickyOffsetPx + 60);
      window.scrollTo({ top, behavior: "smooth" });
      // Pre-set so the underline updates immediately rather than waiting
      // for the IntersectionObserver to fire after the smooth scroll
      // completes; the observer will re-confirm on its own.
      setActiveSection(id);
    },
    [stickyOffsetPx],
  );

  return { activeSection, setActiveSection, scrollToSection, navRef };
}
