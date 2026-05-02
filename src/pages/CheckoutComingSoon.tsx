// Phase 2.3.4 — currently unreachable; preserved for reference and
// possible re-use during Phase 2.5 partial rollouts.
/**
 * Phase 2.3.2 — placeholder rendered when FEATURES.checkoutFlow is off.
 *
 * The pre-2.5 client-side Checkout/Payment flow generated fake
 * `ACR<timestamp>` invoices that no backend ever saw, then sent
 * users to MyBookings which couldn't find them. This page replaces
 * that experience with an honest "coming soon" notice plus a Call
 * Now CTA so motivated users still convert through the phone team.
 *
 * Phase 2.5 will flip the flag and route users to a real Checkout
 * that hits POST /checkout/place-order.
 */
import { ArrowLeft, Phone, ShoppingCart } from "lucide-react";
import PageBanner from "../components/PageBanner";
import { BUSINESS_INFO } from "../data/businessData";
import { useCart } from "../hooks/useCart";

interface CheckoutComingSoonProps {
  setCurrentPage: (page: string) => void;
}

export default function CheckoutComingSoon({
  setCurrentPage,
}: CheckoutComingSoonProps) {
  const { items, subtotal, count } = useCart();

  return (
    <>
      <PageBanner
        title="Checkout"
        breadcrumbs={[
          { label: "Home", onClick: () => setCurrentPage("home") },
          { label: "Cart", onClick: () => setCurrentPage("cart") },
          { label: "Checkout" },
        ]}
      />

      <div className="pb-14 pt-8">
        <div className="site-container">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10 mt-6">
            {/* MAIN — coming soon notice */}
            <div className="lg:col-span-2">
              <div className="bg-white border border-border p-6 sm:p-10">
                <div className="w-14 h-14 bg-primary/10 mx-auto mb-5 flex items-center justify-center">
                  <Phone className="w-7 h-7 text-primary" />
                </div>
                <h2 className="text-2xl sm:text-3xl uppercase font-black text-neutral-900 tracking-tighter mb-3 text-center">
                  Online Checkout{" "}
                  <span className="text-primary">Coming Soon.</span>
                </h2>
                <p className="text-sm text-neutral-600 leading-relaxed max-w-lg mx-auto text-center mb-7">
                  We're upgrading our online booking system. To book your
                  service right now, please call us or use the Quick
                  Estimate form on the homepage and our team will confirm
                  your booking.
                </p>
                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                  <a
                    href={`tel:+91${BUSINESS_INFO.phone}`}
                    className="btn-ink btn-ink-primary px-7 py-3.5 text-xs font-black uppercase tracking-widest inline-flex items-center justify-center gap-2"
                  >
                    <Phone className="w-4 h-4" /> Call +91 {BUSINESS_INFO.phone}
                  </a>
                  <button
                    onClick={() => setCurrentPage("cart")}
                    className="bg-white border border-border text-neutral-700 px-7 py-3.5 text-xs font-black uppercase tracking-widest hover:border-primary hover:text-primary transition-colors inline-flex items-center justify-center gap-2"
                  >
                    <ArrowLeft className="w-4 h-4" /> Back to Cart
                  </button>
                </div>
              </div>
            </div>

            {/* SIDE — read-only cart summary */}
            <aside className="lg:sticky lg:self-start lg:top-28">
              <div className="bg-white border border-border p-5">
                <div className="flex items-center gap-2 mb-4">
                  <ShoppingCart className="w-4 h-4 text-primary" />
                  <h3 className="text-xs font-black uppercase tracking-widest text-neutral-700">
                    Your Cart ({count})
                  </h3>
                </div>
                {items.length === 0 ? (
                  <p className="text-xs text-neutral-500">
                    Your cart is empty.
                  </p>
                ) : (
                  <ul className="divide-y divide-border">
                    {items.map((it) => (
                      <li key={it.id} className="py-2.5 flex items-start justify-between gap-2">
                        <div className="min-w-0 flex-1">
                          <p className="text-xs font-bold text-neutral-900 truncate">
                            {it.title}
                          </p>
                          <p className="text-[10px] text-neutral-500">
                            Qty {it.qty}
                          </p>
                        </div>
                        <p className="text-xs font-black text-neutral-900 whitespace-nowrap">
                          ₹{(it.price * it.qty).toLocaleString("en-IN")}
                        </p>
                      </li>
                    ))}
                  </ul>
                )}
                {items.length > 0 && (
                  <div className="border-t border-border mt-3 pt-3 flex items-center justify-between">
                    <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-500">
                      Subtotal
                    </span>
                    <span className="text-sm font-black text-neutral-900">
                      ₹{subtotal.toLocaleString("en-IN")}
                    </span>
                  </div>
                )}
              </div>
            </aside>
          </div>
        </div>
      </div>
    </>
  );
}
