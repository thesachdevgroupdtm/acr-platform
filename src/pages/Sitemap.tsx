import { ArrowRight } from "lucide-react";
import { useNavigate } from "react-router-dom";
import PageBanner from "../components/PageBanner";
import { LOCATIONS } from "../data/businessData";
import {
  fetchHome,
  type ServiceCategory as ApiCategory,
  type CategorySubService,
} from "../lib/api";
import { useApiQuery } from "../hooks/useApiQuery";

interface SitemapProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

// Phase 3B — explicit label → URL map. The legacy code derived
// the URL from page.toLowerCase().replace(' ', '-'); under the new
// router we want each link to land on a real route, so the map is
// canonical (and "Home" maps to "/" which the old shim already
// handled, just less obviously).
const MAIN_PAGES: Array<{ label: string; path: string }> = [
  { label: "Home",            path: "/" },
  { label: "Services",        path: "/services" },
  { label: "Service Centers", path: "/service-centers" },
  { label: "Insurance",       path: "/insurance" },
  { label: "Corporate",       path: "/corporate" },
  { label: "Gallery",         path: "/gallery" },
  { label: "About",           path: "/about" },
  { label: "Contact",         path: "/contact" },
  { label: "Offers",          path: "/offers" },
  { label: "Coupons",         path: "/coupons" },
  { label: "Testimonials",    path: "/testimonials" },
];

export default function Sitemap(_props: SitemapProps) {
  const navigate = useNavigate();
  // Single /home request. Sub-services are nested under each category in
  // the response (Phase 1.6) — no per-category round trips.
  const home = useApiQuery(["home"], (signal) => fetchHome(signal));
  const categories: ApiCategory[] = home.data?.service_categories ?? [];

  // Flatten the nested sub-services for the "All Services" column.
  const subs: Array<CategorySubService & { _categorySlug: string }> = [];
  for (const c of categories) {
    for (const s of c.services ?? []) {
      subs.push({ ...s, _categorySlug: c.slug });
    }
  }

  return (
    <>
      <PageBanner
        title="Sitemap"
        breadcrumbs={[
          { label: "Home", onClick: () => navigate("/") },
          { label: "Sitemap" },
        ]}
      />
      <div className="section-spacing bg-white">
        <div className="site-container">
          <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-12">

            {/* Main Links */}
            <div>
              <h3 className="text-xl font-black uppercase text-primary-dark mb-6 border-b border-border pb-4">Main Pages</h3>
              <ul className="space-y-4">
                {MAIN_PAGES.map(({ label, path }) => (
                  <li key={path}>
                    <button
                      onClick={() => navigate(path)}
                      className="text-muted hover:text-primary transition-colors text-sm font-medium flex items-center gap-2 group"
                    >
                      <ArrowRight className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                      {label}
                    </button>
                  </li>
                ))}
              </ul>
            </div>

            {/* Service Categories — from /home payload */}
            <div>
              <h3 className="text-xl font-black uppercase text-primary-dark mb-6 border-b border-border pb-4">Service Categories</h3>
              {home.isLoading ? (
                <ul className="space-y-4">
                  {Array.from({ length: 8 }).map((_, i) => (
                    <li key={i} className="h-4 w-40 bg-neutral-200 animate-pulse rounded" />
                  ))}
                </ul>
              ) : home.error ? (
                <p className="text-xs font-bold uppercase tracking-widest text-accent-dark">
                  Could not load: {home.error}
                </p>
              ) : (
                <ul className="space-y-4">
                  {categories.map((category) => (
                    <li key={category.id}>
                      <button
                        onClick={() => navigate(`/category/${category.slug}`)}
                        className="text-muted hover:text-primary transition-colors text-sm font-medium flex items-center gap-2 group"
                      >
                        <ArrowRight className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                        {category.title}
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            {/* Sub Services — flattened from /home's nested services */}
            <div>
              <h3 className="text-xl font-black uppercase text-primary-dark mb-6 border-b border-border pb-4">All Services</h3>
              {home.isLoading ? (
                <ul className="space-y-4">
                  {Array.from({ length: 12 }).map((_, i) => (
                    <li key={i} className="h-4 w-48 bg-neutral-200 animate-pulse rounded" />
                  ))}
                </ul>
              ) : (
                <ul className="space-y-4">
                  {subs.map((service) => (
                    <li key={`${service._categorySlug}-${service.id}`}>
                      <button
                        onClick={() =>
                          navigate(`/services/${service._categorySlug}/${service.slug}`)
                        }
                        className="text-muted hover:text-primary transition-colors text-sm font-medium flex items-center gap-2 group text-left"
                      >
                        <ArrowRight className="w-3 h-3 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" />
                        {service.title}
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            {/* Service Centers — still static (LOCATIONS); separate task to API-back. */}
            <div>
              <h3 className="text-xl font-black uppercase text-primary-dark mb-6 border-b border-border pb-4">Service Centers</h3>
              <ul className="space-y-4">
                {LOCATIONS.map(location => (
                  <li key={location.id}>
                    <button
                      onClick={() => navigate(`/center/${location.id}`)}
                      className="text-muted hover:text-primary transition-colors text-sm font-medium flex items-center gap-2 group text-left"
                    >
                      <ArrowRight className="w-3 h-3 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" />
                      {location.name}
                    </button>
                  </li>
                ))}
              </ul>
            </div>

          </div>
        </div>
      </div>
    </>
  );
}
