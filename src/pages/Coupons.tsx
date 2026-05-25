import type * as React from "react";
import { useState } from "react";
import { motion } from "motion/react";
import { useNavigate } from "react-router-dom";
import PageBanner from "../components/PageBanner";
import { Copy, CheckCircle2, Ticket, Tag, ArrowRight } from "lucide-react";
import { useCoupons } from "../hooks/useCoupons";
import type { CouponResource } from "../types/api";

interface CouponsProps {
  openEstimate?: (isCorporate?: boolean, initialService?: string) => void;
}

/**
 * Phase 2.5b — public coupons listing.
 *
 * Reads featured/active/non-expired coupons from GET /coupons
 * (marketing context). Each card has a Copy Code button using the
 * Clipboard API; success shows a 1.5s "Copied!" toast inline.
 *
 * Marketing context — no Apply button on cards (per D-2.5b-2). To
 * apply, the user navigates to Cart and uses the picker modal.
 */
export default function Coupons(_props: CouponsProps) {
  const navigate = useNavigate();
  const { coupons, isLoading, isError } = useCoupons("marketing");
  const [copiedCode, setCopiedCode] = useState<string | null>(null);

  const copyCode = async (code: string) => {
    try {
      await navigator.clipboard.writeText(code);
      setCopiedCode(code);
      window.setTimeout(() => setCopiedCode((c) => (c === code ? null : c)), 1500);
    } catch {
      // Browser blocked clipboard or permissions denied — silent
      // (the user can still type the code).
    }
  };

  return (
    <>
      <PageBanner
        title="Available Coupons"
        breadcrumbs={[
          { label: "Home", href: "/" },
          { label: "Coupons" },
        ]}
      />

      <div className="pb-14 pt-8">
        <div className="site-container max-w-5xl">
          <div className="flex items-baseline justify-between mb-8">
            <div>
              <h2 className="section-heading">
                CURRENT <span className="section-heading-accent">OFFERS.</span>
              </h2>
              <p className="text-xs text-neutral-500 mt-1">
                Tap Copy Code, then apply at checkout.
              </p>
            </div>
            <button
              onClick={() => navigate("/services")}
              className="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-primary hover:underline flex items-center gap-1"
            >
              Browse Services <ArrowRight className="w-3 h-3" />
            </button>
          </div>

          {isLoading ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {[0, 1, 2, 3].map((i) => (
                <div key={i} className="bg-white border border-border p-5 space-y-3">
                  <div className="h-4 w-1/3 bg-neutral-200 animate-pulse" />
                  <div className="h-3 w-2/3 bg-neutral-200 animate-pulse" />
                  <div className="h-3 w-1/2 bg-neutral-100 animate-pulse" />
                  <div className="h-9 w-32 bg-neutral-200 animate-pulse" />
                </div>
              ))}
            </div>
          ) : isError ? (
            <div className="bg-white border border-accent-dark/30 py-16 text-center">
              <p className="text-sm text-accent-dark">Couldn't load coupons.</p>
            </div>
          ) : coupons.length === 0 ? (
            <div className="bg-white border border-border py-16 px-6 text-center">
              <div className="w-14 h-14 bg-neutral-100 mx-auto mb-4 flex items-center justify-center">
                <Ticket className="w-7 h-7 text-neutral-400" />
              </div>
              <h3 className="text-lg font-black uppercase tracking-tighter text-neutral-900 mb-1">
                No coupons available
              </h3>
              <p className="text-xs text-neutral-500">
                Check back soon — new offers drop regularly.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {coupons.map((c) => (
                <CouponCard
                  key={c.id}
                  coupon={c}
                  copied={copiedCode === c.code}
                  onCopy={() => void copyCode(c.code)}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </>
  );
}

interface CouponCardProps {
  coupon: CouponResource;
  copied: boolean;
  onCopy: () => void;
}

const CouponCard: React.FC<CouponCardProps> = ({ coupon, copied, onCopy }) => {
  const conditions: string[] = [];
  if (coupon.min_order_value > 0) {
    conditions.push(`Min order ₹${coupon.min_order_value}`);
  }
  if (coupon.max_discount !== null) {
    conditions.push(`Max ₹${coupon.max_discount} off`);
  }
  if (coupon.expiry_date) {
    conditions.push(`Valid till ${coupon.expiry_date}`);
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.25, ease: "easeOut" }}
      className="bg-white border border-border p-5 hover:border-primary transition-colors"
    >
      <div className="flex items-start justify-between gap-3 mb-2">
        <div className="min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            <p className="text-base font-black uppercase tracking-tighter text-neutral-900">
              {coupon.code}
            </p>
            {coupon.badge && (
              <span className="bg-primary text-white text-[8px] font-bold uppercase tracking-widest px-1.5 py-0.5">
                {coupon.badge}
              </span>
            )}
          </div>
          <p className="text-sm font-bold text-neutral-700 mt-0.5">{coupon.name}</p>
        </div>
        <Tag className="w-5 h-5 text-primary shrink-0" />
      </div>

      <p className="text-xs text-neutral-500 leading-relaxed mb-3">
        {coupon.description}
      </p>

      {conditions.length > 0 && (
        <p className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 mb-3">
          {conditions.join(" · ")}
        </p>
      )}

      <button
        onClick={onCopy}
        className={`btn-ink ${
          copied ? "btn-ink-primary" : "btn-ink-outline"
        } px-4 py-2.5 text-[10px] font-black uppercase tracking-widest inline-flex items-center gap-1.5 transition-colors`}
      >
        {copied ? (
          <>
            <CheckCircle2 className="w-3.5 h-3.5" /> Copied!
          </>
        ) : (
          <>
            <Copy className="w-3.5 h-3.5" /> Copy Code
          </>
        )}
      </button>
    </motion.div>
  );
};
