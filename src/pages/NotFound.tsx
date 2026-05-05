import { ArrowRight, Compass } from "lucide-react";
import PageBanner from "../components/PageBanner";

interface NotFoundProps {
  setCurrentPage: (page: string) => void;
}

/**
 * Phase 2.6a-fix (Test 1) — graceful 404 for unknown URLs.
 *
 * Pre-fix, App.tsx's switch had `default: return <Home />`, so a
 * direct hit on /payment (deleted in 2.6a) or any typo silently
 * rendered the homepage at the wrong URL — operator-confusing.
 * This component renders at the original URL so the browser bar
 * stays honest, and offers a clear path back to /home.
 */
export default function NotFound({ setCurrentPage }: NotFoundProps) {
  return (
    <>
      <PageBanner
        title="Page Not Found"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Not Found" },
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">
          <div className="bg-white border border-border max-w-2xl mx-auto py-16 px-6 sm:px-12 text-center mt-10">
            <div className="w-16 h-16 bg-primary/10 text-primary mx-auto mb-6 flex items-center justify-center">
              <Compass className="w-8 h-8" />
            </div>
            <h2 className="text-3xl sm:text-5xl font-black uppercase tracking-tighter text-neutral-900 mb-3">
              Page <span className="text-primary">Not Found.</span>
            </h2>
            <p className="text-sm sm:text-base text-neutral-500 leading-relaxed mb-8 max-w-md mx-auto">
              The page you're looking for doesn't exist or has moved. Try
              heading back to the home page.
            </p>
            <button
              onClick={() => setCurrentPage("home")}
              className="btn-ink btn-ink-primary inline-flex items-center gap-2 px-8 py-4 text-xs font-black uppercase tracking-widest"
            >
              Go to Home
              <ArrowRight className="w-4 h-4 btn-arrow" />
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
