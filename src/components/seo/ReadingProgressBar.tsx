import { useEffect, useState } from "react";

/**
 * Phase 4.5b-polish — top-of-page reading progress bar.
 *
 * Listens to scroll + resize and updates a 3px amber bar's
 * width in proportion to documentElement.scrollTop /
 * (scrollHeight - clientHeight). Hides when the page is too
 * short to scroll (no progress to show).
 *
 * Respects `prefers-reduced-motion` by dropping the CSS
 * transition; the bar still updates, just instantly.
 */
export default function ReadingProgressBar() {
  const [progress, setProgress] = useState(0);
  const [isScrollable, setIsScrollable] = useState(false);

  useEffect(() => {
    const update = () => {
      const doc = document.documentElement;
      const max = doc.scrollHeight - doc.clientHeight;
      if (max <= 0) {
        setIsScrollable(false);
        setProgress(0);
        return;
      }
      setIsScrollable(true);
      setProgress(Math.min(100, Math.max(0, (doc.scrollTop / max) * 100)));
    };

    update();
    window.addEventListener("scroll", update, { passive: true });
    window.addEventListener("resize", update);
    return () => {
      window.removeEventListener("scroll", update);
      window.removeEventListener("resize", update);
    };
  }, []);

  if (!isScrollable) return null;

  const reducedMotion =
    typeof window !== "undefined" &&
    window.matchMedia?.("(prefers-reduced-motion: reduce)").matches;

  return (
    <div
      data-testid="reading-progress"
      className="fixed top-0 left-0 right-0 z-[60] h-[3px] bg-transparent pointer-events-none"
      role="progressbar"
      aria-label="Reading progress"
      aria-valuenow={Math.round(progress)}
      aria-valuemin={0}
      aria-valuemax={100}
    >
      <div
        className={`h-full bg-primary ${reducedMotion ? "" : "transition-[width] duration-150 ease-out"}`}
        style={{ width: `${progress}%` }}
      />
    </div>
  );
}
