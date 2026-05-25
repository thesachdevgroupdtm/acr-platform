import { motion } from "motion/react";
import { useNavigate } from "react-router-dom";
import PageBanner from "../components/PageBanner";
import { ArrowRight, AlertCircle, Tag } from "lucide-react";
import { useCoupons } from "../hooks/useCoupons";
import type { CouponResource } from "../types/api";

interface OffersProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

/**
 * Phase 2.6a — `Offers` is now backend-driven (was a static
 * showcase off `data/businessData.OFFERS` with marketing-fluff
 * fields like image / urgency / rating / customers count).
 *
 * Reads `useCoupons('marketing')` (Phase 2.5b) — the same hook
 * `Coupons.tsx` uses. The two pages serve overlapping purposes;
 * a future cleanup pass may consolidate. For now both stay
 * live so external links to either route continue to resolve.
 */
function formatDiscount(c: CouponResource): string {
  if (c.discount_type === "percent") {
    const cap = c.max_discount ? ` (UP TO ₹${c.max_discount})` : "";
    return `${c.discount_value}% OFF${cap}`;
  }
  return `FLAT ₹${c.discount_value} OFF`;
}

function badgeLabel(c: CouponResource): string {
  if (!c.badge) return "LIMITED TIME";
  return c.badge.toUpperCase();
}

export default function Offers(_props: OffersProps) {
  const navigate = useNavigate();
  const { coupons, isLoading, isError } = useCoupons("marketing");

  return (
    <>
      <PageBanner
        title="Offers & Deals"
        breadcrumbs={[
          { label: "Home", href: "/" },
          { label: "Offers" }
        ]}
      />
      <div className="section-spacing pt-0">
        <div className="site-container">

          <div className="max-w-3xl mb-16">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
            >
              <h2 className="section-heading mb-4">
                LIMITED TIME <span className="section-heading-accent">OFFERS.</span>
              </h2>
              <p className="text-lg md:text-xl text-neutral-500 leading-relaxed font-medium">
                Dealership-grade services at transparent prices. Limited slots available.
              </p>
            </motion.div>
          </div>

          {isLoading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {[0, 1, 2].map((i) => (
                <div key={i} className="bg-white border border-border h-[400px] animate-pulse" />
              ))}
            </div>
          ) : isError || coupons.length === 0 ? (
            <div className="py-16 text-center text-sm text-neutral-500">
              No active offers right now. Check back soon — new deals drop regularly.
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {coupons.map((offer, i) => (
                <motion.div
                  key={offer.id}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.5, delay: i * 0.1 }}
                  className="group relative bg-white border border-border flex flex-col overflow-hidden hover:-translate-y-2 transition-all duration-300 shadow-sm hover:shadow-2xl hover:shadow-primary/10 hover:border-primary/30"
                >
                  {/* Header band — brand-coloured gradient (no per-coupon
                      photos on the backend resource). */}
                  <div className="relative h-[250px] overflow-hidden bg-neutral-900">
                    <div className="absolute inset-0 bg-gradient-to-br from-primary-dark via-primary to-primary-dark/80" />
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent_60%)]" />

                    <div className="absolute top-4 left-4 z-10 flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white px-3 py-1.5 text-[10px] font-black uppercase tracking-widest border border-white/20">
                      <AlertCircle className="w-3 h-3" />
                      {badgeLabel(offer)}
                    </div>

                    <div className="absolute bottom-4 left-4 right-4 z-10">
                      <div className="inline-block px-4 py-1 mb-3 text-xs font-black uppercase tracking-widest shadow-md bg-white text-primary-dark">
                        {formatDiscount(offer)}
                      </div>
                      <h4 className="heading-h4 uppercase tracking-tighter !text-white">
                        {offer.name}
                      </h4>
                    </div>
                  </div>

                  <div className="p-6 md:p-8 flex-grow flex flex-col justify-between">
                    <div>
                      <p className="text-neutral-600 font-medium mb-6 leading-relaxed">
                        {offer.description}
                      </p>

                      <div className="flex items-center gap-3 mb-8 pb-6 border-b border-border flex-wrap">
                        <div className="flex items-center gap-1.5 px-3 py-1.5 bg-neutral-50 border border-dashed border-neutral-300 text-neutral-900 font-mono text-xs font-black tracking-widest">
                          <Tag className="w-3 h-3 text-primary" />
                          {offer.code}
                        </div>
                        {offer.min_order_value > 0 && (
                          <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-500">
                            Min order ₹{offer.min_order_value}
                          </span>
                        )}
                        {offer.expiry_date && (
                          <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-500">
                            Valid till {offer.expiry_date}
                          </span>
                        )}
                      </div>
                    </div>

                    <button
                      onClick={() => navigate("/services")}
                      className="btn-ink btn-ink-primary w-full py-5 text-sm font-black uppercase tracking-widest flex items-center justify-center gap-2 group/btn"
                    >
                      BROWSE SERVICES <ArrowRight className="w-5 h-5 btn-arrow transition-transform duration-300 group-hover/btn:-rotate-45" />
                    </button>
                  </div>
                </motion.div>
              ))}
            </div>
          )}

          {/* Bottom CTA */}
          <div className="mt-24 p-10 md:p-16 bg-neutral-900 text-center relative overflow-hidden">
            <div className="relative z-10">
              <h2 className="section-heading !text-white mb-6">
                NEED A CUSTOM{" "}
                <span className="section-heading-accent">SOLUTION?</span>
              </h2>
              <p className="text-neutral-400 mb-10 text-lg max-w-2xl mx-auto">
                Speak directly with our multi-brand technical experts. We'll diligently inspect your vehicle and configure the perfect service package with maximum savings.
              </p>
              <div className="flex flex-wrap items-center justify-center gap-4">
                <button className="btn-ink btn-ink-primary px-8 py-4 font-bold text-sm tracking-widest uppercase shadow-lg shadow-primary/20">
                  Call Now <ArrowRight className="w-4 h-4 btn-arrow" />
                </button>
                <a
                  href="https://wa.me/911234567890"
                  target="_blank"
                  rel="noreferrer"
                  className="bg-[#25D366] text-white px-8 py-4 font-bold text-sm tracking-widest uppercase flex items-center gap-2 hover:bg-[#20bd5a] transition-colors"
                >
                  WhatsApp
                </a>
                <button className="btn-ink btn-ink-white px-8 py-4 font-bold text-sm tracking-widest uppercase">
                  Get Free Inspection
                </button>
              </div>
            </div>

            {/* Simple abstract bg */}
            <div className="absolute inset-0 opacity-10 flex items-center justify-center pointer-events-none">
              <div className="w-[800px] h-[800px] border-[40px] border-white rounded-full -translate-y-1/2"></div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
