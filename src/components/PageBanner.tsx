import { motion } from "motion/react";
import { ReactNode } from "react";
import Breadcrumb, { type BreadcrumbItem } from "./layout/Breadcrumb";

/**
 * Cinematic page banner — restored to its pre-refactor visual posture
 * with one structural change per operator direction:
 *
 *   1. A thin breadcrumb strip renders ABOVE the cinematic banner
 *      block (between the main site header and the banner image).
 *      Bottom-bordered, ~44 px tall, sits flush.
 *
 *   2. The cinematic 40vh banner itself shows ONLY title (plus
 *      optional eyebrow `label` and optional `children` slot). NO
 *      breadcrumbs INSIDE the banner anymore.
 *
 * Page consumers continue to import + use `<PageBanner>` as before:
 *
 *   <PageBanner
 *     title="About Us"
 *     breadcrumbs={[
 *       { label: "Home", href: "/" },
 *       { label: "About" },
 *     ]}
 *   />
 *
 * Internal layout takes care of rendering the strip + banner in the
 * right order. Breadcrumbs can use either `href` (Link) or
 * `onClick` (button) — both supported by the shared Breadcrumb
 * component.
 */

interface PageBannerProps {
  title: string;
  breadcrumbs?: BreadcrumbItem[];
  label?: string;
  backgroundImage?: string;
  children?: ReactNode;
}

export default function PageBanner({
  title,
  breadcrumbs,
  label,
  backgroundImage = "https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=1200",
  children,
}: PageBannerProps) {
  return (
    <>
      {/* Breadcrumb strip — sits above the banner, between the main
          site header and the cinematic block. Edge-to-edge bottom
          border for separation from the banner below. */}
      {breadcrumbs && breadcrumbs.length > 0 && (
        <div className="bg-white border-b border-neutral-200">
          <div className="site-container">
            <div className="py-2.5 md:py-3">
              <Breadcrumb items={breadcrumbs} />
            </div>
          </div>
        </div>
      )}

      {/* Cinematic banner — title only (plus optional label + children) */}
      <div className="relative h-[40vh] min-h-[300px] flex items-center overflow-hidden mb-12">
        <img
          src={backgroundImage}
          className="absolute inset-0 w-full h-full object-cover opacity-30"
          alt={title}
          referrerPolicy="no-referrer"
        />
        <div className="absolute inset-0 bg-neutral-900/80" />
        <div className="absolute inset-0 bg-gradient-to-r from-primary/20 via-transparent to-transparent" />

        <div className="site-container relative z-10 w-full">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="max-w-4xl pt-10"
          >
            {label && (
              <span className="text-primary font-black uppercase tracking-[0.3em] mb-4 block text-xs">
                {label}
              </span>
            )}

            <h1 className="page-title shadow-sm">{title}</h1>

            {children && <div className="mt-6">{children}</div>}
          </motion.div>
        </div>
      </div>
    </>
  );
}
