import { motion } from "motion/react";
import { ShoppingCart, ArrowRight } from "lucide-react";
import { useCart } from "../hooks/useCart";

interface SmartMiniCartProps {
  /** Sibling-level positioning hook for the parent sidebar layout. */
  className?: string;
  /**
   * Page-router setter passed down from the parent page (Phase 2.5.2
   * `navigateTo` arrives under this name). Optional — when omitted,
   * the VIEW CART CTA falls back to a hard `window.location` change
   * so the component stays usable in isolation.
   */
  setCurrentPage?: (page: string) => void;
}

const MAX_VISIBLE = 3;

const inrFormatter = new Intl.NumberFormat("en-IN", {
  style: "currency",
  currency: "INR",
  maximumFractionDigits: 0,
});

/**
 * Phase 2.5.5 (D-2.5.5-3) — contextual mini-cart for product-browse
 * pages (ServiceCategory, ServiceDetail, Services).
 *
 * Renders only when the cart has items. Sits ABOVE the existing
 * BookingSidebar / Re-Check Prices panel as a sibling, so the two
 * cards stack visually without re-flow when items are added.
 *
 * Layout (top → bottom):
 *   - Header row: cart icon + "{N} ITEM(S) IN CART"
 *   - Item lines (max 3) — title left, line-total right
 *   - "+ {K} more items" overflow line when > 3
 *   - Total row (border-t)
 *   - VIEW CART primary CTA (full-width)
 *
 * Total is formatted with Intl.NumberFormat('en-IN') so a ₹1650
 * value renders "₹1,650" rather than "₹1650".
 */
export default function SmartMiniCart({
  className = "",
  setCurrentPage,
}: SmartMiniCartProps) {
  const { items, subtotal } = useCart();

  if (items.length === 0) return null;

  const visible = items.slice(0, MAX_VISIBLE);
  const remaining = items.length - MAX_VISIBLE;

  const handleViewCart = () => {
    if (setCurrentPage) {
      setCurrentPage("cart");
      return;
    }
    if (typeof window !== "undefined") {
      window.location.href = "/cart";
    }
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.25, ease: "easeOut" }}
      className={`bg-white border border-primary/30 shadow-xl ${className}`}
    >
      <div className="px-5 py-3 border-b border-border flex items-center gap-2">
        <ShoppingCart className="w-4 h-4 text-primary" />
        <p className="text-xs font-black uppercase tracking-widest text-primary">
          {items.length} {items.length === 1 ? "Item" : "Items"} in Cart
        </p>
      </div>

      <ul className="px-5 py-3 space-y-2 divide-y divide-border">
        {visible.map((item) => {
          const lineTotal = item.price * item.qty;
          return (
            <li
              key={item.id}
              className="pt-2 first:pt-0 flex items-start justify-between gap-3 text-sm"
            >
              <span className="font-bold text-neutral-700 truncate min-w-0 flex-1">
                {item.title}
                {item.qty > 1 && (
                  <span className="font-normal text-neutral-400"> × {item.qty}</span>
                )}
              </span>
              <span className="font-bold text-neutral-900 shrink-0">
                {lineTotal > 0 ? inrFormatter.format(lineTotal) : "Quote"}
              </span>
            </li>
          );
        })}
        {remaining > 0 && (
          <li className="pt-2 text-xs text-neutral-500 italic">
            + {remaining} more {remaining === 1 ? "item" : "items"}
          </li>
        )}
      </ul>

      <div className="px-5 py-3 border-t border-border flex items-baseline justify-between">
        <span className="text-[10px] font-bold uppercase tracking-widest text-neutral-500">
          Total
        </span>
        <span className="text-base font-black text-neutral-900">
          {inrFormatter.format(subtotal)}
        </span>
      </div>

      <div className="px-5 pb-4">
        <button
          onClick={handleViewCart}
          className="btn-ink btn-ink-primary w-full py-3 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2"
        >
          View Cart <ArrowRight className="w-4 h-4 btn-arrow" />
        </button>
      </div>
    </motion.div>
  );
}
