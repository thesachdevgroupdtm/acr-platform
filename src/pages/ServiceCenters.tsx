import { motion } from "motion/react";
import { useNavigate } from "react-router-dom";
import { MapPin, Phone, Star, ArrowRight, Clock, Shield } from "lucide-react";
import { LOCATIONS } from "../data/businessData";
import PageBanner from "../components/PageBanner";
import SeoHead from "../components/SeoHead";
import ApiErrorState from "../components/ApiErrorState";
import EmptyState from "../components/EmptyState";
import { useServiceCenters } from "../hooks/useServiceCenters";
import type { ServiceCenterResource } from "../types/api";

interface ServiceCentersProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

/**
 * Phase 4.2.5 fix #2 — migrated from a fully static `LOCATIONS`
 * loop to API-driven rendering via `/api/v1/service-centers`.
 *
 * The backend is the single source of truth for which centers exist
 * (id/slug/name/address/phone). Until the backend grows
 * `image`/`features`/`rating` columns (Phase 4.5+), we enrich each
 * row by looking up the legacy `LOCATIONS` constant by slug for
 * presentation-only fields. Centers that don't have a legacy match
 * still render with sensible fallbacks (placeholder image, empty
 * feature list).
 */

const STATIC_BY_SLUG = new Map(LOCATIONS.map((l) => [l.id, l]));

function enrich(row: ServiceCenterResource) {
  const legacy = STATIC_BY_SLUG.get(row.slug);
  return {
    id: row.id,
    slug: row.slug,
    name: row.name,
    address: row.address,
    phone: row.phone,
    rating: legacy?.rating ?? "4.8",
    image:
      legacy?.image ??
      "https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&q=80&w=1200",
    features: legacy?.features ?? [],
  };
}

export default function ServiceCenters(_props: ServiceCentersProps) {
  const navigate = useNavigate();
  const { centers, seo, isLoading, isError, refetch } = useServiceCenters();

  return (
    <>
      {/* Phase 4.5c — list-level SEO synthesised from SiteSeoSettings
          server-side. Defensive guard avoids flashing a partial <head>. */}
      {seo && <SeoHead seo={seo} />}
      <PageBanner
        title="Our Centres"
        breadcrumbs={[
          { label: "Home", href: "/" },
          { label: "Service Centers" },
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">
          <h2 className="section-heading mb-8">OUR <span className="section-heading-accent">CENTRES.</span></h2>
          {isLoading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {[0, 1, 2, 3].map((i) => (
                <div
                  key={i}
                  className="bg-white border border-border overflow-hidden"
                >
                  <div className="h-56 bg-neutral-100 animate-pulse" />
                  <div className="p-6 space-y-3">
                    <div className="h-5 w-2/3 bg-neutral-200 animate-pulse" />
                    <div className="h-3 w-full bg-neutral-100 animate-pulse" />
                    <div className="h-3 w-1/2 bg-neutral-100 animate-pulse" />
                    <div className="h-9 w-full bg-neutral-200 animate-pulse mt-4" />
                  </div>
                </div>
              ))}
            </div>
          ) : isError ? (
            <ApiErrorState
              message="Couldn't load service centers."
              detail="Check your connection and retry."
              onRetry={refetch}
              data-testid="service-centers-error"
            />
          ) : centers.length === 0 ? (
            <EmptyState
              title="No service centers configured"
              hint="Please check back shortly."
            />
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {centers.map((row, i) => {
                const c = enrich(row);
                return (
                  <motion.div
                    key={c.id}
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    transition={{ delay: i * 0.1 }}
                    viewport={{ once: true }}
                    className="bg-white border border-border group overflow-hidden shadow-sm hover:shadow-xl transition-all"
                  >
                    <div className="relative h-56 overflow-hidden">
                      <img
                        src={c.image}
                        alt={c.name}
                        className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                        referrerPolicy="no-referrer"
                      />
                      <div className="absolute top-3 right-3 bg-primary text-white px-2 py-0.5 text-[8px] font-bold uppercase tracking-widest">
                        {c.rating} <Star className="w-2.5 h-2.5 inline-block fill-current" />
                      </div>
                    </div>

                    <div className="p-6">
                      <h3 className="text-xl font-black uppercase mb-3 text-neutral-900">
                        {c.name}
                      </h3>
                      <div className="space-y-3 mb-6">
                        <p className="text-[13px] text-muted leading-relaxed mb-4">
                          {c.name} is a premier ACR facility equipped with the
                          latest diagnostic tools and highly trained technicians.
                          Serving the local community, it ensures your vehicle
                          receives meticulous, factory-standard care for all
                          repair needs.
                        </p>
                        <div className="flex items-start gap-3 text-xs text-neutral-500">
                          <MapPin className="w-4 h-4 text-primary shrink-0" />
                          <span>{c.address}</span>
                        </div>
                        <div className="flex items-center gap-3 text-xs text-neutral-500">
                          <Phone className="w-4 h-4 text-primary shrink-0" />
                          <span>{c.phone}</span>
                        </div>
                      </div>

                      {c.features.length > 0 && (
                        <div className="flex flex-wrap gap-2 mb-6">
                          {c.features.map((feature, j) => (
                            <span
                              key={j}
                              className="bg-neutral-50 text-[8px] font-bold uppercase tracking-widest px-2 py-1 text-neutral-400 border border-border"
                            >
                              {feature}
                            </span>
                          ))}
                        </div>
                      )}

                      <button
                        onClick={() => navigate(`/center/${c.slug}`)}
                        className="w-full border border-primary text-primary py-3 text-[10px] font-bold uppercase tracking-widest hover:bg-primary hover:text-white transition-all flex items-center justify-center gap-2"
                      >
                        View Centre Details <ArrowRight className="w-4 h-4" />
                      </button>
                    </div>
                  </motion.div>
                );
              })}
            </div>
          )}

          {/* Global Standards */}
          <div className="mt-24 grid grid-cols-1 md:grid-cols-3 gap-12 border-t border-border pt-16">
            <div className="text-center space-y-3">
              <div className="bg-neutral-50 w-12 h-12 flex items-center justify-center mx-auto border border-border">
                <Shield className="w-6 h-6 text-primary" />
              </div>
              <h4 className="text-lg font-black uppercase text-neutral-900">
                Standardized Quality
              </h4>
              <p className="text-sm text-neutral-500">
                Uniform quality checks and repair protocols across all our NCR
                locations.
              </p>
            </div>
            <div className="text-center space-y-3">
              <div className="bg-neutral-50 w-12 h-12 flex items-center justify-center mx-auto border border-border">
                <Clock className="w-6 h-6 text-primary" />
              </div>
              <h4 className="text-lg font-black uppercase text-neutral-900">
                Centralized Tracking
              </h4>
              <p className="text-sm text-neutral-500">
                Track your vehicle's repair status in real-time regardless of the
                location.
              </p>
            </div>
            <div className="text-center space-y-3">
              <div className="bg-neutral-50 w-12 h-12 flex items-center justify-center mx-auto border border-border">
                <Star className="w-6 h-6 text-primary" />
              </div>
              <h4 className="text-lg font-black uppercase text-neutral-900">
                Expert Mobility
              </h4>
              <p className="text-sm text-neutral-500">
                Our master technicians travel between centres for specialized
                restoration tasks.
              </p>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
